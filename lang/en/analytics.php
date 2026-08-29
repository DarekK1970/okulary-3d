<?php

return [
    'ranges' => [
        'today' => 'Today',
        'days_7' => '7 days',
        'days_30' => '30 days',
    ],

    'metrics' => [
        'pageviews' => 'Page views',
        'sessions' => 'Sessions',
        'anonymous_sessions' => 'anonymous sessions',
        'pages_per_session' => 'Pages / session',
        'average' => 'average',
        'active_sessions' => 'Active now',
        'last_5_minutes' => 'last 5 minutes',
        'events' => 'Events',
        'interactions' => 'user interactions',
        'lab_actions' => 'LAB actions',
        'recommendation_clicks' => 'Recommendation clicks',
    ],

    'page_types' => [
        'home' => 'Homepage',
        'article' => 'Articles',
        'shop' => 'Shop listing',
        'product' => 'Products',
        'lab' => '3D LAB',
        'archive' => 'Archive',
        'gallery' => 'Gallery',
        'cart' => 'Cart',
        'checkout' => 'Checkout',
        'other' => 'Other',
    ],

    'sources' => [
        'direct' => 'Direct',
        'campaign' => 'UTM campaigns',
        'search' => 'Search engines',
        'social' => 'Social media',
        'referral' => 'Referrals',
        'internal' => 'Internal traffic',
        'other' => 'Other',
    ],

    'devices' => [
        'desktop' => 'Desktop',
        'mobile' => 'Mobile',
        'tablet' => 'Tablet',
        'other' => 'Other',
    ],

    'events' => [
        'lab_action' => '3D LAB action',
        'recommendation_click' => 'Recommendation click',
        'add_to_cart' => 'Add to cart',
        'checkout_submit' => 'Checkout submit',
        'gallery_mode' => 'Gallery mode change',
        'archive_view_mode' => 'Archive viewing mode',
        'newsletter_subscribe' => 'Newsletter signup attempt',
    ],

    'funnel' => [
        'product_views' => 'Product views',
        'add_to_cart' => 'Add to cart',
        'cart_views' => 'Cart views',
        'checkout_views' => 'Checkout views',
        'checkout_submit' => 'Checkout submits',
    ],

    'table' => [
        'time' => 'Time',
        'event' => 'Event',
        'category' => 'Category',
        'label' => 'Label',
        'page' => 'Page',
        'language' => 'Language',
    ],

    'admin' => [
        'kicker' => 'First-party analytics',
        'title' => 'Portal Analytics',
        'description' => 'Traffic, content popularity, acquisition sources, 3D LAB usage, contextual recommendations and a basic shop funnel without an external analytics platform.',
        'privacy_title' => 'Privacy-first:',
        'privacy_text' => 'IP addresses are not stored and no extra analytics cookie is created. Analytics sessions are based on a one-way HMAC of the existing Laravel session identifier and expire after 30 minutes of inactivity. DNT=1 disables measurement.',
        'traffic_kicker' => 'Traffic over time',
        'traffic_title' => 'Page views',
        'top_pages_kicker' => 'Most viewed',
        'top_pages' => 'Popular pages',
        'hourly_kicker' => 'Hourly distribution',
        'hourly' => 'Visit hours',
        'page_types' => 'Portal modules',
        'sources' => 'Traffic sources',
        'devices' => 'Devices',
        'languages' => 'Languages',
        'referrers_kicker' => 'Detailed acquisition',
        'referrers' => 'Domains / campaigns',
        'events_kicker' => 'Interactions',
        'events' => 'Top events',
        'funnel_kicker' => 'E-commerce',
        'funnel' => 'Basic shop funnel',
        'recent_kicker' => 'Live log',
        'recent_events' => 'Recent events',
        'no_data' => 'No data for the selected period.',
        'no_events' => 'No events have been recorded yet.',
    ],
];
