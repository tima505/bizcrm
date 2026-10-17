<?php

use Illuminate\Support\Facades\Route;
use Webkul\Whatsapp\Http\Controllers\Api\IncomingController;
use Webkul\Whatsapp\Http\Middleware\VerifyWhatsappSecret;

Route::middleware(['api', VerifyWhatsappSecret::class])
    ->prefix('api/whatsapp')
    ->group(function () {
        Route::post('incoming', IncomingController::class)->name('api.whatsapp.incoming');
    });
