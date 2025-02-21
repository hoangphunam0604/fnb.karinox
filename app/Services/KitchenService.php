<?php

namespace App\Services;

use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\User;
use App\Notifications\KitchenOrderReadyNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class KitchenService
{
  /**
   * Tạo vé bếp cho đơn hàng
   */
  public function createTicket($order)
  {
    return DB::transaction(function () use ($order) {
      $ticket = KitchenTicket::create([
        'order_id' => $order->id,
        'branch_id' => $order->branch_id,
        'table_id' => $order->table_id,
        'status' => 'waiting',
        'created_by' => auth()->id(),
      ]);

      $order->items->each(function ($item) use ($ticket) {
        KitchenTicketItem::create([
          'kitchen_ticket_id' => $ticket->id,
          'order_item_id' => $item->id,
          'product_id' => $item->product_id,
          'quantity' => $item->quantity,
          'note' => $item->note,
          'status' => 'waiting',
        ]);
      });

      return $ticket;
    });
  }

  /**
   * Lấy danh sách vé bếp theo chi nhánh và trạng thái
   */
  public function getTickets(int $branchId, ?string $status = null)
  {
    return KitchenTicket::where('branch_id', $branchId)
      ->when($status, fn($query) => $query->where('status', $status))
      ->with('items')
      ->get();
  }

  /**
   * Lấy chi tiết vé bếp
   */
  public function getTicketById(int $ticketId): KitchenTicket
  {
    return KitchenTicket::with('items')->findOrFail($ticketId);
  }

  /**
   * Xóa vé bếp
   */
  public function deleteTicket(int $ticketId): bool
  {
    return KitchenTicket::findOrFail($ticketId)->delete();
  }

  /**
   * Cập nhật trạng thái món ăn trong vé bếp
   */
  public function updateItemStatus(int $itemId, string $status): void
  {
    $item = KitchenTicketItem::findOrFail($itemId);
    $item->update(['status' => $status]);

    // Nếu tất cả món đã hoàn tất, cập nhật vé bếp
    $this->updateTicketStatus($item->kitchen_ticket_id);
  }

  /**
   * Nhận món (Cập nhật nhân viên tiếp nhận)
   */
  public function acceptTicket(int $ticketId, int $userId): void
  {
    KitchenTicket::findOrFail($ticketId)->update([
      'accepted_by' => $userId,
      'status' => 'processing',
    ]);
  }

  /**
   * Cập nhật trạng thái vé bếp nếu tất cả món đã hoàn tất
   */
  private function updateTicketStatus(int $ticketId): void
  {
    $ticket = KitchenTicket::with('items')->findOrFail($ticketId);
    if ($ticket->items->every(fn($item) => $item->status === 'completed')) {
      $ticket->update(['status' => 'completed']);
    }
  }

  /**
   * Gửi thông báo cho nhân viên phục vụ khi món đã sẵn sàng
   */
  public function notifyWaiter(int $ticketId): void
  {
    $ticket = KitchenTicket::findOrFail($ticketId);
    $waiters = User::where('role', 'waiter')->get();

    Notification::send($waiters, new KitchenOrderReadyNotification($ticket));
  }
}
