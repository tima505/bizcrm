<?php

return [
    [
        'key' => 'messages',
        'name' => 'whatsapp::app.menu.title',
        'route' => 'admin.messages.index',
        'sort' => 5,
    ], [
        'key' => 'messages.view',
        'name' => 'whatsapp::app.acl.view',
        'route' => ['admin.messages.index', 'admin.messages.thread'],
        'sort' => 1,
    ], [
        'key' => 'messages.send',
        'name' => 'whatsapp::app.acl.send',
        'route' => 'admin.messages.send',
        'sort' => 2,
    ],
];
