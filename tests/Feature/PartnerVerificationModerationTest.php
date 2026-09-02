<?php

namespace Tests\Feature;

use App\Enums\PartnerLinkStatus;
use App\Mail\PartnerVerificationMail;
use App\Models\PartnerLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerVerificationModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_sends_verification_email_and_stores_token(): void
    {
        Mail::fake();
        Storage::fake('public');

        $this->post('/pl/partners', $this->submissionData())
            ->assertRedirect('/pl/partners')
            ->assertSessionHas('partner_verification_id');

        $partner = PartnerLink::query()->firstOrFail();

        $this->assertNotNull($partner->verification_token_hash);
        $this->assertNotNull($partner->verification_sent_at);
        $this->assertNull($partner->email_verified_at);
        $this->assertSame(PartnerLinkStatus::EmailPending, $partner->status);

        Mail::assertSent(
            PartnerVerificationMail::class,
            fn (PartnerVerificationMail $mail): bool =>
                $mail->partner->is($partner)
                && str_contains($mail->verificationUrl, '/pl/partners/verify/' . $partner->id . '/')
        );
    }

    public function test_valid_verification_link_moves_submission_to_pending(): void
    {
        $token = 'verification-token-for-test';
        $partner = $this->partner([
            'verification_token_hash' => hash('sha256', $token),
            'verification_sent_at' => now(),
        ]);

        $this->get('/pl/partners/verify/' . $partner->id . '/' . $token)
            ->assertRedirect('/pl/partners')
            ->assertSessionHas('status');

        $partner->refresh();

        $this->assertNotNull($partner->email_verified_at);
        $this->assertNull($partner->verification_token_hash);
        $this->assertSame(PartnerLinkStatus::Pending, $partner->status);
    }

    public function test_expired_verification_link_does_not_verify_submission(): void
    {
        $token = 'expired-token-for-test';
        $partner = $this->partner([
            'verification_token_hash' => hash('sha256', $token),
            'verification_sent_at' => now()->subHours(49),
        ]);

        $this->get('/pl/partners/verify/' . $partner->id . '/' . $token)
            ->assertRedirect('/pl/partners')
            ->assertSessionHas('status');

        $partner->refresh();

        $this->assertNull($partner->email_verified_at);
        $this->assertSame(PartnerLinkStatus::EmailPending, $partner->status);
    }

    public function test_submission_owner_can_resend_verification_email_from_session(): void
    {
        Mail::fake();
        $partner = $this->partner([
            'verification_token_hash' => hash('sha256', 'old-token'),
            'verification_sent_at' => now()->subHour(),
        ]);
        $oldHash = $partner->verification_token_hash;

        $this->withSession(['partner_verification_id' => $partner->id])
            ->post('/pl/partners/' . $partner->id . '/resend-verification')
            ->assertRedirect()
            ->assertSessionHas('status');

        $partner->refresh();

        $this->assertNotSame($oldHash, $partner->verification_token_hash);
        Mail::assertSent(PartnerVerificationMail::class);
    }

    public function test_admin_can_open_partner_list_but_editor_cannot(): void
    {
        $partner = $this->partner([
            'email_verified_at' => now(),
            'status' => PartnerLinkStatus::Pending,
        ]);

        $admin = $this->user(User::ROLE_ADMIN);
        $editor = $this->user(User::ROLE_EDITOR);

        $this->actingAs($admin)
            ->get('/admin/partners')
            ->assertOk()
            ->assertSee($partner->name)
            ->assertSee($partner->email)
            ->assertSee('Zgłoszone linki partnerskie');

        $this->actingAs($editor)
            ->get('/admin/partners')
            ->assertForbidden();
    }

    public function test_admin_cannot_approve_unverified_partner(): void
    {
        $partner = $this->partner();
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from('/admin/partners/' . $partner->id . '/edit')
            ->patch('/admin/partners/' . $partner->id . '/approve')
            ->assertRedirect('/admin/partners/' . $partner->id . '/edit')
            ->assertSessionHasErrors('partner');

        $this->assertSame(
            PartnerLinkStatus::EmailPending,
            $partner->fresh()->status
        );
    }

    public function test_admin_can_approve_and_revoke_verified_partner(): void
    {
        $partner = $this->partner([
            'email_verified_at' => now(),
            'status' => PartnerLinkStatus::Pending,
        ]);
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from('/admin/partners')
            ->patch('/admin/partners/' . $partner->id . '/approve')
            ->assertRedirect('/admin/partners');

        $partner->refresh();
        $this->assertSame(PartnerLinkStatus::Active, $partner->status);
        $this->assertNotNull($partner->approved_at);
        $this->assertSame($admin->id, $partner->approved_by);

        $this->actingAs($admin)
            ->from('/admin/partners/' . $partner->id . '/edit')
            ->patch('/admin/partners/' . $partner->id . '/revoke')
            ->assertRedirect('/admin/partners/' . $partner->id . '/edit');

        $partner->refresh();
        $this->assertSame(PartnerLinkStatus::Pending, $partner->status);
        $this->assertNull($partner->approved_at);
        $this->assertNull($partner->approved_by);
    }

    public function test_admin_can_ban_domain_and_delete_partner(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('partners/logos/test.webp', 'logo');

        $partner = $this->partner([
            'logo_path' => 'partners/logos/test.webp',
            'email_verified_at' => now(),
            'status' => PartnerLinkStatus::Pending,
        ]);
        $admin = $this->user(User::ROLE_SUPER_ADMIN);

        $this->actingAs($admin)
            ->from('/admin/partners/' . $partner->id . '/edit')
            ->patch('/admin/partners/' . $partner->id . '/ban', [
                'banned_reason' => 'Naruszenie zasad programu partnerskiego.',
            ])
            ->assertRedirect('/admin/partners/' . $partner->id . '/edit');

        $partner->refresh();
        $this->assertSame(PartnerLinkStatus::Banned, $partner->status);
        $this->assertSame($admin->id, $partner->banned_by);
        $this->assertSame(
            'Naruszenie zasad programu partnerskiego.',
            $partner->banned_reason
        );

        $this->actingAs($admin)
            ->delete('/admin/partners/' . $partner->id)
            ->assertRedirect('/admin/partners');

        $this->assertDatabaseMissing('partner_links', ['id' => $partner->id]);
        Storage::disk('public')->assertMissing('partners/logos/test.webp');
    }

    private function submissionData(): array
    {
        return [
            'name' => 'Stereo Partner',
            'website_url' => 'https://partner.example',
            'backlink_url' => 'https://partner.example/links',
            'description' => 'Serwis poświęcony fotografii stereoskopowej i obrazom 3D.',
            'logo' => UploadedFile::fake()->image('logo.png', 120, 60)->size(50),
            'email' => 'partner@example.org',
            'commercial' => '0',
            'contact_person' => 'Jan Testowy',
            'phone' => '+48 500 000 000',
            'backlink_commitment' => '1',
            'privacy_consent' => '1',
        ];
    }

    private function partner(array $overrides = []): PartnerLink
    {
        return PartnerLink::query()->create(array_merge([
            'source_locale' => 'pl',
            'name' => 'Stereo Partner',
            'website_url' => 'https://partner.example',
            'domain' => 'partner.example',
            'backlink_url' => 'https://partner.example/links',
            'description' => 'Serwis poświęcony fotografii stereoskopowej i obrazom 3D.',
            'logo_path' => 'partners/logos/partner.webp',
            'email' => 'partner@example.org',
            'commercial' => false,
            'contact_person' => 'Jan Testowy',
            'phone' => '+48 500 000 000',
            'status' => PartnerLinkStatus::EmailPending,
            'backlink_commitment_at' => now(),
            'privacy_accepted_at' => now(),
        ], $overrides));
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
