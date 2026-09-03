<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class ArticleHtmlSanitizer
{
    /**
     * HTML supported by the global K89 editor.
     *
     * The allow-list is intentionally narrower than arbitrary HTML.
     * Scriptable/embedded elements and unsafe URL/style values are removed.
     */
    private const ALLOWED_TAGS = [
        'p',
        'br',
        'h2',
        'h3',
        'h4',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'ul',
        'ol',
        'li',
        'blockquote',
        'a',
        'span',
        'hr',
        'pre',
        'code',
        'sup',
        'sub',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'th',
        'td',
        'img',
    ];

    private const GLOBAL_ATTRIBUTES = [
        'style',
    ];

    private const ATTRIBUTES = [
        'a' => [
            'href',
            'target',
            'rel',
            'title',
        ],
        'img' => [
            'src',
            'alt',
            'title',
            'width',
            'height',
        ],
        'th' => [
            'colspan',
            'rowspan',
            'scope',
        ],
        'td' => [
            'colspan',
            'rowspan',
        ],
    ];

    private const STYLE_PROPERTIES = [
        'text-align',
        'color',
        'background-color',
        'font-size',
        'font-family',
        'text-decoration',
    ];

    public function sanitize(
        ?string $html
    ): string {
        $html = trim(
            (string) $html
        );

        if ($html === '') {
            return '';
        }

        $document =
            new DOMDocument(
                '1.0',
                'UTF-8'
            );

        $previous =
            libxml_use_internal_errors(
                true
            );

        $document->loadHTML(
            '<?xml encoding="UTF-8">'
            .'<div data-sanitize-root>'
            .$html
            .'</div>',
            LIBXML_HTML_NOIMPLIED
            | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors(
            $previous
        );

        $root = $document
            ->getElementsByTagName(
                'div'
            )
            ->item(0);

        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->sanitizeChildren(
            $root
        );

        $result = '';

        foreach (
            iterator_to_array(
                $root->childNodes
            ) as $child
        ) {
            $result .= $document
                ->saveHTML($child);
        }

        return trim($result);
    }

    private function sanitizeChildren(
        DOMNode $parent
    ): void {
        foreach (
            iterator_to_array(
                $parent->childNodes
            ) as $child
        ) {
            if (
                $child->nodeType
                !== XML_ELEMENT_NODE
            ) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower(
                $child->tagName
            );

            if (
                ! in_array(
                    $tag,
                    self::ALLOWED_TAGS,
                    true
                )
            ) {
                while (
                    $child->firstChild
                ) {
                    $parent->insertBefore(
                        $child->firstChild,
                        $child
                    );
                }

                $parent->removeChild(
                    $child
                );

                continue;
            }

            $this->sanitizeAttributes(
                $child,
                $tag
            );

            $this->sanitizeChildren(
                $child
            );
        }
    }

    private function sanitizeAttributes(
        DOMElement $element,
        string $tag
    ): void {
        $allowed = array_merge(
            self::GLOBAL_ATTRIBUTES,
            self::ATTRIBUTES[$tag]
                ?? []
        );

        foreach (
            iterator_to_array(
                $element->attributes
            ) as $attribute
        ) {
            $name = strtolower(
                $attribute->name
            );

            if (
                str_starts_with(
                    $name,
                    'on'
                )
                || ! in_array(
                    $name,
                    $allowed,
                    true
                )
            ) {
                $element
                    ->removeAttribute(
                        $attribute->name
                    );

                continue;
            }

            if ($name === 'style') {
                $style =
                    $this->sanitizeStyle(
                        $attribute->value,
                        strtolower($element->tagName) === 'img'
                    );

                if ($style === '') {
                    $element
                        ->removeAttribute(
                            'style'
                        );
                } else {
                    $element
                        ->setAttribute(
                            'style',
                            $style
                        );
                }
            }
        }

        if ($tag === 'a') {
            $this->sanitizeLink(
                $element
            );
        }

        if ($tag === 'img') {
            $this->sanitizeImage(
                $element
            );
        }

        if (
            in_array(
                $tag,
                ['td', 'th'],
                true
            )
        ) {
            $this->sanitizeSpanAttribute(
                $element,
                'colspan',
                20
            );

            $this->sanitizeSpanAttribute(
                $element,
                'rowspan',
                100
            );
        }
    }

    private function sanitizeLink(
        DOMElement $element
    ): void {
        $href = trim(
            $element->getAttribute(
                'href'
            )
        );

        if (
            $href !== ''
            && ! $this->isSafeUrl(
                $href,
                true
            )
        ) {
            $element->removeAttribute(
                'href'
            );
        }

        $target = $element
            ->getAttribute(
                'target'
            );

        if (
            $target !== ''
            && $target !== '_blank'
        ) {
            $element->removeAttribute(
                'target'
            );
        }

        if (
            $element->getAttribute(
                'target'
            ) === '_blank'
        ) {
            $element->setAttribute(
                'rel',
                'noopener noreferrer'
            );
        } else {
            $element->removeAttribute(
                'rel'
            );
        }
    }

    private function sanitizeImage(
        DOMElement $element
    ): void {
        $src = trim(
            $element->getAttribute(
                'src'
            )
        );

        if (
            $src === ''
            || ! $this->isSafeUrl(
                $src,
                false
            )
        ) {
            $element->removeAttribute(
                'src'
            );
        }

        foreach (
            ['width', 'height'] as $dimension
        ) {
            $value = $element
                ->getAttribute(
                    $dimension
                );

            if (
                $value !== ''
                && ! preg_match(
                    '/^\d{1,4}$/',
                    $value
                )
            ) {
                $element
                    ->removeAttribute(
                        $dimension
                    );
            }
        }
    }

    private function isSafeUrl(
        string $value,
        bool $allowMailAndTelephone
    ): bool {
        if (
            str_starts_with(
                $value,
                '/'
            )
            || str_starts_with(
                $value,
                './'
            )
            || str_starts_with(
                $value,
                '../'
            )
            || str_starts_with(
                $value,
                '#'
            )
        ) {
            return true;
        }

        $scheme = strtolower(
            (string) parse_url(
                $value,
                PHP_URL_SCHEME
            )
        );

        $allowed = [
            'http',
            'https',
        ];

        if ($allowMailAndTelephone) {
            $allowed[] = 'mailto';
            $allowed[] = 'tel';
        }

        return in_array(
            $scheme,
            $allowed,
            true
        );
    }

    private function sanitizeStyle(
        string $style,
        bool $isImage = false
    ): string {
        $safe = [];

        foreach (
            explode(';', $style) as $declaration
        ) {
            if (
                ! str_contains(
                    $declaration,
                    ':'
                )
            ) {
                continue;
            }

            [
                $property,
                $value,
            ] = array_map(
                'trim',
                explode(
                    ':',
                    $declaration,
                    2
                )
            );

            $property = strtolower(
                $property
            );

            if ($isImage && (
                ($property === 'display' && $value === 'block')
                || (in_array($property, ['margin-left', 'margin-right'], true)
                    && preg_match('/^(auto|0(?:px)?)$/', $value))
            )) {
                $safe[] = $property.': '.$value;

                continue;
            }

            if (
                ! in_array(
                    $property,
                    self::STYLE_PROPERTIES,
                    true
                )
            ) {
                continue;
            }

            if (
                $value === ''
                || preg_match(
                    '/(?:url\s*\(|expression\s*\(|behavior\s*:|-moz-binding)/i',
                    $value
                )
            ) {
                continue;
            }

            if (
                ! $this->safeStyleValue(
                    $property,
                    $value
                )
            ) {
                continue;
            }

            $safe[] =
                $property
                .': '
                .$value;
        }

        return implode(
            '; ',
            $safe
        );
    }

    private function safeStyleValue(
        string $property,
        string $value
    ): bool {
        return match ($property) {
            'text-align' => in_array(
                strtolower($value),
                [
                    'left',
                    'center',
                    'right',
                    'justify',
                ],
                true
            ),

            'text-decoration' => preg_match(
                '/^(?:none|underline|line-through|underline line-through|line-through underline)$/i',
                $value
            ) === 1,

            'font-size' => preg_match(
                '/^(?:[8-9]|[1-6]\d|72)(?:px|pt)$|^(?:0\.[5-9]|1(?:\.[0-9])?|2)em$|^(?:50|60|70|80|90|100|110|120|130|140|150|175|200)%$/i',
                $value
            ) === 1,

            'font-family' => preg_match(
                '/^[a-z0-9 ,"\'-]{1,120}$/i',
                $value
            ) === 1,

            'color',
            'background-color' => preg_match(
                '/^(?:#[0-9a-f]{3,8}|rgba?\([0-9., %]+\)|hsla?\([0-9., %deg]+\)|[a-z]{3,24})$/i',
                $value
            ) === 1,

            default => false,
        };
    }

    private function sanitizeSpanAttribute(
        DOMElement $element,
        string $attribute,
        int $max
    ): void {
        $value = $element
            ->getAttribute(
                $attribute
            );

        if ($value === '') {
            return;
        }

        if (
            ! ctype_digit($value)
            || (int) $value < 1
            || (int) $value > $max
        ) {
            $element
                ->removeAttribute(
                    $attribute
                );
        }
    }
}
