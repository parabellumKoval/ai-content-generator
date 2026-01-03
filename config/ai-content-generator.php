<?php

return [
    'default_driver' => env('AI_CONTENT_DEFAULT_DRIVER', 'openai'),

    'response_format' => 'text', // text|json|array

    'output_type' => 'single', // single|collection

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
            'models' => [
                'gpt-4o-mini' => 'gpt-4o-mini',
                'gpt-4o' => 'gpt-4o',
                'gpt-5.1' => 'gpt-5.1',
            ],
            'timeout' => 30,
        ],
        'gemini' => [
            'name' => 'Gemini',
            'handler' => ParabellumKoval\AiContentGenerator\Services\Drivers\GeminiDriver::class,
            'api_key' => env('GEMINI_API_KEY'),
            'base_uri' => env('GEMINI_BASE_URI', 'https://generativelanguage.googleapis.com/v1beta'),
            'default_model' => env('GEMINI_MODEL', 'gemini-pro'),
            'models' => [
                'gemini-1.5-flash' => 'gemini-1.5-flash',
                'gemini-1.5-pro' => 'gemini-1.5-pro',
            ],
            'timeout' => 30,
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
            'timeout' => 30,
        ],
    ],

    'rate_limit' => [
        'cooldown_minutes' => 10,
    ],
];
