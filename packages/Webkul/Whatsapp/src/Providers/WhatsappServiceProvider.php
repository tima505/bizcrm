<?php

namespace Webkul\Whatsapp\Providers;

use Illuminate\Support\ServiceProvider;

class WhatsappServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'whatsapp');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'whatsapp');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api-routes.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'whatsapp');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        $this->app->register(ModuleServiceProvider::class);
    }
}
