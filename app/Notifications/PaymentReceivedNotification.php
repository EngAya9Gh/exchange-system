<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public $amount;
    public $method;
    public $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct($amount, $method, $notes = null)
    {
        $this->amount = $amount;
        $this->method = $method;
        $this->notes = $notes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'amount' => $this->amount,
            'method' => $this->method,
            'notes' => $this->notes,
            'message' => 'تم استلام دفعة بقيمة ' . number_format($this->amount, 2) . ' عبر ' . $this->method,
        ];
    }
}
