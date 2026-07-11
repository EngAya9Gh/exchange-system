<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Services\ExchangeRateService;
use App\Models\ExchangeRate;

class ExchangeRates extends Component
{
    public $amount = 100;
    public $egpAmount = 0;
    public $currency = 'TRY';
    public $egpRate = 0;
    public $usdRate = 0;
    public $lastUpdated = null;

    public function mount()
    {
        $this->fetchRates();
        $this->calculateEgp();
    }

    public function updatedCurrency()
    {
        $this->fetchRates();
        $this->calculateEgp();
    }

    public function updatedAmount()
    {
        $this->calculateEgp();
    }

    public function updatedEgpAmount()
    {
        $this->calculateSourceAmount();
    }

    public function calculateEgp()
    {
        if (is_numeric($this->amount)) {
            $this->egpAmount = round((float) $this->amount * $this->egpRate, 2);
        } else {
            $this->egpAmount = 0;
        }
    }

    public function calculateSourceAmount()
    {
        if (is_numeric($this->egpAmount) && $this->egpRate > 0) {
            $this->amount = round((float) $this->egpAmount / $this->egpRate, 2);
        } else {
            $this->amount = 0;
        }
    }

    public function fetchRates()
    {
        $rateService = app(ExchangeRateService::class);
        $this->egpRate = (float) $rateService->getRate($this->currency, 'EGP');
        $this->usdRate = (float) $rateService->getRate('USD', 'EGP');

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

    public function render()
    {
        return view('livewire.public.exchange-rates')->layout('layouts.public');
    }
}
