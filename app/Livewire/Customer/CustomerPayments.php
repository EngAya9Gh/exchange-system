<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Payment;

class CustomerPayments extends Component
{
    use WithPagination;

    public $dateFrom = '';
    public $dateTo = '';

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function render()
    {
        $payments = Payment::where('user_id', auth()->id())
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.customer.customer-payments', compact('payments'))
            ->layout('layouts.app');
    }
}
