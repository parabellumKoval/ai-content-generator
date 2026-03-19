<?php

return [
    'default_driver' => env('AI_CONTENT_DEFAULT_DRIVER', 'openai'),

    'response_format' => 'text', // text|json|array|image

    'output_type' => 'single', // single|collection

    'collection_strategy' => env('AI_CONTENT_COLLECTION_STRATEGY', 'single_array'), // single_array|multi_completion

    'logging' => [
        'enabled' => true,
    ],

    'tables' => [
        'history' => 'ai_content_generations',
        'providers' => 'ai_provider_statuses',
    ],

    'drivers' => [
        'openai' => [
            'name' => 'OpenAI',
            'handler' => ParabellumKoval\AiContentGenerator\Services\Drivers\OpenAiDriver::class,
            'api_key' => env('OPENAI_API_KEY'),
            'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
            'default_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'max_n' => env('OPENAI_MAX_N', 8),
            'models' => [
                'gpt-4o-mini' => 'gpt-4o-mini',
                'gpt-4o' => 'gpt-4o',
                'gpt-5.1' => 'gpt-5.1',
            ],
            'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 15),
            'timeout' => (int) env('OPENAI_TIMEOUT', 90),
        ],
        'gemini' => [
            'name' => 'Gemini',
            'handler' => ParabellumKoval\AiContentGenerator\Services\Drivers\GeminiDriver::class,
            'api_key' => env('GEMINI_API_KEY'),
            'base_uri' => env('GEMINI_BASE_URI', 'https://generativelanguage.googleapis.com/v1beta'),
            'default_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'models' => [
                'gemini-3.1-pro-preview' => 'gemini-3.1-pro-preview',
                'gemini-3-flash-preview' => 'gemini-3-flash-preview',
                'gemini-3.1-flash-lite-preview' => 'gemini-3.1-flash-lite-preview',
                'gemini-3.1-flash-image-preview' => 'gemini-3.1-flash-image-preview',
                'gemini-3-pro-image-preview' => 'gemini-3-pro-image-preview',
                'gemini-2.5-pro' => 'gemini-2.5-pro',
                'gemini-2.5-flash' => 'gemini-2.5-flash',
                'gemini-2.5-flash-lite' => 'gemini-2.5-flash-lite',
                'gemini-2.5-flash-image' => 'gemini-2.5-flash-image',
            ],
            'model_aliases' => [
                // Deprecated and shut down models: remap to currently supported counterparts.
                'gemini-2.5-flash-image-preview' => 'gemini-2.5-flash-image',
                'gemini-2.0-flash-exp-image-generation' => 'gemini-2.5-flash-image',
                'gemini-2.0-flash-preview-image-generation' => 'gemini-2.5-flash-image',
                'gemini-1.5-flash' => 'gemini-2.5-flash-lite',
                'gemini-1.5-pro' => 'gemini-2.5-pro',
                'gemini-3-pro-preview' => 'gemini-3.1-pro-preview',
            ],
            'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 15),
            'timeout' => (int) env('GEMINI_TIMEOUT', 90),
        ],
        'grok' => [
            'name' => 'Grok',
            'handler' => ParabellumKoval\AiContentGenerator\Services\Drivers\GrokDriver::class,
            'api_key' => env('GROK_API_KEY'),
            'base_uri' => env('GROK_BASE_URI', 'https://api.x.ai/v1'),
            'default_model' => env('GROK_MODEL', 'grok-beta'),
            'models' => [
                'grok-beta' => 'grok-beta',
                'grok-2' => 'grok-2',
            ],
            'connect_timeout' => (int) env('GROK_CONNECT_TIMEOUT', 15),
            'timeout' => (int) env('GROK_TIMEOUT', 90),
        ],
    ],

    'rate_limit' => [
        'cooldown_minutes' => 10,
    ],

    'error' => [
        'cooldown_minutes' => (int) env('AI_CONTENT_ERROR_COOLDOWN_MINUTES', 2),
    ],
];
