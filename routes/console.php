<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\ExchangeRateService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تم إيقاف التحديث التلقائي لأسعار الصرف عبر الكرون للحفاظ على التسعير اليدوي
// Schedule::call(function () {
//     app(ExchangeRateService::class)->syncAllRates();
// })->hourly();
