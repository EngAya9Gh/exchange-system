<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationDropdown extends Component
{
    public $notifications = [];
    public $unreadCount = 0;
    public $isOpen = false;

    // Listen to events from other components (like when a new transfer is created)
    protected $listeners = ['request-created' => 'loadNotifications', 'transfer-created' => 'loadNotifications'];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (auth()->check()) {
            $this->notifications = auth()->user()->notifications()->take(15)->get();
            $this->unreadCount = auth()->user()->unreadNotifications()->count();
        }
    }

    public function toggleDropdown()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->loadNotifications();
        }
    }

    public function markAsRead($notificationId)
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            
            $transferId = $notification->data['transfer_id'] ?? $notification->data['request_id'] ?? null;
            if ($transferId && auth()->user()->role === 'admin') {
                $this->dispatch('open-transfer-receipt', id: $transferId);
            }
        }
        $this->loadNotifications();
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.notification-dropdown');
    }
}
