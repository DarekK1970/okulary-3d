<?php

return [
    'actions' => [
        'edit' => 'Edit',
        'translate' => 'Automatic translation',
        'generate_image' => 'Generate image',
        'generate_image_confirm' => 'Generate a new AI image and assign it as this publication’s hero image?',
        'preview' => 'Preview',
        'delete' => 'Delete',
    ],

    'tooltips' => [
        'translation_ready' => 'The target language version is already ready, so automatic translation is locked.',
        'no_target_language' => 'No second target language is configured for the portal.',
        'preview_unavailable' => 'Preview becomes available after the article is published.',
    ],

    'messages' => [
        'image_generated' => 'The image was generated, stored in the Media Library and assigned to the publication.',
    ],

    'errors' => [
        'image_exists' => 'This publication already has an associated image. AI generation never overwrites an existing image.',
        'openai_not_configured' => 'Image generation requires enabled AI settings and an OpenAI API key.',
        'source_missing' => 'The source-language version of the publication was not found.',
        'empty_image' => 'OpenAI returned no image data.',
        'invalid_image' => 'OpenAI returned invalid image data.',
    ],
];
