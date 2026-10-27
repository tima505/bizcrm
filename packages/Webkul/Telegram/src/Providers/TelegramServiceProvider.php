<?php

namespace Webkul\Telegram\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Telegram\Console\Commands\PollCommand;
use Webkul\Telegram\Console\Commands\WebhookCommand;

class TelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'telegram');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'telegram');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api-routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PollCommand::class,
                WebhookCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'telegram');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        $this->app->register(ModuleServiceProvider::class);
    }
}
