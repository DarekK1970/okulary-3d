<?php

namespace Tests\Feature;

use App\Enums\ArchiveTranslationStatus;
use App\Models\ArchiveItem;
use App\Models\ArchiveItemTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StereoscopicArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_public_archive_is_available(): void
    {
        $this->get('/pl/archive')
            ->assertOk()
            ->assertSee('Archiwum stereoskopii');
    }

    public function test_regular_user_cannot_manage_archive(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get('/admin/archive')
            ->assertForbidden();
    }

    public function test_editor_can_create_and_publish_archive_item(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $response = $this->actingAs($editor)
            ->post('/admin/archive', [
                'source_locale' => 'pl',
                'technique' => 'stereocard',
                'year_from' => 1903,
                'circa' => '1',
                'creator' => 'John Doe',
                'publisher' => 'Stereo Company',
                'country' => 'USA',
                'collection_name' => 'Test Collection',
                'source_name' => 'Public Library',
                'source_url' => 'https://example.com/archive/1',
                'rights_status' => 'public_domain',
                'rights_note' => 'Public domain scan.',
                'is_published' => '1',
                'original_image' =>
                    UploadedFile::fake()
                        ->image(
                            'stereocard.jpg',
                            1200,
                            700
                        ),
                'translations' => [
                    'pl' => [
                        'title' =>
                            'Most Brookliński 1903',
                        'description' =>
                            'Historyczna karta stereo.',
                        'historical_note' =>
                            'Rozszerzony kontekst historyczny.',
                    ],
                    'en' => [
                        'title' =>
                            'Brooklyn Bridge 1903',
                        'description' =>
                            'Historic stereo card.',
                        'translation_status' =>
                            ArchiveTranslationStatus::Ready->value,
                    ],
                ],
            ]);

        $item = ArchiveItem::query()
            ->with('translations')
            ->firstOrFail();

        $response->assertRedirect(
            '/admin/archive/'
            . $item->id
            . '/edit'
        );

        $this->assertTrue(
            $item->is_published
        );

        $this->assertNotNull(
            $item->published_at
        );

        $pl = $item->translation('pl');
        $en = $item->translation('en');

        $this->assertSame(
            ArchiveTranslationStatus::Source,
            $pl->translation_status
        );

        $this->assertSame(
            ArchiveTranslationStatus::Ready,
            $en->translation_status
        );

        Storage::disk('public')
            ->assertExists(
                $item->original_image_path
            );

        $this->get(
            '/pl/archive/' . $pl->slug
        )
            ->assertOk()
            ->assertSee('Most Brookliński 1903')
            ->assertSee('Public Library');

        $this->get(
            '/en/archive/' . $en->slug
        )
            ->assertOk()
            ->assertSee('Brooklyn Bridge 1903');
    }

    public function test_draft_translation_is_not_public(): void
    {
        [$item, $pl, $en] =
            $this->archiveItem(
                enStatus:
                    ArchiveTranslationStatus::Draft
            );

        $this->get(
            '/pl/archive/' . $pl->slug
        )->assertOk();

        $this->get(
            '/en/archive/' . $en->slug
        )->assertNotFound();
    }

    public function test_stereo_pair_enables_historical_viewer_modes(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->post('/admin/archive', [
                'source_locale' => 'pl',
                'technique' => 'stereocard',
                'year_from' => 1898,
                'source_name' => 'Test Museum',
                'rights_status' => 'public_domain',
                'is_published' => '1',
                'original_image' =>
                    UploadedFile::fake()
                        ->image(
                            'card.jpg',
                            1200,
                            700
                        ),
                'left_image' =>
                    UploadedFile::fake()
                        ->image(
                            'left.jpg',
                            800,
                            600
                        ),
                'right_image' =>
                    UploadedFile::fake()
                        ->image(
                            'right.jpg',
                            800,
                            600
                        ),
                'translations' => [
                    'pl' => [
                        'title' =>
                            'Para testowa',
                    ],
                ],
            ])
            ->assertRedirect();

        $translation =
            ArchiveItemTranslation::query()
                ->where('locale', 'pl')
                ->firstOrFail();

        $this->get(
            '/pl/archive/'
            . $translation->slug
        )
            ->assertOk()
            ->assertSee(
                'data-archive-viewer',
                false
            )
            ->assertSee('Parallel')
            ->assertSee('Cross-eye')
            ->assertSee(
                'Anaglif czerwono-cyjanowy'
            )
            ->assertSee('Wiggle');
    }

    public function test_archive_filters_by_period_and_technique(): void
    {
        [$oldItem] =
            $this->archiveItem(
                year: 1885,
                technique: 'stereocard',
                slugSuffix: 'old'
            );

        [$newItem] =
            $this->archiveItem(
                year: 1955,
                technique: 'viewmaster',
                slugSuffix: 'new'
            );

        $this->get(
            '/pl/archive'
            . '?technique=stereocard'
            . '&year_to=1900'
        )
            ->assertOk()
            ->assertSee(
                $oldItem
                    ->translation('pl')
                    ->title
            )
            ->assertDontSee(
                $newItem
                    ->translation('pl')
                    ->title
            );
    }

    public function test_homepage_and_history_navigation_link_to_archive(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee(
                '/pl/archive',
                false
            );
    }

    /**
     * @return array{
     *     0: ArchiveItem,
     *     1: ArchiveItemTranslation,
     *     2: ArchiveItemTranslation
     * }
     */
    private function archiveItem(
        ArchiveTranslationStatus $enStatus =
            ArchiveTranslationStatus::Ready,
        int $year = 1900,
        string $technique = 'stereocard',
        string $slugSuffix = 'one'
    ): array {
        $user = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $item = ArchiveItem::create([
            'source_locale' => 'pl',
            'technique' => $technique,
            'year_from' => $year,
            'circa' => false,
            'source_name' => 'Test Source',
            'rights_status' => 'public_domain',
            'original_image_path' =>
                'archive/test/original.jpg',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Storage::disk('public')
            ->put(
                $item->original_image_path,
                'image'
            );

        $pl =
            $item->translations()->create([
                'locale' => 'pl',
                'title' =>
                    'Obiekt '
                    . $year
                    . ' '
                    . $slugSuffix,
                'slug' =>
                    'obiekt-'
                    . $year
                    . '-'
                    . $slugSuffix,
                'description' =>
                    'Opis archiwalny.',
                'translation_status' =>
                    ArchiveTranslationStatus::Source,
            ]);

        $en =
            $item->translations()->create([
                'locale' => 'en',
                'title' =>
                    'Object '
                    . $year
                    . ' '
                    . $slugSuffix,
                'slug' =>
                    'object-'
                    . $year
                    . '-'
                    . $slugSuffix,
                'description' =>
                    'Archive description.',
                'translation_status' =>
                    $enStatus,
            ]);

        $item->load('translations');

        return [
            $item,
            $pl,
            $en,
        ];
    }
}
