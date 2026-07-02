<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Payment;

class PaymentsLog extends Component
{
    use WithPagination;

    public $searchQuery = '';
    public $dateFrom = '';
    public $dateTo = '';

    public function updatingSearchQuery()
    {
        $this->resetPage();
    }

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
        $payments = Payment::with(['user', 'admin'])
            ->when($this->searchQuery, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchQuery . '%')
                      ->orWhere('phone', 'like', '%' . $this->searchQuery . '%');
                });
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.payments-log', compact('payments'))
            ->layout('layouts.app');
    }
}
