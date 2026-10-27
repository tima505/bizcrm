<?php

return [
    [
        'key' => 'settings.integrations',
        'name' => 'telegram::app.menu.integrations',
        'info' => 'telegram::app.menu.integrations-info',
        'route' => 'admin.settings.integrations.index',
        'sort' => 4,
        'icon-class' => '',
    ], [
        'key' => 'settings.integrations.telegram',
        'name' => 'telegram::app.menu.telegram',
        'info' => 'telegram::app.menu.telegram-info',
        'route' => 'admin.settings.integrations.index',
        'sort' => 1,
        'icon-class' => 'icon-mail',
    ],
];
