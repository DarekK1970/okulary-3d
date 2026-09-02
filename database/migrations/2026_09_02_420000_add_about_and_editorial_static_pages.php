<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('static_pages')
            || ! Schema::hasTable('static_page_translations')
        ) {
            return;
        }

        $pages = [
            [
                'key' => 'about',
                'group' => 'content',
                'sort_order' => 5,
                'translations' => [
                    'pl' => [
                        'title' => 'O nas',
                        'body_html' => '<p><strong>Wortal Okulary 3D</strong> to serwis tematyczny poświęcony stereoskopii, obrazowaniu przestrzennemu i technikom 3D — od historii fotografii stereoskopowej po współczesne rozwiązania cyfrowe.</p><h2>Co znajdziesz w wortalu?</h2><p>Publikujemy artykuły, materiały historyczne, opisy technik 3D, galerie oraz praktyczne narzędzia związane z anaglifem, polaryzacją, efektem Pulfricha, fotografią stereoskopową, lentikularem i innymi metodami tworzenia obrazu przestrzennego.</p><h2>Dla kogo tworzymy?</h2><p>Serwis jest kierowany zarówno do osób rozpoczynających przygodę ze stereoskopią, jak i do pasjonatów, fotografów, twórców, edukatorów oraz osób szukających praktycznej wiedzy o sprzęcie i technikach 3D.</p><h2>Kontakt</h2><p>Uwagi do publikacji, propozycje tematów i informacje dotyczące współpracy można przesyłać na adres <strong>kontakt@okulary-3d.pl</strong>.</p>',
                        'seo_title' => 'O nas — Wortal Okulary 3D',
                        'seo_description' => 'Poznaj Wortal Okulary 3D — serwis o stereoskopii, technikach obrazu przestrzennego, fotografii 3D, lentikularze i historii trójwymiarowego obrazu.',
                        'translation_status' => 'source',
                    ],
                    'en' => [
                        'title' => 'About us',
                        'body_html' => '<p><strong>3D Glasses Portal</strong> is a thematic website devoted to stereoscopy, spatial imaging and 3D techniques — from the history of stereoscopic photography to modern digital solutions.</p><h2>What can you find here?</h2><p>We publish articles, historical materials, explanations of 3D techniques, galleries and practical tools related to anaglyphs, polarization, the Pulfrich effect, stereoscopic photography, lenticular imaging and other methods of creating spatial images.</p><h2>Who is the portal for?</h2><p>The website is intended both for people beginning their journey with stereoscopy and for enthusiasts, photographers, creators, educators and anyone looking for practical knowledge about 3D equipment and techniques.</p><h2>Contact</h2><p>Comments on publications, topic suggestions and cooperation enquiries can be sent to <strong>kontakt@okulary-3d.pl</strong>.</p>',
                        'seo_title' => 'About us — 3D Glasses Portal',
                        'seo_description' => 'Learn about 3D Glasses Portal — a website devoted to stereoscopy, spatial imaging techniques, 3D photography, lenticular imaging and the history of three-dimensional images.',
                        'translation_status' => 'ready',
                    ],
                ],
            ],
            [
                'key' => 'editorial-policy',
                'group' => 'content',
                'sort_order' => 60,
                'translations' => [
                    'pl' => [
                        'title' => 'Redakcja portalu',
                        'body_html' => '<p>Materiały informacyjne w <strong>Wortalu Okulary 3D</strong> przygotowuje i redaguje zespół redakcyjny serwisu. W pracach mogą uczestniczyć autorzy, współpracownicy oraz administratorzy odpowiedzialni za publikację i aktualizację treści. Za zasady publikowania, korekty i utrzymanie jakości materiałów odpowiada redakcja wortalu.</p><h2>Zasady przygotowania materiałów</h2><ul><li>Publikacje informacyjne powinny opierać się na możliwych do zweryfikowania źródłach, dokumentacji, materiałach producentów, publikacjach historycznych lub wiedzy specjalistycznej właściwej dla omawianego tematu.</li><li>Materiały zaczerpnięte z zewnętrznych źródeł są opracowywane redakcyjnie; celem nie jest kopiowanie cudzych publikacji, lecz przygotowanie własnego, użytecznego opracowania.</li><li>W materiałach historycznych i archiwalnych staramy się zachować kontekst, datowanie i informacje o pochodzeniu prezentowanych obiektów lub danych, gdy są dostępne.</li><li>Treści handlowe i informacje o produktach powinny być możliwe do odróżnienia od materiałów redakcyjnych i edukacyjnych.</li><li>Po wykryciu istotnego błędu materiał może zostać poprawiony, uzupełniony albo zaktualizowany.</li></ul><h2>Wykorzystanie sztucznej inteligencji</h2><p>Narzędzia AI mogą wspierać wyszukiwanie i porządkowanie informacji, przygotowanie wersji roboczych, tłumaczenie, korektę językową, tworzenie metadanych oraz pracę nad materiałami ilustracyjnymi. AI traktujemy jako narzędzie wspomagające proces redakcyjny, a nie jako podmiot odpowiedzialny za treść. Redakcja odpowiada za zasady weryfikacji, korekty i aktualizacji opublikowanych materiałów.</p><h2>Źródła, prawa i materiały zewnętrzne</h2><p>Przy opracowywaniu treści respektujemy prawa autorów i właścicieli materiałów. Cytaty, reprodukcje, fotografie i inne materiały zewnętrzne powinny być wykorzystywane w zakresie dopuszczonym przez prawo, licencję lub zgodę uprawnionego podmiotu. Gdy charakter publikacji tego wymaga, wskazujemy źródło lub autorstwo.</p><h2>Korekty i kontakt z redakcją</h2><p>Jeżeli zauważysz błąd rzeczowy, nieaktualną informację, problem z oznaczeniem źródła albo chcesz zaproponować uzupełnienie materiału, napisz na <strong>kontakt@okulary-3d.pl</strong>. Zgłoszenia są analizowane i — gdy jest to uzasadnione — publikacja jest korygowana lub aktualizowana.</p>',
                        'seo_title' => 'Redakcja portalu — Wortal Okulary 3D',
                        'seo_description' => 'Informacje o zasadach redagowania materiałów w Wortalu Okulary 3D, wykorzystywaniu źródeł, narzędzi AI, korektach i odpowiedzialności redakcyjnej.',
                        'translation_status' => 'source',
                    ],
                    'en' => [
                        'title' => 'Editorial team',
                        'body_html' => '<p>Information published by <strong>3D Glasses Portal</strong> is prepared and edited by the portal editorial team. Authors, contributors and administrators responsible for publishing and updating content may take part in the process. The editorial team is responsible for publishing standards, corrections and maintaining the quality of the material.</p><h2>Editorial principles</h2><ul><li>Informational publications should be based on verifiable sources, documentation, manufacturer materials, historical publications or specialist knowledge relevant to the subject.</li><li>Material derived from external sources is editorially reworked; the purpose is not to copy third-party publications but to create an original and useful presentation.</li><li>For historical and archival material we aim to preserve context, dating and provenance information whenever it is available.</li><li>Commercial and product-related content should be distinguishable from editorial and educational material.</li><li>When a material error is identified, an article may be corrected, supplemented or updated.</li></ul><h2>Use of artificial intelligence</h2><p>AI tools may assist with finding and organizing information, preparing drafts, translations, language editing, metadata and illustrative materials. We treat AI as a tool supporting the editorial process, not as an entity responsible for the content. The editorial team remains responsible for the rules governing verification, correction and updating of published material.</p><h2>Sources, rights and external materials</h2><p>When preparing content we respect the rights of authors and rights holders. Quotations, reproductions, photographs and other external materials should be used only to the extent permitted by law, licence or the consent of the rights holder. Where the nature of the publication requires it, the source or authorship is indicated.</p><h2>Corrections and editorial contact</h2><p>If you notice a factual error, outdated information, an issue with source attribution or would like to suggest an addition, contact us at <strong>kontakt@okulary-3d.pl</strong>. Reports are reviewed and, where justified, the publication is corrected or updated.</p>',
                        'seo_title' => 'Editorial team — 3D Glasses Portal',
                        'seo_description' => 'Editorial standards of 3D Glasses Portal, including sources, use of AI tools, corrections and editorial responsibility.',
                        'translation_status' => 'ready',
                    ],
                ],
            ],
        ];

        foreach ($pages as $page) {
            $existing = DB::table('static_pages')
                ->where('key', $page['key'])
                ->first();

            if (! $existing) {
                $pageId = DB::table('static_pages')->insertGetId([
                    'key' => $page['key'],
                    'group' => $page['group'],
                    'source_locale' => 'pl',
                    'is_active' => true,
                    'sort_order' => $page['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $pageId = $existing->id;

                DB::table('static_pages')
                    ->where('id', $pageId)
                    ->update([
                        'group' => $page['group'],
                        'sort_order' => $page['sort_order'],
                        'updated_at' => now(),
                    ]);
            }

            foreach ($page['translations'] as $locale => $translation) {
                $translationExists = DB::table('static_page_translations')
                    ->where('static_page_id', $pageId)
                    ->where('locale', $locale)
                    ->exists();

                if ($translationExists) {
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
    }

    public function down(): void
    {
        // Content can be edited in the CMS after deployment.
        // Intentionally keep these pages on rollback to avoid deleting
        // editorial work that may have been added later.
    }
};
