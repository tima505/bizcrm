<?php

return [
    'menu' => [
        'title' => 'Messages',
    ],

    'acl' => [
        'view' => 'View',
        'send' => 'Send',
    ],

    'index' => [
        'title' => 'Messages',
        'no-chats' => 'No chats yet. Incoming messages will appear here automatically.',
        'pick-chat' => 'Select a chat on the left',
        'service-offline' => 'WhatsApp Node service is unavailable. Start it and reload the page.',
    ],

    'thread' => [
        'empty' => 'No messages in this chat yet.',
    ],

    'send' => [
        'placeholder' => 'Type a message…',
        'button' => 'Send',
        'success' => 'Sent',
        'failed' => 'Failed to send the message',
    ],

    'validations' => [
        'bad-secret' => 'Invalid secret',
    ],
];
