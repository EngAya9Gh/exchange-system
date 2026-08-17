<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BillPayment;

class BillPaymentsHistory extends Component
{
    use WithPagination;

    public $statusFilter = 'all';
    public $searchQuery = '';

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('Super Admin') || $user->role === 'admin';

        $query = BillPayment::with('user')->latest();

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('api_status', $this->statusFilter);
        }

        if (!empty($this->searchQuery)) {
            $query->where(function($q) {
                $q->where('abone_no', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('tahsilat_api_islem_id', 'like', '%' . $this->searchQuery . '%')
                  ->orWhereHas('user', function($userQ) {
                      $userQ->where('name', 'like', '%' . $this->searchQuery . '%');
                  });
            });
        }

        $bills = $query->paginate(15);

        return view('livewire.admin.bill-payments-history', [
            'bills' => $bills
        ])->layout('layouts.app'); // or 'layouts.admin' depending on standard
    }
}
