<?php

use Illuminate\Support\Facades\Route;
use App\Models\Transfer;

Route::redirect('/', '/login');

Route::group([
    'prefix' => \Mcamara\LaravelLocalization\Facades\LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
], function () {

    Route::middleware(['auth', 'verified'])->group(function () {
        // Customer routes
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::view('profile', 'profile')->name('profile');

        // Admin routes
        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::view('dashboard', 'admin.dashboard')->name('dashboard');
        });
    });

});

// Public verification route for QR codes
Route::get('transfers/verify/{number}', function ($number) {
    $transfer = Transfer::with(['region', 'branch'])->where('transfer_number', $number)->firstOrFail();
    return view('transfers.verify', compact('transfer'));
})->name('transfers.verify');

// Public route to view receipt as a web page
Route::get('receipts/{number}', function ($number) {
    $transfer = Transfer::where('transfer_number', $number)->firstOrFail();
    
    $currencyName = 'عملة';
    switch (strtoupper($transfer->target_currency)) {
        case 'EGP': $currencyName = 'جنيه مصري'; break;
        case 'TRY': $currencyName = 'ليرة تركية'; break;
        case 'USD': $currencyName = 'دولار أمريكي'; break;
        case 'EUR': $currencyName = 'يورو'; break;
    }

    $amountInWords = \App\Helpers\ArabicNumberToWords::convert((float) $transfer->received_amount, $currencyName);

    $theme = request('theme', '1');
    if ($theme == '2') {
        return view('receipts.transfer_v2', compact('transfer', 'amountInWords'));
    }

    return view('receipts.transfer', compact('transfer', 'amountInWords'));
})->name('receipt.view');

require __DIR__.'/auth.php';
