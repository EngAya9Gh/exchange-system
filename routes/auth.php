<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('register', 'pages.auth.register')
        ->name('register');

    Route::view('login', 'livewire.pages.auth.login')
        ->name('login');

    Route::post('login', [\App\Http\Controllers\Auth\LoginController::class, 'store']);

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('verify-otp', [\App\Http\Controllers\Auth\OtpVerificationController::class, 'show'])->name('otp.verify');
    Route::post('verify-otp', [\App\Http\Controllers\Auth\OtpVerificationController::class, 'verify'])->name('otp.verify.post');
    Route::post('verify-otp/resend', [\App\Http\Controllers\Auth\OtpVerificationController::class, 'resend'])->name('otp.resend');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
});
