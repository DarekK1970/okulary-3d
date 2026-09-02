<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partner_links')) {
            Schema::create('partner_links', function (Blueprint $table): void {
                $table->id();
                $table->string('source_locale', 10)->default('pl');
                $table->string('name', 60);
                $table->text('website_url');
                $table->string('domain', 253)->index();
                $table->text('backlink_url')->nullable();
                $table->string('description', 300);
                $table->string('logo_path');
                $table->string('email')->index();
                $table->boolean('commercial')->default(false);
                $table->string('contact_person', 120)->nullable();
                $table->string('phone', 60)->nullable();
                $table->string('status', 40)->default('email_pending')->index();

                $table->timestamp('backlink_commitment_at')->nullable();
                $table->timestamp('privacy_accepted_at')->nullable();

                $table->string('verification_token_hash', 64)->nullable()->unique();
                $table->timestamp('verification_sent_at')->nullable();
                $table->timestamp('email_verified_at')->nullable()->index();

                $table->timestamp('approved_at')->nullable()->index();
                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('banned_at')->nullable();
                $table->foreignId('banned_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->text('banned_reason')->nullable();

                $table->timestamp('last_checked_at')->nullable()->index();
                $table->unsignedSmallInteger('last_http_status')->nullable();
                $table->timestamp('last_backlink_found_at')->nullable();
                $table->unsignedTinyInteger('consecutive_failures')->default(0);
                $table->text('last_check_error')->nullable();
                $table->unsignedBigInteger('click_count')->default(0);
                $table->timestamps();
            });
        }

        $this->seedPartnerProgramPage();
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_links');

        // The CMS page is intentionally kept on rollback so later editorial
        // changes are never removed together with a database rollback.
    }

    private function seedPartnerProgramPage(): void
    {
        if (
            ! Schema::hasTable('static_pages')
            || ! Schema::hasTable('static_page_translations')
        ) {
            return;
        }

        $existing = DB::table('static_pages')
            ->where('key', 'partner-program')
            ->first();

        if (! $existing) {
            $pageId = DB::table('static_pages')->insertGetId([
                'key' => 'partner-program',
                'group' => 'content',
                'source_locale' => 'pl',
                'is_active' => true,
                'sort_order' => 70,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $pageId = $existing->id;
        }

        $translations = [
            'pl' => [
                'title' => 'Działajmy razem',
                'body_html' => '<p>Jeżeli interesujesz się stereofotografią, drukiem soczewkowym, jesteś fotografem realizującym tego typu usługi albo drukarnią wykonującą wysokonakładowy druk 3D, możesz zostawić w naszym wortalu link do swojej strony.</p><p>Po akceptacji przez redakcję kafelek z logo i nazwą Twojej strony będzie prezentowany nad stopką portalu. Warunkiem udziału jest umieszczenie na wskazanej stronie partnera aktywnego linku prowadzącego do <strong>okulary-3d.pl</strong>.</p><p>Program jest przeznaczony zarówno dla pasjonatów i inicjatyw niekomercyjnych, jak i dla podmiotów komercyjnych związanych bezpośrednio ze stereoskopią, fotografią 3D, drukiem lentikularnym i pokrewnymi technikami.</p>',
                'seo_title' => 'Działajmy razem — partnerzy Okulary-3D.pl',
                'seo_description' => 'Zgłoś stronę związaną ze stereoskopią, fotografią 3D lub drukiem lentikularnym do programu partnerskiego Wortal Okulary 3D.',
                'translation_status' => 'source',
            ],
            'en' => [
                'title' => 'Let’s work together',
                'body_html' => '<p>If you are interested in stereoscopic photography or lenticular printing, work professionally as a 3D photographer, or represent a printing company producing lenticular or other high-volume 3D prints, you can submit a link to your website.</p><p>Once approved by the editorial team, a tile containing your logo and website name will be displayed above the portal footer. Participation requires an active backlink to <strong>okulary-3d.pl</strong> on the partner website you indicate.</p><p>The programme is open to both enthusiasts and non-commercial initiatives as well as commercial organisations directly connected with stereoscopy, 3D photography, lenticular printing and related techniques.</p>',
                'seo_title' => 'Let’s work together — Okulary-3D.pl partners',
                'seo_description' => 'Submit a website related to stereoscopy, 3D photography or lenticular printing to the 3D Glasses Portal partner programme.',
                'translation_status' => 'ready',
            ],
        ];

        foreach ($translations as $locale => $translation) {
            $exists = DB::table('static_page_translations')
                ->where('static_page_id', $pageId)
                ->where('locale', $locale)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('static_page_translations')->insert([
                'static_page_id' => $pageId,
                'locale' => $locale,
                'title' => $translation['title'],
                'body_html' => $translation['body_html'],
                'seo_title' => $translation['seo_title'],
                'seo_description' => $translation['seo_description'],
                'translation_status' => $translation['translation_status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
