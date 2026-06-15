<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;

class TelegramLink extends Component
{
    public function render()
    {
        $user = auth()->user();
        
        // If not linked and no token, generate one
        if (empty($user->telegram_chat_id) && empty($user->telegram_link_token)) {
            $user->telegram_link_token = Str::random(32);
            $user->save();
        }

        return view('livewire.telegram-link', [
            'user' => $user,
            'botUsername' => config('services.telegram.bot_username', 'exchange_adbtrk_bot'),
        ]);
    }

    public function unlink()
    {
        $user = auth()->user();
        $user->telegram_chat_id = null;
        $user->telegram_link_token = Str::random(32);
        $user->save();
        
        $this->dispatch('telegram-unlinked');
    }
}
