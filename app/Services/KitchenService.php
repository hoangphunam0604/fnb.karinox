<?php

namespace App\Services;

use App\Enums\KitchenTicketStatus;
use App\Enums\OrderItemStatus;
use App\Enums\UserRole;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Events\KitchenOrderReady;
use App\Events\KitchenTicketUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class KitchenService
{

  /**
   * Lấy danh sách vé bếp theo chi nhánh và trạng thái
   */
  public function getTickets(int $branchId, ?KitchenTicketStatus $status = null, int $perPage = 10): LengthAwarePaginator
  {
    $query = KitchenTicket::where('branch_id', $branchId)
      ->with('items');

    // Mặc định chỉ hiển thị vé chưa hoàn thành
    if ($status) {
      $query->where('status', $status);
    } else {
      $query->where('status', '!=', KitchenTicketStatus::COMPLETED);
    }

    return $query->paginate($perPage);
  }

  public function addItemsToKitchen(Order $order)
  {
    return DB::transaction(function () use ($order) {
      // Kiểm tra nếu đơn hàng không có món => Không tạo vé bếp
      if ($order->items()->count() === 0) {
        return null;
      }


      // Tìm vé bếp hiện tại của bàn, nếu chưa có thì tạo mới
      $ticket = KitchenTicket::firstOrCreate([
        'order_id' => $order->id,
        'branch_id' => $order->branch_id,
        'table_id' => $order->table_id,
      ], [
        'status' => 'waiting',
        'created_by' => Auth::id(),
      ]);

      // Lọc ra các món mới chưa có trong vé bếp
      $existingItemIds = $ticket->items()->pluck('order_item_id')->toArray();
      $newItems = $order->items->reject(fn($item) => in_array($item->id, $existingItemIds));

      if ($newItems->isEmpty()) {
        return $ticket; // Không có món mới
      }

      // Thêm món mới vào vé bếp
      $itemsData = $newItems->map(function ($item) use ($ticket) {
        return [
          'kitchen_ticket_id' => $ticket->id,
          'order_item_id' => $item->id,
          'product_id' => $item->product_id,
          'quantity' => $item->quantity,
          'note' => $item->note,
          'status' => 'waiting',
          'created_at' => now(),
          'updated_at' => now(),
        ];
      });

      KitchenTicketItem::insert($itemsData->toArray());

      $ticket->load('items'); // Load lại các món mới vào vé bếp
      // 🔴 **Phát sự kiện sau khi cập nhật vé bếp**
      broadcast(new KitchenTicketUpdated($ticket))->toOthers();
      return $ticket;
    });
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
    return KitchenTicket::where('id', $ticketId)->delete();
  }


  /**
   * Cập nhật trạng thái món ăn trong vé bếp
   */
  public function updateItemStatus(int $itemId, KitchenTicketStatus $status): void
  {
    $item = KitchenTicketItem::select('id', 'kitchen_ticket_id')
      ->where('id', $itemId)
      ->where('status', '!=', KitchenTicketStatus::COMPLETED)
      ->first();

    //Bỏ qua nếu không có vé hoặc trùng trạng thái
    if (!$item || $item->status === $status) {
      return;
    }

    $item->update(['status' => $status]);

    // Kiểm tra nếu tất cả món đã hoàn tất, cập nhật trạng thái vé bếp
    if (!KitchenTicketItem::where('kitchen_ticket_id', $item->kitchen_ticket_id)
      ->where('status', '!=', KitchenTicketStatus::COMPLETED)
      ->exists()) {
      KitchenTicket::where('id', $item->kitchen_ticket_id)
        ->update(['status' => KitchenTicketStatus::COMPLETED]);
    }
  }


  /**
   * Nhận món (Cập nhật nhân viên tiếp nhận)
   */
  public function acceptTicket(int $ticketId, int $userId): void
  {

    $user = User::findOrFail($userId);
    abort_if(!$user->hasRole(UserRole::KITCHEN_STAFF), 403, "Chỉ nhân viên bếp mới có thể nhận món.");
    DB::transaction(function () use ($ticketId, $userId) {

      $ticket = KitchenTicket::where('id', $ticketId)->firstOrFail();
      if ($ticket->accepted_by) {
        abort(403, "Vé bếp này không thể được nhận vì đã được tiếp nhận bởi: {$ticket->acceptedBy->fullname}.");
      }
      if ($ticket->status !== KitchenTicketStatus::WAITING) {
        abort(403, "Vé bếp này không thể được nhận vì đang ở trạng thái: {$ticket->status->value}.");
      }

      if ($ticket->status !== KitchenTicketStatus::WAITING) {
        abort(403, "Vé bếp này không thể được nhận vì đang ở trạng thái: {$ticket->status->value}.");
      }


      $ticket->update([
        'accepted_by' => $userId,
        'status' => KitchenTicketStatus::PROCESSING,
      ]);

      $orderItemIds = $ticket->items->pluck('order_item_id');

      KitchenTicketItem::where('kitchen_ticket_id', $ticket->id)
        ->update(['status' => KitchenTicketStatus::PROCESSING]);
      if ($orderItemIds->isNotEmpty()) {
        OrderItem::whereIn('id', $orderItemIds)
          ->update(['status' => OrderItemStatus::ACCEPTED]);
      }
    });
  }


  /**
   * Gửi thông báo cho nhân viên phục vụ khi món đã sẵn sàng
   */
  public function notifyWaiter(int $ticketId): void
  {
    $ticket = KitchenTicket::where('id', $ticketId)
      ->where('status', '!=', KitchenTicketStatus::COMPLETED)
      ->first();

    if (!$ticket) {
      return;
    }
    // Lấy danh sách waiter đang online (chỉ lấy ID)
    $waiterIds = User::role(UserRole::WAITER)
      ->where('is_active', true)
      ->whereNotNull('last_seen_at')
      ->pluck('id')
      ->toArray();

    if (empty($waiterIds)) {
      return;
    }

    broadcast(new KitchenOrderReady($ticket, $waiterIds));
  }
}
