<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DepositRequest;
use Illuminate\Support\Facades\DB;

class DepositRequests extends Component
{
    use WithPagination;

    public $statusFilter = 'pending';

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function approve($id)
    {
        $request = DepositRequest::findOrFail($id);
        
        if ($request->status === 'pending') {
            DB::transaction(function () use ($request) {
                $request->status = 'approved';
                $request->save();

                // Add balance to user
                $request->user->balance += $request->amount;
                $request->user->save();
            });

            session()->flash('success', 'تم قبول الطلب وإضافة الرصيد لحساب الزبون بنجاح.');
        }
    }

    public function reject($id)
    {
        $request = DepositRequest::findOrFail($id);
        
        if ($request->status === 'pending') {
            $request->status = 'rejected';
            $request->save();
            
            session()->flash('success', 'تم رفض الطلب.');
        }
    }

    public function render()
    {
        $requests = DepositRequest::with('user')
            ->when($this->statusFilter !== 'all', function($q) {
                $q->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.deposit-requests', compact('requests'));
    }
}
