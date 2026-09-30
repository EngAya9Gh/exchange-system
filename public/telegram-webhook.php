<?php

/**
 * Standalone Telegram Webhook Entry Point
 * 
 * This file bypasses ALL Laravel middleware (CSRF, Auth, 2FA, Session, etc.)
 * It bootstraps Laravel just enough to run the TelegramWebhookController.
 * 
 * The webhook URL registered with Telegram should point to:
 * https://vcmoneytransfer.com/telegram-webhook.php
 */

// Bootstrap Laravel
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Build a fake request from PHP globals
$request = Illuminate\Http\Request::capture();

// Force the route to match our controller without going through the full router
try {
    $app->boot();
    $controller = $app->make(\App\Http\Controllers\TelegramWebhookController::class);
    $response = $controller->handle($request);
} catch (\Throwable $e) {
    \Illuminate\Support\Facades\Log::error('Telegram webhook fatal error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']); // Always return 200 to Telegram
    exit;
}

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
