<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\Transfer;
use Livewire\Component;
use Livewire\WithPagination;

class RequestHistory extends Component
{
    use WithPagination;

    protected $listeners = ['request-created' => '$refresh'];

    public function render()
    {
        $requests = Transfer::where('user_id', auth()->id())
            ->latest()
            ->paginate(5);

        return view('livewire.customer.request-history', compact('requests'));
    }
}
