<?php

namespace Tests\Feature;

use App\Enums\GalleryStatus;
use App\Models\StereoGalleryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityStereoGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_public_gallery_page_is_available(): void
    {
        $this->get('/pl/gallery')
            ->assertOk()
            ->assertSee('Galeria stereoskopowa');
    }

    public function test_guest_cannot_open_submission_form(): void
    {
        $this->get('/pl/gallery/submit')
            ->assertRedirect();
    }

    public function test_authenticated_user_can_submit_stereo_pair(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Most w stereo',
                'description' => 'Testowa para stereoskopowa.',
                'author_name' => 'Jan Stereo',
                'license' => 'cc_by',
                'left_image' => UploadedFile::fake()
                    ->image('left.jpg', 800, 600),
                'right_image' => UploadedFile::fake()
                    ->image('right.jpg', 800, 600),
                'rights_confirmation' => '1',
            ])
            ->assertRedirect('/pl/account/gallery');

        $item = StereoGalleryItem::query()
            ->firstOrFail();

        $this->assertSame(
            GalleryStatus::Pending,
            $item->status
        );

        $this->assertSame(
            'Jan Stereo',
            $item->author_name
        );

        Storage::disk('public')
            ->assertExists(
                $item->left_image_path
            );

        Storage::disk('public')
            ->assertExists(
                $item->right_image_path
            );
    }

    public function test_pending_submission_is_not_public(): void
    {
        $item = $this->galleryItem(
            GalleryStatus::Pending
        );

        $this->get('/pl/gallery')
            ->assertDontSee($item->title);

        $this->get(
            '/pl/gallery/' . $item->slug
        )->assertNotFound();
    }

    public function test_editor_can_publish_submission(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $item = $this->galleryItem(
            GalleryStatus::Pending
        );

        $this->actingAs($editor)
            ->patch(
                '/admin/gallery/' . $item->slug,
                [
                    'status' =>
                        GalleryStatus::Published->value,
                    'moderation_note' =>
                        'Materiał zaakceptowany.',
                ]
            )
            ->assertRedirect(
                '/admin/gallery/' . $item->slug
            );

        $item->refresh();

        $this->assertSame(
            GalleryStatus::Published,
            $item->status
        );

        $this->assertNotNull(
            $item->published_at
        );

        $this->assertSame(
            $editor->id,
            $item->moderated_by
        );

        $this->get(
            '/pl/gallery/' . $item->slug
        )
            ->assertOk()
            ->assertSee($item->title)
            ->assertSee(
                'data-community-viewer',
                false
            )
            ->assertSee(
                'Anaglyph czerwono-cyjanowy'
            )
            ->assertSee('Wiggle');
    }

    public function test_user_can_delete_own_unpublished_submission(): void
    {
        $user = User::factory()->create();

        $item = $this->galleryItem(
            GalleryStatus::Rejected,
            $user
        );

        Storage::disk('public')
            ->put(
                $item->left_image_path,
                'left'
            );

        Storage::disk('public')
            ->put(
                $item->right_image_path,
                'right'
            );

        $this->actingAs($user)
            ->delete(
                '/pl/account/gallery/'
                . $item->slug
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'stereo_gallery_items',
            ['id' => $item->id]
        );

        Storage::disk('public')
            ->assertMissing(
                $item->left_image_path
            );

        Storage::disk('public')
            ->assertMissing(
                $item->right_image_path
            );
    }

    public function test_user_cannot_delete_published_submission(): void
    {
        $user = User::factory()->create();

        $item = $this->galleryItem(
            GalleryStatus::Published,
            $user
        );

        $this->actingAs($user)
            ->delete(
                '/pl/account/gallery/'
                . $item->slug
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'stereo_gallery_items',
            ['id' => $item->id]
        );
    }

    public function test_homepage_and_header_link_to_real_gallery(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee('/pl/gallery', false)
            ->assertSee('Otwórz galerię');
    }

    private function galleryItem(
        GalleryStatus $status,
        ?User $user = null
    ): StereoGalleryItem {
        $user ??= User::factory()->create();

        return StereoGalleryItem::create([
            'user_id' => $user->id,
            'slug' => 'test-stereo-' . uniqid(),
            'title' => 'Testowa praca stereo',
            'description' => 'Opis pracy.',
            'author_name' => 'Autor Testowy',
            'license' => 'all_rights_reserved',
            'status' => $status,
            'left_image_path' =>
                'gallery/test/left.jpg',
            'right_image_path' =>
                'gallery/test/right.jpg',
            'left_width' => 800,
            'left_height' => 600,
            'right_width' => 800,
            'right_height' => 600,
            'rights_confirmed_at' => now(),
            'published_at' =>
                $status === GalleryStatus::Published
                    ? now()
                    : null,
        ]);
    }
}
