<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\KitchenTicket;

class KitchenOrderReadyNotification extends Notification implements ShouldQueue
{
  use Queueable;

  protected $ticket;

  /**
   * Create a new notification instance.
   */
  public function __construct(KitchenTicket $ticket)
  {
    $this->ticket = $ticket;
  }

  /**
   * Get the notification's delivery channels.
   */
  public function via($notifiable)
  {
    return ['database', 'broadcast'];
  }

  /**
   * Get the array representation of the notification for database storage.
   */
  public function toArray($notifiable)
  {
    return [
      'ticket_id' => $this->ticket->id,
      'order_id' => $this->ticket->order_id,
      'table_id' => $this->ticket->table_id,
      'message' => 'Vé bếp #' . $this->ticket->id . ' đã sẵn sàng để phục vụ.',
    ];
  }

  /**
   * Get the broadcastable representation of the notification.
   */
  public function toBroadcast($notifiable)
  {
    return new BroadcastMessage([
      'ticket_id' => $this->ticket->id,
      'order_id' => $this->ticket->order_id,
      'table_id' => $this->ticket->table_id,
      'message' => 'Vé bếp #' . $this->ticket->id . ' đã sẵn sàng để phục vụ.',
    ]);
  }
}
