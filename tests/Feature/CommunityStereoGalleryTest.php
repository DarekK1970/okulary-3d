<?php

namespace Tests\Feature;

use App\Enums\GalleryStatus;
use App\Models\StereoGalleryItem;
use App\Models\StereoGalleryRating;
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

    public function test_authenticated_user_can_submit_single_stereo_pair_file(): void
    {
        $user = User::factory()->create();

        $sideBySide = $this->createStereoPairUpload(
            'stereo-pair.jpg',
            800,
            600
        );

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Most z pojedynczego pliku',
                'description' => 'Stereopara z jednego pliku.',
                'author_name' => 'Anna 3D',
                'license' => 'cc_by_sa',
                'submission_type' => 'stereo_pair',
                'source_image' => $sideBySide,
                'rights_confirmation' => '1',
            ])
            ->assertRedirect('/pl/account/gallery');

        $item = StereoGalleryItem::query()
            ->firstOrFail();

        $this->assertNotNull(
            $item->stereo_pair_path
        );

        Storage::disk('public')
            ->assertExists(
                $item->stereo_pair_path
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

    public function test_uploaded_stereo_pair_is_optimized_to_maximum_frame_size(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Duża stereopara',
                'description' => 'Stereopara większa niż limit.',
                'author_name' => 'Anna 3D',
                'license' => 'cc_by',
                'submission_type' => 'stereo_pair',
                'source_image' => $this->createStereoPairUpload(
                    'large-stereo-pair.jpg',
                    5000,
                    1800
                ),
                'rights_confirmation' => '1',
            ])
            ->assertRedirect('/pl/account/gallery');

        $item = StereoGalleryItem::query()
            ->firstOrFail();

        $this->assertSame(1920, $item->left_width);
        $this->assertSame(1382, $item->left_height);
        $this->assertSame(1920, $item->right_width);
        $this->assertSame(1382, $item->right_height);
        $this->assertStoredImageSize(
            $item->stereo_pair_path,
            3840,
            1382
        );
    }

    public function test_uploaded_left_and_right_images_are_optimized_to_maximum_frame_size(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Duże osobne klatki',
                'description' => 'Lewy i prawy obraz większy niż limit.',
                'author_name' => 'Jan Stereo',
                'license' => 'cc_by',
                'submission_type' => 'left_right',
                'left_image' => UploadedFile::fake()
                    ->image('left.jpg', 2600, 1600),
                'right_image' => UploadedFile::fake()
                    ->image('right.jpg', 2600, 1600),
                'rights_confirmation' => '1',
            ])
            ->assertRedirect('/pl/account/gallery');

        $item = StereoGalleryItem::query()
            ->firstOrFail();

        $this->assertSame(1920, $item->left_width);
        $this->assertSame(1182, $item->left_height);
        $this->assertSame(1920, $item->right_width);
        $this->assertSame(1182, $item->right_height);
        $this->assertStoredImageSize(
            $item->left_image_path,
            1920,
            1182
        );
        $this->assertStoredImageSize(
            $item->stereo_pair_path,
            3840,
            1182
        );
    }

    public function test_too_small_stereo_pair_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Za mała stereopara',
                'description' => 'Obraz poniżej minimalnego limitu.',
                'author_name' => 'Anna 3D',
                'license' => 'cc_by',
                'submission_type' => 'stereo_pair',
                'source_image' => $this->createStereoPairUpload(
                    'small-stereo-pair.jpg',
                    500,
                    600
                ),
                'rights_confirmation' => '1',
            ])
            ->assertSessionHasErrors([
                'source_image' => 'Zbyt słaba rozdzielczość obrazka.',
            ]);

        $this->assertDatabaseCount(
            'stereo_gallery_items',
            0
        );
    }

    public function test_too_small_left_right_image_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Za mała klatka',
                'description' => 'Jeden wymiar obrazu jest zbyt mały.',
                'author_name' => 'Jan Stereo',
                'license' => 'cc_by',
                'submission_type' => 'left_right',
                'left_image' => UploadedFile::fake()
                    ->image('left.jpg', 259, 600),
                'right_image' => UploadedFile::fake()
                    ->image('right.jpg', 600, 600),
                'rights_confirmation' => '1',
            ])
            ->assertSessionHasErrors([
                'left_image' => 'Zbyt słaba rozdzielczość obrazka.',
            ]);

        $this->assertDatabaseCount(
            'stereo_gallery_items',
            0
        );
    }

    public function test_stereo_pair_with_embedded_jpeg_metadata_is_not_treated_as_mpo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Stereopara z miniaturą EXIF',
                'description' => 'Poprawny JPG z dodatkowymi markerami JPEG w metadanych.',
                'author_name' => 'Anna 3D',
                'license' => 'cc_by',
                'submission_type' => 'stereo_pair',
                'source_image' => $this->createStereoPairWithEmbeddedJpegMarkers(
                    'stereo-pair-with-exif.jpg',
                    800,
                    600
                ),
                'rights_confirmation' => '1',
            ])
            ->assertRedirect('/pl/account/gallery');

        $this->assertDatabaseHas(
            'stereo_gallery_items',
            [
                'title' => 'Stereopara z miniaturą EXIF',
                'status' => GalleryStatus::Pending->value,
                'user_id' => $user->id,
            ]
        );
    }

    public function test_authenticated_user_can_submit_mpo_file_for_moderation(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'MPO do moderacji',
                'description' => 'Para stereo w pliku MPO.',
                'author_name' => 'Anna MPO',
                'license' => 'cc_by',
                'submission_type' => 'mpo',
                'source_image' => $this->createMpoUpload(
                    'stereo.mpo',
                    800,
                    600
                ),
                'rights_confirmation' => '1',
            ])
            ->assertRedirect('/pl/account/gallery');

        $this->assertDatabaseHas(
            'stereo_gallery_items',
            [
                'title' => 'MPO do moderacji',
                'status' => GalleryStatus::Pending->value,
                'user_id' => $user->id,
            ]
        );

        $this->actingAs($admin)
            ->get('/admin/gallery')
            ->assertOk()
            ->assertSee('MPO do moderacji')
            ->assertSee('Oczekuje na moderację');
    }

    public function test_corrupt_jpeg_upload_is_rejected_without_crashing(): void
    {
        $user = User::factory()->create();

        $temp = tempnam(
            sys_get_temp_dir(),
            'broken-jpeg-'
        );

        file_put_contents(
            $temp,
            'not-a-real-jpeg-data'
        );

        $upload = new UploadedFile(
            $temp,
            'broken.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->actingAs($user)
            ->post('/pl/gallery', [
                'title' => 'Uszkodzony plik',
                'description' => 'Próba wysłania uszkodzonego JPG.',
                'author_name' => 'Jan Test',
                'license' => 'cc_by',
                'submission_type' => 'stereo_pair',
                'source_image' => $upload,
                'rights_confirmation' => '1',
            ])
            ->assertSessionHasErrors('source_image');
    }

    public function test_pending_submission_is_not_public(): void
    {
        $item = $this->galleryItem(
            GalleryStatus::Pending
        );

        $this->get('/pl/gallery')
            ->assertDontSee($item->title);

        $this->get(
            '/pl/gallery/'.$item->slug
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
                '/admin/gallery/'.$item->slug,
                [
                    'status' => GalleryStatus::Published->value,
                    'moderation_note' => 'Materiał zaakceptowany.',
                ]
            )
            ->assertRedirect(
                '/admin/gallery/'.$item->slug
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
            '/pl/gallery/'.$item->slug
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

    public function test_editor_can_bulk_publish_selected_pending_submissions(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $firstPending = $this->galleryItem(
            GalleryStatus::Pending
        );
        $secondPending = $this->galleryItem(
            GalleryStatus::Pending
        );
        $rejected = $this->galleryItem(
            GalleryStatus::Rejected
        );

        $this->actingAs($editor)
            ->get('/admin/gallery')
            ->assertOk()
            ->assertSee('ZATWIERDŹ ZAZNACZONE')
            ->assertSee('name="gallery_items[]"', false)
            ->assertSee('value="'.$firstPending->id.'"', false)
            ->assertSee('value="'.$secondPending->id.'"', false)
            ->assertDontSee('value="'.$rejected->id.'"', false);

        $this->actingAs($editor)
            ->post('/admin/gallery/bulk-publish', [
                'gallery_items' => [
                    $firstPending->id,
                    $secondPending->id,
                    $rejected->id,
                ],
            ])
            ->assertRedirect();

        $firstPending->refresh();
        $secondPending->refresh();
        $rejected->refresh();

        $this->assertSame(
            GalleryStatus::Published,
            $firstPending->status
        );
        $this->assertNotNull($firstPending->published_at);
        $this->assertSame($editor->id, $firstPending->moderated_by);

        $this->assertSame(
            GalleryStatus::Published,
            $secondPending->status
        );
        $this->assertNotNull($secondPending->published_at);
        $this->assertSame($editor->id, $secondPending->moderated_by);

        $this->assertSame(
            GalleryStatus::Rejected,
            $rejected->status
        );
        $this->assertNull($rejected->published_at);
    }

    public function test_authenticated_user_can_rate_published_gallery_item_only_once(): void
    {
        $user = User::factory()->create();
        $item = $this->galleryItem(
            GalleryStatus::Published
        );

        $this->actingAs($user)
            ->postJson(
                '/pl/gallery/'.$item->slug.'/ratings',
                ['rating' => 5]
            )
            ->assertOk()
            ->assertJson([
                'count' => 1,
                'average' => 5,
                'summary' => '1 ★ 5.0',
            ]);

        $this->assertDatabaseHas(
            'stereo_gallery_ratings',
            [
                'stereo_gallery_item_id' => $item->id,
                'user_id' => $user->id,
                'rating' => 5,
            ]
        );

        $this->actingAs($user)
            ->postJson(
                '/pl/gallery/'.$item->slug.'/ratings',
                ['rating' => 4]
            )
            ->assertConflict();

        $this->assertDatabaseCount(
            'stereo_gallery_ratings',
            1
        );
    }

    public function test_gallery_browser_shows_ratings_navigation_thumbnails_and_author_filter(): void
    {
        $viewer = User::factory()->create();
        $first = $this->galleryItem(
            GalleryStatus::Published,
            null,
            'Autor Jeden',
            'Pierwsza praca'
        );
        $second = $this->galleryItem(
            GalleryStatus::Published,
            null,
            'Autor Dwa',
            'Druga praca'
        );
        $third = $this->galleryItem(
            GalleryStatus::Published,
            null,
            'Autor Jeden',
            'Trzecia praca'
        );

        $first->update(['published_at' => now()->subMinutes(3)]);
        $second->update(['published_at' => now()->subMinutes(2)]);
        $third->update(['published_at' => now()->subMinute()]);

        StereoGalleryRating::create([
            'stereo_gallery_item_id' => $second->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
        ]);
        StereoGalleryRating::create([
            'stereo_gallery_item_id' => $second->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 4,
        ]);

        $this->actingAs($viewer)
            ->get('/pl/gallery/'.$second->slug)
            ->assertOk()
            ->assertSee('data-community-viewer', false)
            ->assertSee('data-gallery-current-title', false)
            ->assertSee('data-gallery-current-description', false)
            ->assertSee('data-gallery-current-license', false)
            ->assertSee('data-gallery-browser', false)
            ->assertSee('data-gallery-browser-item', false)
            ->assertSee('data-gallery-nav="previous"', false)
            ->assertSee('data-gallery-nav="next"', false)
            ->assertSee('community-gallery-nav-prev', false)
            ->assertSee('community-gallery-nav-next', false)
            ->assertSee('community-gallery-strip', false)
            ->assertSee('4.5')
            ->assertSee('Oceń obraz:', false)
            ->assertSee('Druga praca')
            ->assertSee('Opis pracy.')
            ->assertSee('Autor Dwa')
            ->assertSee('Wszelkie prawa zastrzeżone')
            ->assertSee('Wyświetl wszystkie prace tego autora.');

        $this->get('/pl/gallery?author=Autor%20Jeden')
            ->assertOk()
            ->assertSee('Pierwsza praca')
            ->assertSee('Trzecia praca')
            ->assertSee('/pl/gallery?author=Autor%20Jeden&amp;photo=', false)
            ->assertDontSee('Druga praca');
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
                .$item->slug
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
                .$item->slug
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

    private function createStereoPairUpload(
        string $filename,
        int $width,
        int $height
    ): UploadedFile {
        $source = tempnam(
            sys_get_temp_dir(),
            'gallery-pair-'
        );

        $left = imagecreatetruecolor(
            $width / 2,
            $height
        );
        $right = imagecreatetruecolor(
            $width / 2,
            $height
        );

        imagefilledrectangle(
            $left,
            0,
            0,
            $width / 2,
            $height,
            imagecolorallocate($left, 0, 0, 255)
        );

        imagefilledrectangle(
            $right,
            0,
            0,
            $width / 2,
            $height,
            imagecolorallocate($right, 255, 0, 0)
        );

        $combined = imagecreatetruecolor(
            $width,
            $height
        );
        imagecopy($combined, $left, 0, 0, 0, 0, $width / 2, $height);
        imagecopy($combined, $right, $width / 2, 0, 0, 0, $width / 2, $height);
        imagejpeg($combined, $source);

        return new UploadedFile(
            $source,
            $filename,
            'image/jpeg',
            null,
            true
        );
    }

    private function createMpoUpload(
        string $filename,
        int $width,
        int $height
    ): UploadedFile {
        $source = tempnam(
            sys_get_temp_dir(),
            'gallery-mpo-'
        );

        $left = imagecreatetruecolor(
            $width,
            $height
        );
        $right = imagecreatetruecolor(
            $width,
            $height
        );

        imagefilledrectangle(
            $left,
            0,
            0,
            $width,
            $height,
            imagecolorallocate($left, 0, 0, 255)
        );

        imagefilledrectangle(
            $right,
            0,
            0,
            $width,
            $height,
            imagecolorallocate($right, 255, 0, 0)
        );

        ob_start();
        imagejpeg($left);
        $leftJpeg = ob_get_clean();

        ob_start();
        imagejpeg($right);
        $rightJpeg = ob_get_clean();

        file_put_contents(
            $source,
            $leftJpeg.$rightJpeg
        );

        imagedestroy($left);
        imagedestroy($right);

        return new UploadedFile(
            $source,
            $filename,
            'image/jpeg',
            null,
            true
        );
    }

    private function createStereoPairWithEmbeddedJpegMarkers(
        string $filename,
        int $width,
        int $height
    ): UploadedFile {
        $baseUpload = $this->createStereoPairUpload(
            $filename,
            $width,
            $height
        );
        $source = tempnam(
            sys_get_temp_dir(),
            'gallery-pair-exif-'
        );
        $jpeg = file_get_contents(
            $baseUpload->getRealPath()
        );
        $payload = 'Exif'.chr(0).chr(0)
            .chr(255).chr(216)
            .'embedded-thumbnail-marker'
            .chr(255).chr(217)
            .chr(255).chr(216)
            .'second-embedded-thumbnail-marker'
            .chr(255).chr(217);
        $segment = chr(255).chr(225)
            .pack('n', strlen($payload) + 2)
            .$payload;

        file_put_contents(
            $source,
            substr($jpeg, 0, 2)
            .$segment
            .substr($jpeg, 2)
        );

        return new UploadedFile(
            $source,
            $filename,
            'image/jpeg',
            null,
            true
        );
    }

    private function assertStoredImageSize(
        string $path,
        int $width,
        int $height
    ): void {
        $size = getimagesize(
            Storage::disk('public')->path($path)
        );

        $this->assertSame($width, $size[0]);
        $this->assertSame($height, $size[1]);
    }

    private function galleryItem(
        GalleryStatus $status,
        ?User $user = null,
        string $authorName = 'Autor Testowy',
        string $title = 'Testowa praca stereo'
    ): StereoGalleryItem {
        $user ??= User::factory()->create();

        return StereoGalleryItem::create([
            'user_id' => $user->id,
            'slug' => 'test-stereo-'.uniqid(),
            'title' => $title,
            'description' => 'Opis pracy.',
            'author_name' => $authorName,
            'license' => 'all_rights_reserved',
            'status' => $status,
            'left_image_path' => 'gallery/test/left.jpg',
            'right_image_path' => 'gallery/test/right.jpg',
            'left_width' => 800,
            'left_height' => 600,
            'right_width' => 800,
            'right_height' => 600,
            'rights_confirmed_at' => now(),
            'published_at' => $status === GalleryStatus::Published
                    ? now()
                    : null,
        ]);
    }
}
