<?php

return [
    'menu' => 'fal.ai', 'kicker' => 'AI LENTICULAR STUDIO', 'title' => 'fal.ai integration',
    'description' => 'Seedance video generation, quality upscaling and cost safeguard settings.',
    'ready' => 'Ready', 'not_ready' => 'Configuration required', 'save' => 'Save settings',
    'connection' => ['title' => 'API connection', 'description' => 'Credentials are encrypted in the database and available only to super administrators.', 'enabled' => 'Enable fal.ai integration', 'enabled_help' => 'Generation becomes available after a valid key is saved.', 'timeout' => 'Request timeout (seconds)', 'api_key' => 'API key', 'key_placeholder' => 'Paste an API key from the fal.ai dashboard', 'secret_help' => 'Leave blank to keep the current key.', 'clear_key' => 'Remove saved API key'],
    'seedance' => ['title' => 'Seedance — image to video', 'description' => 'Default generation parameters for 3D sequences.', 'model' => 'Model endpoint', 'resolution' => 'Resolution', 'duration' => 'Video duration (seconds)', 'audio' => 'Generate audio', 'audio_help' => 'Audio should remain disabled for lenticular projects.'],
    'upscale' => ['title' => 'Resolution upscaling', 'description' => 'Prepare assets for A3 and larger prints.', 'enabled' => 'Enable upscaling for demanding formats', 'enabled_help' => 'The project engine will decide when source resolution is insufficient.', 'model' => 'Upscaler endpoint', 'resolution' => 'Target resolution'],
    'cost' => ['title' => 'Cost safeguards', 'description' => 'Hard limits checked before a paid job is submitted.', 'maximum_job' => 'Maximum single job cost (USD)', 'daily_budget' => 'Application daily budget (USD)', 'note' => 'Application limits complement the limits and balance configured in fal.ai.'],
    'test' => ['title' => 'Connection test', 'description' => 'Checks authorization by reading model pricing. It does not start paid generation.', 'button' => 'Test connection'],
    'messages' => ['saved' => 'fal.ai settings have been saved.', 'test_success' => 'The fal.ai connection works correctly.', 'missing_key' => 'Save a fal.ai API key first.', 'connection_error' => 'Could not connect to fal.ai.', 'test_failed' => 'fal.ai rejected the request (HTTP :status). Check the key and model endpoint.'],
];
