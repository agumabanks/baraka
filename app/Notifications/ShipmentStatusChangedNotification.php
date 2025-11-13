<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Shipment $shipment) {}

    public function via($notifiable): array
    {
        $channels = ['mail'];
        // Optional: extend with SMS/push based on user preferences
        if (is_array($notifiable->notification_prefs ?? null)) {
            $prefs = $notifiable->notification_prefs;
            if (! empty($prefs['sms'])) {
                // e.g., add a custom SMS channel here
            }
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $s = $this->shipment;
        $statusLabel = optional($s->current_status)->value ?? 'Unknown';

        return (new MailMessage)
            ->subject('Shipment #'.$s->id.' status updated to '.$statusLabel)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your shipment status has changed to: '.$statusLabel)
            ->line('Origin: '.($s->originBranch->name ?? '—').' | Destination: '.($s->destBranch->name ?? '—'))
            ->action('View in Portal', route('portal.index'))
            ->line('Thank you for choosing us.');
    }
}
