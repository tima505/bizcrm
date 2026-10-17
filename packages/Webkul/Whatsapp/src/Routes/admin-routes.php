<?php

use Illuminate\Support\Facades\Route;
use Webkul\Whatsapp\Http\Controllers\Admin\MessageController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path').'/messages')
    ->group(function () {
        Route::get('', [MessageController::class, 'index'])->name('admin.messages.index');
        Route::get('thread', [MessageController::class, 'thread'])->name('admin.messages.thread');
        Route::post('send', [MessageController::class, 'send'])->name('admin.messages.send');
    });
