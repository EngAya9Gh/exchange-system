<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;

// This file is loaded with ZERO middleware — no CSRF, no auth, no session, no 2FA
// It is the only reliable way to accept Telegram webhook callbacks on restricted servers
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle'])
    ->name('webhook.telegram');
