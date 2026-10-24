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
        'pick-chat-hint' => 'Pick a conversation on the left to read the history and reply.',
        'service-offline' => 'WhatsApp Node service is unavailable. Start it and reload the page.',
    ],

    'thread' => [
        'empty' => 'No messages in this chat yet.',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'open-lead' => 'Open lead',
    ],

    'chat' => [
        'anonymous' => 'WhatsApp contact',
    ],

    'time' => [
        'yesterday' => 'yesterday',
    ],

    'send' => [
        'placeholder' => 'Type a message… (Enter to send, Shift+Enter for new line)',
        'button' => 'Send',
        'success' => 'Sent',
        'failed' => 'Failed to send the message',
    ],

    'validations' => [
        'bad-secret' => 'Invalid secret',
    ],
];
