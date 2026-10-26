<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'odoo' => [
        'connector_url' => env('ODOO_CONNECTOR_URL', 'http://localhost:8090'),
        'webhook_secret' => env('ODOO_WEBHOOK_SECRET', 'change-me'),
        'source_token' => env('ODOO_SOURCE_TOKEN', ''),
    ],

    'llm' => [
        'provider' => env('LLM_PROVIDER', 'openai-compatible'),
        'base_url' => env('LLM_BASE_URL', 'http://localhost:20128/v1'),
        'api_key' => env('LLM_API_KEY'),
        'chat_model' => env('LLM_CHAT_MODEL', 'ag/gemini-3.8-flash-low'),
        'image_model' => env('LLM_IMAGE_MODEL', 'ag/gemini-3.1-flash-image,cf/@cf/bytedance/stable-diffusion-xl-lightning'),
        'vision_model' => env('LLM_VISION_MODEL', 'ag/gemini-3.1-flash-image'),
        'max_tokens' => (int) env('LLM_MAX_TOKENS', 0) ?: null,
        'timeout' => (int) env('LLM_TIMEOUT_SECONDS', 60),
    ],

];
