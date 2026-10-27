<?php

namespace Webkul\Telegram\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Telegram\Models\ChannelIntegration;
use Webkul\Telegram\Models\Message;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        ChannelIntegration::class,
        Message::class,
    ];
}
