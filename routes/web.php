<?php

use Illuminate\Support\Facades\Route;
use App\Models\Transfer;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    // Customer routes
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    // Admin routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('dashboard', 'admin.dashboard')->name('dashboard');
    });
});

// Public verification route for QR codes
Route::get('transfers/verify/{number}', function ($number) {
    $transfer = Transfer::with(['region', 'branch'])->where('transfer_number', $number)->firstOrFail();
    return view('transfers.verify', compact('transfer'));
})->name('transfers.verify');

// Public route to view/download receipt PDF directly
Route::get('receipts/{number}', function ($number) {
    $path = 'receipts/' . $number . '.pdf';
    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        abort(404);
    }
    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->name('receipt.download');

require __DIR__.'/auth.php';
