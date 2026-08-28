<?php

return [
    'meta' => [
        'title' => '3D Glasses Portal — stereoscopy, anaglyphs, lenticular and 3D shop',
        'description' => 'A portal dedicated to stereoscopy, 3D photography and video, lenticular imaging, online tools and 3D accessories.',
    ],

    'hero' => [
        'badge' => 'A new post from the 3D world every day',
        'title_html' => 'See the world<br><span class="text-red">in three</span> <span class="text-cyan">dimensions</span>',
        'lead' => 'Create 3D images, explore stereoscopy and discover techniques that add depth. Online tools, knowledge and a specialist shop — all in one place.',
        'cta_anaglyph' => 'Create an anaglyph',
        'cta_lenticular' => 'Lenticular Lab',
        'cta_shop' => '3D Shop',
        'history_years' => 'years of stereoscopy',
        'tools_online' => 'online tools',
        'languages' => 'language versions',
    ],

    'articles' => [
        'kicker' => 'Latest publications',
        'title' => 'Latest from the 3D world',
        'all' => 'View all articles',
        'items' => [
            [
                'tag' => '3D HISTORY',
                'title' => 'A brief history of stereoscopy',
                'description' => 'From Wheatstone and Brewster to modern glasses and spatial displays.',
                'date' => '28.08.2026',
                'reading_time' => '6 min read',
                'image' => 'article-history.svg',
            ],
            [
                'tag' => 'GUIDE',
                'title' => 'How to take a 3D photo with a smartphone',
                'description' => 'A simple method for creating a stereo pair without a dedicated camera or expensive equipment.',
                'date' => '27.08.2026',
                'reading_time' => '7 min read',
                'image' => 'article-smartphone.svg',
            ],
            [
                'tag' => 'TECHNOLOGY',
                'title' => 'Spatial Photo and the new 3D era',
                'description' => 'Classic stereoscopy returns under a new name and reaches a mass audience once again.',
                'date' => '26.08.2026',
                'reading_time' => '5 min read',
                'image' => 'article-spatial.svg',
            ],
        ],
    ],

    'lab' => [
        'kicker' => 'Create it yourself',
        'title' => '3D LAB — online tools',
        'description' => 'Practical browser-based tools. Upload your media, set parameters and download the final result.',
        'run' => 'Launch',
        'tools' => [
            [
                'title' => 'Anaglyph Maker',
                'description' => 'Combine left and right images into a classic red/cyan anaglyph.',
                'icon' => '<span class="mini-glasses"><i></i><i></i></span>',
            ],
            [
                'title' => '60 LPI Lenticular Creator',
                'description' => 'Prepare an interlaced print file for lenticular sheets.',
                'icon' => '<span class="mini-lenticular"></span>',
            ],
            [
                'title' => 'Stereo Converter',
                'description' => 'Convert between SBS, parallel, cross-eye and anaglyph formats.',
                'icon' => '<span class="mini-stereo">↔</span>',
            ],
            [
                'title' => 'Wigglegram Maker',
                'description' => 'Turn a stereo pair into an eye-catching GIF or WebP animation.',
                'icon' => '<span class="mini-wiggle">≋</span>',
            ],
            [
                'title' => 'Stereo Base Calculator',
                'description' => 'Calculate a safe camera separation for the photographed subject.',
                'icon' => '<span class="mini-ruler">↔</span>',
            ],
            [
                'title' => 'MPO Viewer',
                'description' => 'Open and split MPO files from Fuji, Sony and other 3D cameras.',
                'icon' => '<span class="mini-mpo">MPO</span>',
            ],
        ],
    ],

    'shop' => [
        'kicker' => '3D Shop',
        'title' => 'Shop categories',
        'all' => 'Go to shop',
        'products' => 'View products',
        'categories' => [
            [
                'title' => '3D Glasses',
                'price' => 'from PLN 2.90',
                'image' => 'shop-glasses.svg',
                'chips' => ['anaglyph', 'polarized', 'Pulfrich', 'electronic'],
            ],
            [
                'title' => 'Lenticular Sheets',
                'price' => 'from PLN 34.90',
                'image' => 'shop-lenticular.svg',
                'chips' => ['40 LPI', '60 LPI', '75 LPI', '100 LPI'],
            ],
            [
                'title' => 'Stereoscopes',
                'price' => 'from PLN 99.00',
                'image' => 'shop-stereoscope.svg',
                'chips' => ['pocket', 'Holmes', 'retro'],
            ],
            [
                'title' => '3D Cameras',
                'price' => 'new and used equipment',
                'image' => 'shop-camera.svg',
                'chips' => ['Fuji W3', 'stereo cameras', 'accessories'],
            ],
        ],
    ],

    'today' => [
        'kicker' => 'Stereoscopy today',
        'title' => '3D today',
        'items' => [
            [
                'label' => 'SPATIAL',
                'title' => 'Spatial Photos',
                'description' => 'A modern way to record stereoscopic photos and video on mobile devices.',
                'symbol' => '▣',
                'class' => 'today-blue',
            ],
            [
                'label' => 'IMMERSIVE',
                'title' => 'VR / AR',
                'description' => 'From stereoscopy to full immersion — technologies that create a sense of presence.',
                'symbol' => '◫',
                'class' => 'today-purple',
            ],
            [
                'label' => 'DISPLAY',
                'title' => '3D Displays',
                'description' => 'Autostereoscopy, light-field displays and a new generation of glasses-free imaging.',
                'symbol' => '◈',
                'class' => 'today-red',
            ],
        ],
    ],

    'gallery' => [
        'kicker' => 'Community',
        'title' => 'Community gallery',
        'tabs_label' => 'Stereo image viewing mode',
        'items' => [
            ['user' => '@stereo_fan', 'likes' => '128', 'mode' => 'Parallel'],
            ['user' => '@depth_explorer', 'likes' => '96', 'mode' => 'Cross-eye'],
            ['user' => '@3d_nature', 'likes' => '145', 'mode' => 'Anaglyph'],
            ['user' => '@city_in_3d', 'likes' => '87', 'mode' => 'Wiggle'],
            ['user' => '@retro_3d', 'likes' => '133', 'mode' => 'Parallel'],
            ['user' => '@stereoworld', 'likes' => '102', 'mode' => 'Anaglyph'],
        ],
    ],

    'archive' => [
        'kicker' => 'Stereoscopic heritage',
        'title' => 'From the stereoscopy archive',
        'description' => 'A journey through more than 180 years of spatial photography — from stereoscopic cards to modern digital reconstructions.',
        'all' => 'Explore the archive',
        'items' => [
            ['type' => 'Stereoscopic card', 'title' => 'Paris — view from the Seine', 'year' => 'circa 1900'],
            ['type' => 'Stereoscopic card', 'title' => 'Tatra Mountains — Morskie Oko', 'year' => '1898'],
            ['type' => 'Stereoscopic card', 'title' => 'Cathedral interior', 'year' => '1910'],
            ['type' => 'Stereoscopic card', 'title' => 'New York — Broadway', 'year' => '1904'],
            ['type' => 'Stereoscopic card', 'title' => 'Niagara Falls', 'year' => '1895'],
        ],
    ],
];
