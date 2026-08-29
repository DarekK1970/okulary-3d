<?php

namespace Tests\Feature;

use App\Enums\NewsletterCampaignStatus;
use App\Enums\NewsletterSubscriberStatus;
use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\NewsletterCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_footer_contains_real_double_opt_in_form(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee(
                route('newsletter.subscribe', ['locale' => 'pl']),
                false
            )
            ->assertSee('name="consent"', false)
            ->assertSee('type="email"', false);
    }

    public function test_subscription_creates_pending_record_and_sends_confirmation_mail(): void
    {
        $this->post('/pl/newsletter/subscribe', [
            'email' => 'Stereo.User@Example.com',
            'consent' => '1',
        ])->assertRedirect();

        $subscriber = NewsletterSubscriber::query()->firstOrFail();

        $this->assertSame('stereo.user@example.com', $subscriber->email);
        $this->assertSame('pl', $subscriber->locale);
        $this->assertSame(
            NewsletterSubscriberStatus::Pending,
            $subscriber->status
        );
        $this->assertNotEmpty($subscriber->confirmation_token);
        $this->assertNotEmpty($subscriber->unsubscribe_token);

        Mail::assertSent(
            NewsletterConfirmationMail::class,
            fn ($mail): bool => $mail->hasTo('stereo.user@example.com')
        );
    }

    public function test_confirmation_activates_subscriber(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'confirm@example.com',
            'locale' => 'pl',
            'status' => NewsletterSubscriberStatus::Pending,
            'source' => 'footer',
            'confirmation_token' => 'confirm-token',
            'unsubscribe_token' => 'unsubscribe-token',
            'consent_requested_at' => now(),
        ]);

        $this->get('/pl/newsletter/confirm/' . $subscriber->id . '/confirm-token')
            ->assertOk()
            ->assertSee('Subskrypcja potwierdzona');

        $subscriber->refresh();

        $this->assertSame(
            NewsletterSubscriberStatus::Active,
            $subscriber->status
        );
        $this->assertNotNull($subscriber->confirmed_at);
        $this->assertNull($subscriber->confirmation_token);
    }

    public function test_unsubscribe_link_requires_explicit_confirmation_post(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'unsubscribe@example.com',
            'locale' => 'pl',
            'status' => NewsletterSubscriberStatus::Active,
            'source' => 'footer',
            'confirmation_token' => null,
            'unsubscribe_token' => 'unsubscribe-token',
            'confirmed_at' => now(),
        ]);

        $this->get('/pl/newsletter/unsubscribe/' . $subscriber->id . '/unsubscribe-token')
            ->assertOk()
            ->assertSee('Potwierdzam wypisanie');

        $this->assertSame(
            NewsletterSubscriberStatus::Active,
            $subscriber->fresh()->status
        );

        $this->post('/pl/newsletter/unsubscribe/' . $subscriber->id . '/unsubscribe-token')
            ->assertOk()
            ->assertSee('Adres został wypisany');

        $this->assertSame(
            NewsletterSubscriberStatus::Unsubscribed,
            $subscriber->fresh()->status
        );
    }

    public function test_editor_cannot_access_subscriber_database(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/admin/newsletter')
            ->assertForbidden();
    }

    public function test_admin_can_manage_newsletter(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/newsletter')
            ->assertOk()
            ->assertSee('Newsletter')
            ->assertSee('Subskrybenci')
            ->assertSee('Kampanie');
    }

    public function test_campaign_sends_only_to_active_subscribers_in_campaign_locale(): void
    {
        $activePl = $this->subscriber(
            'active-pl@example.com',
            'pl',
            NewsletterSubscriberStatus::Active
        );

        $this->subscriber(
            'pending-pl@example.com',
            'pl',
            NewsletterSubscriberStatus::Pending
        );

        $this->subscriber(
            'active-en@example.com',
            'en',
            NewsletterSubscriberStatus::Active
        );

        $campaign = NewsletterCampaign::create([
            'locale' => 'pl',
            'subject' => 'Nowości 3D',
            'preheader' => 'Najciekawsze materiały tygodnia',
            'body_html' => '<p>Treść kampanii.</p>',
            'status' => NewsletterCampaignStatus::Scheduled,
            'scheduled_at' => now(),
        ]);

        app(NewsletterCampaignService::class)
            ->processCampaign($campaign, 100);

        Mail::assertSent(
            NewsletterCampaignMail::class,
            1
        );

        Mail::assertSent(
            NewsletterCampaignMail::class,
            fn ($mail): bool => $mail->hasTo('active-pl@example.com')
        );

        $campaign->refresh();

        $this->assertSame(
            NewsletterCampaignStatus::Sent,
            $campaign->status
        );
        $this->assertSame(1, $campaign->recipient_count);
        $this->assertSame(1, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertNotNull($activePl->fresh()->last_sent_at);
    }

    public function test_send_due_command_processes_scheduled_campaign(): void
    {
        $this->subscriber(
            'scheduled@example.com',
            'pl',
            NewsletterSubscriberStatus::Active
        );

        $campaign = NewsletterCampaign::create([
            'locale' => 'pl',
            'subject' => 'Zaplanowana kampania',
            'body_html' => '<p>Treść.</p>',
            'status' => NewsletterCampaignStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('newsletter:send-due --limit=100')
            ->assertSuccessful();

        $this->assertSame(
            NewsletterCampaignStatus::Sent,
            $campaign->fresh()->status
        );

        Mail::assertSent(NewsletterCampaignMail::class, 1);
    }

    private function subscriber(
        string $email,
        string $locale,
        NewsletterSubscriberStatus $status
    ): NewsletterSubscriber {
        return NewsletterSubscriber::create([
            'email' => $email,
            'locale' => $locale,
            'status' => $status,
            'source' => 'test',
            'confirmation_token' => $status === NewsletterSubscriberStatus::Pending
                ? 'confirm-' . uniqid()
                : null,
            'unsubscribe_token' => 'unsubscribe-' . uniqid(),
            'consent_requested_at' => now(),
            'confirmed_at' => $status === NewsletterSubscriberStatus::Active
                ? now()
                : null,
        ]);
    }
}
