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

    Route::get('fatura-test-balance', function (\App\Services\FaturaApiService $api) {
        return response()->json($api->getDeposit());
    });

    // Public Promotional Rates Page
    Route::get('rates', \App\Livewire\Public\ExchangeRates::class)->name('rates.public');
    // Also add an alias for /egp
    Route::get('egp', \App\Livewire\Public\ExchangeRates::class)->name('egp.public');

    Route::middleware(['auth', 'verified'])->group(function () {
        // App Selection Route
        Route::view('apps', 'apps-selection')->name('apps');
        
        // Invoices System Route (accessible to all authenticated users as requested)
        Route::get('invoices', function () {
            if (auth()->user()->hasAnyRole(['Super Admin', 'Agent'])) {
                return redirect()->route('admin.dashboard');
            }
            return view('dashboard', ['systemContext' => 'invoices']);
        })->name('invoices.dashboard');
        
        // Customer routes
        Route::get('dashboard', function () {
            return view('dashboard', ['systemContext' => 'transfers']);
        })->name('dashboard');
        Route::view('profile', 'profile')->name('profile');
        Route::get('my-payments', \App\Livewire\Customer\CustomerPayments::class)->name('customer.payments');

        // Admin routes
        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::view('dashboard', 'admin.dashboard')->name('dashboard');
            Route::get('deliver/{number}', \App\Livewire\Admin\DeliverTransfer::class)->name('transfers.deliver');
            Route::get('deposit-requests', \App\Livewire\Admin\DepositRequests::class)->name('deposit-requests');
            Route::get('balance-management', \App\Livewire\Admin\BalanceManagement::class)->name('balance-management');
            Route::get('payments-log', \App\Livewire\Admin\PaymentsLog::class)->name('payments-log');
            Route::get('bill-payments-history', \App\Livewire\Admin\BillPaymentsHistory::class)->name('bill-payments-history');
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

// Public route to view bill payment receipt as a web page
Route::get('bill-receipts/{reference}', function ($reference) {
    $bill = \App\Models\BillPayment::with('user')->where('tahsilat_api_islem_id', $reference)->firstOrFail();
    
    $amountInWords = \App\Helpers\ArabicNumberToWords::convert((float) $bill->amount, 'ليرة تركية');

    return view('receipts.bill_payment', compact('bill', 'amountInWords'));
})->name('bill-receipt.view');

// 🛠️ مسار مؤقت لعرض الفاتورة ببيانات تجريبية لمعاينة التصميم
Route::get('bill-receipts-preview/test', function () {
    $dummyUser = new \App\Models\User(['name' => 'شركة الصرافة والتسديدات السريعة']);
    
    $bill = new \App\Models\BillPayment([
        'tahsilat_api_islem_id' => 'BILL_173456789_1234',
        'abone_no' => '5391234567',
        'fatura_no' => 'INV-987654321',
        'amount' => 1500.50,
        'commission' => 5.00,
        'total_deducted' => 1505.50,
        'api_status' => 'completed',
        'api_status_message' => 'تم الدفع',
    ]);
    
    // Set protected properties directly
    $bill->created_at = now();
    
    // ربط المستخدم الوهمي
    $bill->setRelation('user', $dummyUser);
    
    $amountInWords = \App\Helpers\ArabicNumberToWords::convert((float) $bill->amount, 'ليرة تركية');

    return view('receipts.bill_payment', compact('bill', 'amountInWords'));
});

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
