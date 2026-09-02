<?php

namespace Tests\Feature;

use App\Enums\PartnerLinkStatus;
use App\Models\PartnerLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerPublicCarouselTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_does_not_render_partner_module_without_active_partners(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertDontSee('data-partner-showcase', false);
    }

    public function test_only_verified_and_approved_active_partners_are_rendered(): void
    {
        $active = $this->partner([
            'name' => 'Aktywny Partner 3D',
            'status' => PartnerLinkStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $this->partner([
            'name' => 'Partner oczekujący',
            'status' => PartnerLinkStatus::Pending,
            'email_verified_at' => now(),
        ]);

        $this->partner([
            'name' => 'Partner zawieszony',
            'status' => PartnerLinkStatus::SuspendedBacklink,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $this->get('/pl')
            ->assertOk()
            ->assertSee('data-partner-showcase', false)
            ->assertSee($active->name)
            ->assertDontSee('Partner oczekujący')
            ->assertDontSee('Partner zawieszony')
            ->assertSee('/pl/partners/' . $active->id . '/go', false);
    }

    public function test_partner_redirect_increments_click_counter(): void
    {
        $partner = $this->partner([
            'website_url' => 'https://stereo-partner.example/oferta',
            'status' => PartnerLinkStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
            'click_count' => 4,
        ]);

        $this->get('/pl/partners/' . $partner->id . '/go')
            ->assertRedirect('https://stereo-partner.example/oferta');

        $this->assertSame(5, $partner->fresh()->click_count);
    }

    public function test_suspended_partner_redirect_is_not_public(): void
    {
        $partner = $this->partner([
            'status' => PartnerLinkStatus::SuspendedBacklink,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $this->get('/pl/partners/' . $partner->id . '/go')
            ->assertNotFound();

        $this->assertSame(0, $partner->fresh()->click_count);
    }

    private function partner(array $overrides = []): PartnerLink
    {
        static $counter = 0;
        $counter++;

        return PartnerLink::query()->create(array_merge([
            'source_locale' => 'pl',
            'name' => 'Partner ' . $counter,
            'website_url' => 'https://partner-' . $counter . '.example',
            'domain' => 'partner-' . $counter . '.example',
            'backlink_url' => 'https://partner-' . $counter . '.example/links',
            'description' => 'Serwis związany ze stereoskopią i obrazem 3D.',
            'logo_path' => 'partners/logos/partner-' . $counter . '.webp',
            'email' => 'partner-' . $counter . '@example.org',
            'commercial' => false,
            'status' => PartnerLinkStatus::EmailPending,
            'backlink_commitment_at' => now(),
            'privacy_accepted_at' => now(),
            'click_count' => 0,
        ], $overrides));
    }
}
