<?php

return [
    'common' => [
        'home' => 'Home',
        'local_only' => 'Local browser processing',
        'sources_kicker' => 'Source material',
        'sources_title' => 'Load a stereo pair',
        'sources_help' => 'For best results, use images with a similar crop and resolution.',
        'left_image' => 'Left image',
        'right_image' => 'Right image',
        'choose_or_drop' => 'Click or drop a JPG / PNG / WEBP file',
        'no_file' => 'No file selected',
        'swap' => 'Swap L / R',
        'preview' => 'Preview',
        'waiting' => 'Waiting for two images',
        'ready' => 'Ready',
        'loading' => 'Loading image…',
        'fit' => 'Fit',
        'reset' => 'Reset',
        'empty_title' => 'Load left and right images',
        'empty_text' => 'The preview appears automatically after both files are selected.',

        'geometry' => [
            'title' => 'Geometry correction',
            'help' => 'Adjust the right image position, scale and rotation against the left image.',
            'shift_x' => 'Horizontal shift',
            'shift_y' => 'Vertical shift',
            'scale' => 'Right image scale',
            'rotation' => 'Right image rotation',
            'tip' => 'Align vertical geometry first, then horizontal position. Small horizontal parallax creates depth in anaglyphs.',
        ],

        'export' => [
            'title' => 'Export',
            'help' => 'Generate the final PNG without uploading files to the server.',
            'size' => 'Maximum export dimension',
            'original' => 'Source resolution',
            'button' => 'Export PNG',
            'note' => 'Very large source images may require more RAM in original-resolution mode.',
        ],

        'errors' => [
            'two_images' => 'Select both left and right images first.',
            'image' => 'The image could not be loaded. Choose a valid JPG, PNG or WEBP file.',
        ],
    ],

    'index' => [
        'meta_title' => '3D LAB — stereoscopic tools',
        'meta_description' => 'Free browser tools for creating anaglyphs and preparing stereo image pairs.',
        'title' => '3D LAB',
        'description' => 'Practical tools for creating and preparing stereoscopic images. Start with a classic anaglyph or align a stereo pair for further processing.',
        'local_processing_title' => 'Your images stay on your device.',
        'local_processing' => 'In this version of 3D LAB, images are processed with Canvas in your browser and are not uploaded to the server.',
        'open_tool' => 'Open tool',

        'anaglyph' => [
            'title' => 'Anaglyph Maker',
            'description' => 'Combine left and right views into a classic red-cyan anaglyph.',
            'feature_1' => 'Color, Half-color, Gray and Optimized',
            'feature_2' => 'X/Y alignment, scale and rotation',
            'feature_3' => 'Final PNG export',
        ],

        'alignment' => [
            'title' => 'Stereo Alignment / Converter',
            'description' => 'Align a stereo pair and inspect it in several viewing modes before export.',
            'feature_1' => 'Parallel and Cross-eye',
            'feature_2' => 'Anaglyph, Overlay and Blink',
            'feature_3' => 'Side-by-side or anaglyph export',
        ],

        'workflow_kicker' => 'Workflow',
        'workflow_title' => 'From two photographs to a finished 3D image',

        'workflow' => [
            '1' => [
                'title' => 'Load',
                'text' => 'Add left and right images captured from two viewpoints.',
            ],
            '2' => [
                'title' => 'Align',
                'text' => 'Correct vertical/horizontal shift, scale and small rotation differences.',
            ],
            '3' => [
                'title' => 'Inspect',
                'text' => 'Switch between anaglyph, parallel, cross-eye or overlay preview.',
            ],
            '4' => [
                'title' => 'Export',
                'text' => 'Save the result as PNG for further use or publication.',
            ],
        ],
    ],

    'anaglyph' => [
        'meta_title' => 'Online Anaglyph Maker',
        'meta_description' => 'Combine two stereo photographs into a red-cyan anaglyph and export the result as PNG.',
        'title' => 'Anaglyph Maker',
        'description' => 'Load a stereo pair, align the images and generate a red-cyan anaglyph directly in your browser.',
        'mode_title' => 'Anaglyph method',
        'mode_help' => 'Choose how the left and right color channels are mixed.',
        'mode' => 'Mode',
        'modes' => [
            'color' => 'Color — full color',
            'half_color' => 'Half-color — softer red',
            'gray' => 'Gray — monochrome',
            'optimized' => 'Optimized — reduced ghosting/crosstalk',
        ],
        'preview_hint' => 'Red-cyan preview',
    ],

    'alignment' => [
        'meta_title' => 'Online Stereo Alignment / Converter',
        'meta_description' => 'Align two stereo images, inspect parallax and export a side-by-side pair or anaglyph.',
        'title' => 'Stereo Alignment / Converter',
        'description' => 'Precisely align left and right frames, switch viewing modes and prepare a stereo pair for further processing.',
        'preview_mode_title' => 'Preview mode',
        'preview_mode_help' => 'Change the viewing method without changing the geometry.',
        'preview_mode' => 'View',
        'modes' => [
            'parallel' => 'Parallel — side-by-side',
            'cross' => 'Cross-eye — swapped pair',
            'anaglyph' => 'Red-cyan anaglyph',
            'overlay' => '50% overlay',
            'blink' => 'Blink — alternating L/R',
        ],
        'export_help' => 'Export follows the current mode: anaglyph as a single image, other modes as side-by-side.',
        'preview_hint' => 'Pay special attention to horizontal lines and objects close to the frame edges.',
    ],
];
