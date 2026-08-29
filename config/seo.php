<?php

return [
    'organization' => [
        'name' => 'Wortal Okulary 3D',
    ],

    'og_locales' => [
        'pl' => 'pl_PL',
        'en' => 'en_US',
    ],

    'indexable_routes' => [
        'home',
        'shop.index',
        'lab.index',
        'lab.anaglyph',
        'lab.stereo-alignment',
        'lab.lenticular',
        'lab.mpo',
        'lab.wigglegram',
        'archive.index',
        'gallery.index',
        'gallery.show',
    ],

    'noindex_routes' => [
        'gallery.create',
        'cart.*',
        'checkout.*',
        'order.*',
        'payment.*',
        'login',
        'register',
        'password.*',
        'account',
        'account.*',
    ],

    'sitemap_static_routes' => [
        'home',
        'shop.index',
        'lab.index',
        'lab.anaglyph',
        'lab.stereo-alignment',
        'lab.lenticular',
        'lab.mpo',
        'lab.wigglegram',
        'archive.index',
        'gallery.index',
    ],
];
