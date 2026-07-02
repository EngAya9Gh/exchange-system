<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\ExchangeRateService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تحديث أسعار الصرف تلقائياً كل ساعة
Schedule::call(function () {
    app(ExchangeRateService::class)->syncAllRates();
})->hourly();
