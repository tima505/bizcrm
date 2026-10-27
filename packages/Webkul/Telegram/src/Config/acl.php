<?php

return [
    [
        'key' => 'settings.integrations',
        'name' => 'telegram::app.acl.integrations',
        'route' => 'admin.settings.integrations.index',
        'sort' => 95,
    ], [
        'key' => 'settings.integrations.view',
        'name' => 'telegram::app.acl.view',
        'route' => 'admin.settings.integrations.index',
        'sort' => 1,
    ], [
        'key' => 'settings.integrations.edit',
        'name' => 'telegram::app.acl.edit',
        'route' => ['admin.settings.integrations.telegram.connect', 'admin.settings.integrations.telegram.disconnect'],
        'sort' => 2,
    ],
];
