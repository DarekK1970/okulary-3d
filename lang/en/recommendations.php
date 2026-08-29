<?php

return [
    'tools' => [
        'anaglyph' => [
            'title' => 'Anaglyph Maker',
            'description' => 'Combine left and right images into a red-cyan anaglyph and export the result.',
        ],
        'stereo_alignment' => [
            'title' => 'Stereo Alignment / Converter',
            'description' => 'Align a stereo pair and inspect it as Parallel, Cross-eye, Overlay or Anaglyph.',
        ],
        'lenticular' => [
            'title' => 'Lenticular LAB',
            'description' => 'Interlace images, generate a Pitch Test and prepare a print-size PDF for lenticular material.',
        ],
        'mpo' => [
            'title' => 'MPO Viewer / Converter',
            'description' => 'Open MPO files, extract the L/R pair and convert it into convenient stereo formats.',
        ],
        'wigglegram' => [
            'title' => 'Wigglegram Maker',
            'description' => 'Turn a stereo pair or image sequence into an animated depth effect.',
        ],
    ],

    'admin' => [
        'kicker' => 'Article → LAB → Shop path',
        'title' => 'Contextual recommendations',
        'help' => 'Select tools and products that best extend the article topic. Manual selections always take priority over automatic matching.',
        'auto' => 'Automatically complete recommendations',
        'auto_help' => 'If the manual selection is incomplete, the system can fill remaining slots based on article content. Disable this to display manual choices only.',
        'tools' => '3D LAB tools',
        'tools_help' => 'Select up to 2 tools.',
        'products' => 'Shop products',
        'products_help' => 'Select up to 4 products. Use Ctrl/Cmd to select multiple items.',
    ],

    'public' => [
        'kicker' => 'From article to practice',
        'title' => 'Try this topic in practice',
        'description' => 'Open the relevant 3D LAB tool or explore products related to the technique discussed in this article.',
        'tools_title' => 'Related tools',
        'products_title' => 'Related products',
        'shop_badge' => '3D Shop',
        'open_tool' => 'Open tool',
        'open_product' => 'View product',
        'from' => 'from',
    ],
];
