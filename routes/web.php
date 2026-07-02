<?php

use Illuminate\Support\Facades\Route;
use App\Models\Transfer;

Route::redirect('/', '/login');

Route::group([
    'prefix' => \Mcamara\LaravelLocalization\Facades\LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
], function () {

    \Livewire\Livewire::setUpdateRoute(function ($handle) {
        return Route::post('/livewire-e92fe52f/update', $handle);
    });

    // Public Promotional Rates Page
    Route::get('rates', \App\Livewire\Public\ExchangeRates::class)->name('rates.public');
    // Also add an alias for /egp
    Route::get('egp', \App\Livewire\Public\ExchangeRates::class)->name('egp.public');

    Route::middleware(['auth', 'verified'])->group(function () {
        // Customer routes
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::view('profile', 'profile')->name('profile');
        Route::get('my-payments', \App\Livewire\Customer\CustomerPayments::class)->name('customer.payments');

        // Admin routes
        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::view('dashboard', 'admin.dashboard')->name('dashboard');
            Route::get('deliver/{number}', \App\Livewire\Admin\DeliverTransfer::class)->name('transfers.deliver');
            Route::get('deposit-requests', \App\Livewire\Admin\DepositRequests::class)->name('deposit-requests');
            Route::get('balance-management', \App\Livewire\Admin\BalanceManagement::class)->name('balance-management');
            Route::get('payments-log', \App\Livewire\Admin\PaymentsLog::class)->name('payments-log');
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

use App\Http\Controllers\TelegramWebhookController;

// Telegram Webhook
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle'])
    ->name('webhook.telegram')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Fallback route to force logout if JS is broken
Route::get('/force-logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('force-logout');

require __DIR__.'/auth.php';
