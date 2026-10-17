<?php

namespace Webkul\Whatsapp\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Whatsapp\Models\Message;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Message::class,
    ];
}
