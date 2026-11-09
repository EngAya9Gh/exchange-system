<?php

namespace App\Livewire\Public;

use Livewire\Component;

/**
 * Public "check your package balance" page (no login), shareable for marketing.
 * Embeds the supplier's query page; a "buy a package" WhatsApp button and a dealer login link.
 */
class SimBalance extends Component
{
    public function render()
    {
        return view('livewire.public.sim-balance')->layout('layouts.public');
    }
}
