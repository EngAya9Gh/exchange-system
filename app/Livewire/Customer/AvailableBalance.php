<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\On;

class AvailableBalance extends Component
{
    #[On('request-created')]
    public function render()
    {
        return view('livewire.customer.available-balance');
    }
}
