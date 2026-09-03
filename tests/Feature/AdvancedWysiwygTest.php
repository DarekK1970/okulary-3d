<?php

namespace Tests\Feature;

use App\Services\ArticleHtmlSanitizer;
use Tests\TestCase;

class AdvancedWysiwygTest extends TestCase
{
    public function test_global_admin_editor_contains_advanced_features(): void
    {
        $javascript = file_get_contents(
            resource_path(
                'js/admin-cms.js'
            )
        );

        $this->assertIsString(
            $javascript
        );

        foreach (
            [
                'source',
                'fullscreen',
                'insertHorizontalRule',
                'insertUnorderedList',
                'insertOrderedList',
                'justifyFull',
                'foreColor',
                'hiliteColor',
                'tableMarkup',
                'sanitizePastedHtml',
                'data-media-picker-modal',
                'Słowa:',
                'Znaki:',
            ] as $needle
        ) {
            $this->assertStringContainsString(
                $needle,
                $javascript
            );
        }
    }

    public function test_sanitizer_keeps_advanced_safe_markup(): void
    {
        $html = <<<'HTML'
<h2 style="text-align: center; color: #123456">Nagłówek</h2>
<p><u>Podkreślenie</u> <s>stare</s> H<sub>2</sub>O x<sup>2</sup></p>
<table>
<thead><tr><th scope="col">A</th></tr></thead>
<tbody><tr><td colspan="2">B</td></tr></tbody>
</table>
<hr>
<pre><code>echo "3D";</code></pre>
<img src="/storage/media/test.webp" alt="3D">
HTML;

        $result = app(
            ArticleHtmlSanitizer::class
        )->sanitize($html);

        $this->assertStringContainsString(
            '<h2 style="text-align: center; color: #123456">',
            $result
        );

        $this->assertStringContainsString(
            '<u>Podkreślenie</u>',
            $result
        );

        $this->assertStringContainsString(
            '<table>',
            $result
        );

        $this->assertStringContainsString(
            'colspan="2"',
            $result
        );

        $this->assertStringContainsString(
            '<img src="/storage/media/test.webp" alt="3D">',
            $result
        );
    }

    public function test_image_size_and_alignment_survive_sanitizing_without_allowing_arbitrary_layout(): void
    {
        $sanitizer = app(ArticleHtmlSanitizer::class);
        foreach (['0px' => 'auto', 'auto' => '0px'] as $left => $right) {
            $result = $sanitizer->sanitize(
                '<img src="/storage/photo.jpg" width="320" alt="Photo" style="display:block;margin-left:'.$left.';margin-right:'.$right.';position:fixed;transform:scale(8)">'
            );
            $this->assertStringContainsString('width="320"', $result);
            $this->assertStringContainsString('display: block', $result);
            $this->assertStringContainsString('margin-left: '.$left, $result);
            $this->assertStringContainsString('margin-right: '.$right, $result);
            $this->assertStringNotContainsString('position', $result);
            $this->assertStringNotContainsString('transform', $result);
            $this->assertSame($result, $sanitizer->sanitize($result));
        }

        $result = $sanitizer->sanitize('<img src="/photo.jpg" style="display:none;margin-left:-9999px;margin-right:expression(alert(1))"><p style="display:block;margin-left:auto">Text</p>');
        $this->assertStringNotContainsString('style=', $result);
    }

    public function test_sanitizer_removes_scriptable_content_and_unsafe_styles(): void
    {
        $html = <<<'HTML'
<p
    onclick="alert(1)"
    style="position:fixed; color:#112233; background-image:url(javascript:alert(1))"
>
    Test
</p>
<a href="javascript:alert(1)" target="_blank">link</a>
<img src="data:image/svg+xml;base64,PHN2Zz4=" onerror="alert(1)">
<iframe src="https://example.com"></iframe>
HTML;

        $result = app(
            ArticleHtmlSanitizer::class
        )->sanitize($html);

        $this->assertStringNotContainsString(
            'onclick',
            $result
        );

        $this->assertStringNotContainsString(
            'position:',
            $result
        );

        $this->assertStringNotContainsString(
            'background-image',
            $result
        );

        $this->assertStringContainsString(
            'color: #112233',
            $result
        );

        $this->assertStringNotContainsString(
            'javascript:',
            $result
        );

        $this->assertStringNotContainsString(
            'data:image',
            $result
        );

        $this->assertStringNotContainsString(
            '<iframe',
            $result
        );
    }
}
