<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fallback bot token (dev only)
    |--------------------------------------------------------------------------
    |
    | In production the token is set via Admin → Настройки → Интеграции and
    | stored encrypted in the `channel_integrations` table. The env value is
    | only consulted when no DB row exists — handy for a fresh dev machine.
    */
    'token' => env('TELEGRAM_BOT_TOKEN'),

    /*
    | Shared secret appended to the webhook URL and sent back by Telegram in
    | the `X-Telegram-Bot-Api-Secret-Token` header. If empty, the webhook
    | middleware refuses every call — so set this before pointing a prod
    | webhook at the server.
    */
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    /*
    | Public base URL used by `php artisan telegram:webhook:set` when
    | registering the webhook with Telegram. Falls back to APP_URL.
    */
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),

    /*
    | Bot API base. Overridable for self-hosted bot API server if ever needed.
    */
    'api_base' => env('TELEGRAM_API_BASE', 'https://api.telegram.org'),

    /*
    | Long-polling timeout in seconds (Bot API max is 50). Keep below Laravel's
    | HTTP timeout buffer so the artisan command does not deadlock.
    */
    'poll_timeout' => (int) env('TELEGRAM_POLL_TIMEOUT', 25),
];
