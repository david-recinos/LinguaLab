<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the AI provider for generating intelligent content like
    | distractors for multiple-choice questions. Supports any OpenAI-compatible API.
    |
    */

    'default' => env('AI_PROVIDER', 'openai_compatible'),

    'providers' => [
        'openai_compatible' => [
            'name'        => env('AI_PROVIDER_NAME', 'Local LLM'),
            'base_url'    => env('AI_BASE_URL', 'http://localhost:1234/v1'),
            'api_key'     => env('AI_API_KEY'),
            'model'       => env('AI_MODEL'),
            'timeout'     => env('AI_TIMEOUT', 120),
            'max_tokens'  => env('AI_MAX_TOKENS', 4096),
            'temperature' => env('AI_TEMPERATURE', 0.7),
        ],

        // Legacy NVIDIA provider — kept for deployments still using AI_PROVIDER=nvidia.
        'nvidia' => [
            'name'        => 'NVIDIA NIM',
            'base_url'    => env('AI_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
            'api_key'     => env('AI_API_KEY'),
            'model'       => env('AI_MODEL', 'z-ai/glm5'),
            'timeout'     => env('AI_TIMEOUT', 120),
            'max_tokens'  => env('AI_MAX_TOKENS', 4096),
            'temperature' => env('AI_TEMPERATURE', 0.7),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific AI features. When disabled, the system
    | will fall back to non-AI alternatives.
    |
    */

    'features' => [
        'distractors' => [
            'enabled'           => env('AI_DISTRACTORS_ENABLED', true),
            'fallback_on_failure' => true,
        ],
    ],
];
