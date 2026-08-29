@php
    echo '<?xml version="1.0" encoding="UTF-8"?>';
@endphp
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
>
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
        @if ($entry['lastmod'])
            <lastmod>{{ $entry['lastmod'] }}</lastmod>
        @endif
        @foreach ($entry['alternates'] as $locale => $alternateUrl)
            <xhtml:link
                rel="alternate"
                hreflang="{{ $locale }}"
                href="{{ $alternateUrl }}"
            />
        @endforeach
        @if (! empty($entry['alternates']))
            <xhtml:link
                rel="alternate"
                hreflang="x-default"
                href="{{ $entry['alternates'][$defaultLocale] ?? (array_values($entry['alternates'])[0] ?? '') }}"
            />
        @endif
    </url>
@endforeach
</urlset>
