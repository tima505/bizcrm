<?php

use Illuminate\Support\Facades\Route;
use Webkul\Telegram\Http\Controllers\Api\WebhookController;
use Webkul\Telegram\Http\Middleware\VerifyTelegramSecret;

Route::middleware(['api', VerifyTelegramSecret::class])
    ->prefix('api/telegram')
    ->group(function () {
        Route::post('webhook', WebhookController::class)->name('api.telegram.webhook');
    });
