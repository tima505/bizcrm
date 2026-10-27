<?php

use Illuminate\Support\Facades\Route;
use Webkul\Telegram\Http\Controllers\Admin\IntegrationController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path').'/settings/integrations')
    ->group(function () {
        Route::get('', [IntegrationController::class, 'index'])->name('admin.settings.integrations.index');
        Route::post('telegram/connect', [IntegrationController::class, 'connectTelegram'])->name('admin.settings.integrations.telegram.connect');
        Route::post('telegram/disconnect', [IntegrationController::class, 'disconnectTelegram'])->name('admin.settings.integrations.telegram.disconnect');
    });
