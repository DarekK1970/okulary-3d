<?php

return [
    'kicker' => 'AI translation workflow',
    'title' => 'AI Translator',
    'description' => 'Generate PL/EN versions of portal content. AI always saves a draft; publication requires a separate editorial decision.',
    'content_types' => 'Content types',

    'types' => [
        'article' => 'Articles',
        'product' => 'Products',
        'product_category' => 'Product categories',
        'marketplace_category' => 'Marketplace categories',
        'archive' => 'Archive',
    ],

    'status' => [
        'engine' => 'Engine',
        'ready' => 'Ready',
        'not_ready' => 'Not configured',
        'provider' => 'Provider',
        'model' => 'Model',
        'workflow' => 'Publication rule',
        'draft_only' => 'AI → Draft',
    ],

    'not_configured' => [
        'title' => 'The translator is not active yet.',
        'text' => 'A Super Admin must enable the translator, select a provider and model, and save an API key in the settings panel.',
    ],

    'table' => [
        'content' => 'Content',
        'direction' => 'Direction',
        'target_status' => 'Target version',
        'rule' => 'Rule',
        'actions' => 'Actions',
        'ready_locked' => 'Ready content is protected from AI overwrite.',
        'saved_as_draft' => 'AI output will be saved as Draft.',
        'edit' => 'Edit',
        'translate' => 'Translate with AI',
        'regenerate' => 'Regenerate',
        'empty' => 'No content in this section.',
    ],

    'target_statuses' => [
        'missing' => 'Missing translation',
        'source' => 'Source',
        'draft' => 'Draft',
        'review' => 'Review',
        'ready' => 'Ready',
    ],

    'run_statuses' => [
        'started' => 'Running',
        'success' => 'Success',
        'failed' => 'Failed',
    ],

    'runs' => [
        'kicker' => 'Audit / tokens',
        'title' => 'Recent runs',
        'description' => 'History stores the provider, model and token usage for later Orchestrator analysis.',
        'date' => 'Date',
        'content' => 'Content',
        'provider' => 'Provider / model',
        'tokens' => 'Total tokens',
        'user' => 'Started by',
        'status' => 'Status',
        'empty' => 'The translator has not been run yet.',
    ],

    'messages' => [
        'generated' => 'Translation was generated as Draft by :provider / :model.',
    ],

    'errors' => [
        'not_configured' => 'AI Translator is not configured or is disabled.',
        'provider' => 'Unsupported AI provider.',
        'http' => 'The AI provider returned HTTP error :status.',
        'empty_response' => 'The AI provider returned no translation content.',
        'invalid_json' => 'The AI provider returned an invalid data structure.',
        'source_missing' => 'The source-language version is missing.',
        'target_missing' => 'No second supported language was found.',
        'ready_locked' => 'The target version is Ready/Source and cannot be overwritten automatically. Change it manually to Draft or Review first.',
        'type' => 'Unsupported content type.',
        'required_field' => 'The AI provider returned an empty required field: :field.',
    ],

    'settings' => [
        'open' => 'AI settings',
        'kicker' => 'Super Admin / configuration',
        'title' => 'AI Translator Settings',
        'description' => 'API keys are stored encrypted in the application settings database. Manual .env editing is not required.',
        'back' => 'Back to translator',
        'general' => 'General configuration',
        'enabled' => 'Enable AI Translator',
        'enabled_help' => 'Disabling blocks new API calls without deleting configuration or history.',
        'provider' => 'Active provider',
        'timeout' => 'API timeout (seconds)',
        'model' => 'Model',
        'key_placeholder' => 'Enter API key',
        'secret_help' => 'Leave empty to keep the currently stored key.',
        'clear_key' => 'Delete stored key',
        'glossary' => 'Glossary and terminology rules',
        'glossary_help' => 'Optional project rules are appended to the system prompt for every translation.',
        'glossary_placeholder' => "Example:\nstereocard = stereoscopic card\nlenticular lens = lenticular lens\nDo not translate device model names.",
        'save' => 'Save settings',
        'saved' => 'AI Translator settings saved.',
    ],
];
