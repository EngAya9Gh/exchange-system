<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Services\ExchangeRateService;
use App\Models\ExchangeRate;

class ExchangeRates extends Component
{
    public $amount = 100;
    public $currency = 'TRY';
    public $egpRate = 0;
    public $lastUpdated = null;

    public function mount()
    {
        $this->fetchRates();
    }

    public function fetchRates()
    {
        $rateService = app(ExchangeRateService::class);
        $this->egpRate = (float) $rateService->getRate($this->currency, 'EGP');

        $rateRecord = ExchangeRate::where('from_currency', $this->currency)
            ->where('to_currency', 'EGP')
            ->latest('updated_at')
            ->first();

        if ($rateRecord && $rateRecord->updated_at) {
            $this->lastUpdated = $rateRecord->updated_at->diffForHumans();
        } else {
            $this->lastUpdated = 'الآن';
        }
    }

    public function getCalculatedEgpProperty()
    {
        return round((float) $this->amount * $this->egpRate, 2);
    }

    public function render()
    {
        return view('livewire.public.exchange-rates')->layout('layouts.public');
    }
}
