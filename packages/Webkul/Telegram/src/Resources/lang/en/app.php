<?php

return [
    'menu' => [
        'integrations' => 'Integrations',
        'integrations-info' => 'Connect messaging channels — Telegram, WhatsApp and more.',
        'telegram' => 'Telegram',
        'telegram-info' => 'Configure a Telegram bot for inbound and outbound messages.',
    ],

    'acl' => [
        'integrations' => 'Integrations',
        'view' => 'View',
        'edit' => 'Edit',
    ],

    'index' => [
        'title' => 'Integrations',
        'subtitle' => 'Connect the channels your customers already use to reach you.',

        'telegram-title' => 'Telegram',
        'telegram-hint' => 'The bot receives inbound messages and lets your team reply right from CRM.',

        'token-label' => 'Bot token',
        'token-placeholder' => '123456789:AA...',
        'token-help' => 'Create a bot via @BotFather, copy the token and paste it here.',

        'connect' => 'Connect',
        'reconnect' => 'Reconnect',
        'disconnect' => 'Disconnect',

        'status-connected' => 'Connected',
        'status-disconnected' => 'Not connected',

        'bot-info' => 'Bot: :name',
        'verified-at' => 'Verified :date',

        'connected' => 'Done. Bot :bot is connected.',
        'disconnected' => 'Integration disabled. Token kept — you can re-enable it later.',

        'how-to-run' => 'How to receive messages',
        'how-to-run-dev' => 'Local dev: open another PowerShell window and run',
        'how-to-run-dev-cmd' => 'php artisan telegram:poll',
        'how-to-run-prod' => 'Production (public URL): register a webhook with',
        'how-to-run-prod-cmd' => 'php artisan telegram:webhook set --url=https://your.domain',
    ],

    'errors' => [
        'token-required' => 'Enter the bot token.',
        'token-format' => 'The token looks malformed. It should look like 123456789:AA....',
        'token-verify-failed' => 'Telegram rejected the token: :error',
    ],
];
