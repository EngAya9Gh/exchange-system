<?php

use App\Http\Controllers\Api\PartnerApiController;
use App\Http\Middleware\AuthenticatePartnerApi;
use Illuminate\Support\Facades\Route;

/*
 | Partner API v1 (documentation: /api-docs). Stateless JSON: no session, no CSRF, no locale prefix.
 | Every request is authenticated with the account's API key and limited to 60 requests / minute.
 */
Route::prefix('api/v1')
    ->middleware([AuthenticatePartnerApi::class, 'throttle:partner-api'])
    ->name('partner-api.')
    ->group(function () {
        Route::get('balance', [PartnerApiController::class, 'balance'])->name('balance');
        Route::get('packages', [PartnerApiController::class, 'packages'])->name('packages');
        Route::post('orders', [PartnerApiController::class, 'createOrder'])->name('orders.store');
        Route::get('orders/{id}', [PartnerApiController::class, 'showOrder'])->name('orders.show')
            ->where('id', '[A-Za-z0-9_.\-]+');
    });
