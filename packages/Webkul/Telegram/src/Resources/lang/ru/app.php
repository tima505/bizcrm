<?php

return [
    'menu' => [
        'integrations' => 'Интеграции',
        'integrations-info' => 'Подключение каналов связи — Telegram, WhatsApp и других.',
        'telegram' => 'Telegram',
        'telegram-info' => 'Настройка Telegram-бота для приёма и отправки сообщений.',
    ],

    'acl' => [
        'integrations' => 'Интеграции',
        'view' => 'Просмотр',
        'edit' => 'Изменение',
    ],

    'index' => [
        'title' => 'Интеграции',
        'subtitle' => 'Подключите каналы, через которые ваши клиенты уже с вами общаются.',

        'telegram-title' => 'Telegram',
        'telegram-hint' => 'Бот принимает входящие сообщения и позволяет отвечать прямо из CRM.',

        'token-label' => 'Токен бота',
        'token-placeholder' => '123456789:AA...',
        'token-help' => 'Создайте бота у @BotFather в Telegram, скопируйте выданный токен и вставьте сюда.',

        'connect' => 'Подключить',
        'reconnect' => 'Переподключить',
        'disconnect' => 'Отключить',

        'status-connected' => 'Подключён',
        'status-disconnected' => 'Не подключён',

        'bot-info' => 'Бот: :name',
        'verified-at' => 'Проверено :date',

        'connected' => 'Готово. Бот :bot подключён.',
        'disconnected' => 'Интеграция отключена. Токен сохранён — можно включить обратно.',

        'how-to-run' => 'Как запустить приём сообщений',
        'how-to-run-dev' => 'Локально: в отдельном окне PowerShell выполните',
        'how-to-run-dev-cmd' => 'php artisan telegram:poll',
        'how-to-run-prod' => 'На сервере с публичным URL: зарегистрируйте webhook командой',
        'how-to-run-prod-cmd' => 'php artisan telegram:webhook set --url=https://your.domain',
    ],

    'chat' => [
        'anonymous' => 'Клиент Telegram',
    ],

    'service' => [
        'start' => 'Клиент начал диалог',
    ],

    'errors' => [
        'token-required' => 'Введите токен бота.',
        'token-format' => 'Токен выглядит некорректно. Он должен быть вида 123456789:AA....',
        'token-verify-failed' => 'Telegram не принял токен: :error',
    ],
];
