<?php

namespace Tests\Feature;

use App\Enums\PartnerLinkStatus;
use App\Models\PartnerLink;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_program_page_is_seeded_and_public(): void
    {
        $this->assertDatabaseHas('static_pages', [
            'key' => 'partner-program',
            'is_active' => true,
        ]);

        $page = StaticPage::query()
            ->where('key', 'partner-program')
            ->firstOrFail();

        $this->assertNotNull($page->translation('pl'));
        $this->assertNotNull($page->translation('en'));

        $this->get('/pl/partners')
            ->assertOk()
            ->assertSee('Działajmy razem')
            ->assertSee('Zgłoś swoją stronę')
            ->assertSee('name="website_url"', false)
            ->assertSee('name="backlink_url"', false)
            ->assertSee('name="logo"', false)
            ->assertSee('/pl/partners', false);
    }

    public function test_main_navigation_contains_partner_program_link(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee('Działajmy razem')
            ->assertSee('/pl/partners', false);
    }

    public function test_guest_can_submit_partner_application_and_logo_is_resized(): void
    {
        Storage::fake('public');

        $response = $this->post('/pl/partners', [
            'name' => 'Stereo Studio Toruń',
            'website_url' => 'https://www.stereo-example.pl/',
            'backlink_url' => 'https://stereo-example.pl/partnerzy/',
            'description' => 'Fotografia stereoskopowa, skanowanie 3D i realizacja materiałów edukacyjnych.',
            'logo' => UploadedFile::fake()
                ->image('logo.png', 600, 300)
                ->size(80),
            'email' => 'PARTNER@example.pl',
            'commercial' => '1',
            'contact_person' => 'Jan Kowalski',
            'phone' => '+48 500 600 700',
            'backlink_commitment' => '1',
            'privacy_consent' => '1',
        ]);

        $response
            ->assertRedirect('/pl/partners')
            ->assertSessionHas('status');

        $partner = PartnerLink::query()->firstOrFail();

        $this->assertSame('Stereo Studio Toruń', $partner->name);
        $this->assertSame('https://www.stereo-example.pl', $partner->website_url);
        $this->assertSame('stereo-example.pl', $partner->domain);
        $this->assertSame('https://stereo-example.pl/partnerzy', $partner->backlink_url);
        $this->assertSame('partner@example.pl', $partner->email);
        $this->assertTrue($partner->commercial);
        $this->assertSame(PartnerLinkStatus::EmailPending, $partner->status);
        $this->assertNotNull($partner->backlink_commitment_at);
        $this->assertNotNull($partner->privacy_accepted_at);
        $this->assertNull($partner->email_verified_at);

        Storage::disk('public')->assertExists($partner->logo_path);

        $image = imagecreatefromstring(
            Storage::disk('public')->get($partner->logo_path)
        );

        $this->assertNotFalse($image);
        $this->assertLessThanOrEqual(120, imagesx($image));
        $this->assertSame(60, imagesy($image));

        imagedestroy($image);
    }

    public function test_submission_requires_backlink_and_privacy_commitments(): void
    {
        Storage::fake('public');

        $this->from('/pl/partners')
            ->post('/pl/partners', [
                'name' => 'Stereo Test',
                'website_url' => 'https://example.org',
                'description' => 'Opis przykładowej strony związanej ze stereoskopią.',
                'logo' => UploadedFile::fake()->image('logo.jpg', 120, 60),
                'email' => 'test@example.org',
                'commercial' => '0',
            ])
            ->assertRedirect('/pl/partners')
            ->assertSessionHasErrors([
                'backlink_commitment',
                'privacy_consent',
            ]);

        $this->assertDatabaseCount('partner_links', 0);
    }

    public function test_name_and_logo_limits_are_enforced(): void
    {
        Storage::fake('public');

        $this->from('/pl/partners')
            ->post('/pl/partners', [
                'name' => str_repeat('A', 61),
                'website_url' => 'https://example.org',
                'description' => 'Opis przykładowej strony związanej ze stereoskopią.',
                'logo' => UploadedFile::fake()
                    ->image('logo.jpg', 120, 60)
                    ->size(101),
                'email' => 'test@example.org',
                'commercial' => '0',
                'backlink_commitment' => '1',
                'privacy_consent' => '1',
            ])
            ->assertRedirect('/pl/partners')
            ->assertSessionHasErrors(['name', 'logo']);

        $this->assertDatabaseCount('partner_links', 0);
    }

    public function test_banned_domain_cannot_be_submitted_again(): void
    {
        Storage::fake('public');

        PartnerLink::query()->create([
            'source_locale' => 'pl',
            'name' => 'Zablokowany partner',
            'website_url' => 'https://blocked.example',
            'domain' => 'blocked.example',
            'backlink_url' => 'https://blocked.example',
            'description' => 'Zablokowany wpis testowy.',
            'logo_path' => 'partners/logos/blocked.png',
            'email' => 'blocked@example.org',
            'commercial' => true,
            'status' => PartnerLinkStatus::Banned,
        ]);

        $this->from('/pl/partners')
            ->post('/pl/partners', [
                'name' => 'Ponowne zgłoszenie',
                'website_url' => 'https://www.blocked.example/',
                'description' => 'Próba ponownego zgłoszenia zablokowanej domeny.',
                'logo' => UploadedFile::fake()->image('logo.jpg', 120, 60),
                'email' => 'other@example.org',
                'commercial' => '1',
                'backlink_commitment' => '1',
                'privacy_consent' => '1',
            ])
            ->assertRedirect('/pl/partners')
            ->assertSessionHasErrors('website_url');

        $this->assertDatabaseCount('partner_links', 1);
    }
}
