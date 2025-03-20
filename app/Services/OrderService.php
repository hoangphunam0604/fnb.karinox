<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Product;
use App\Models\ProductTopping;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
  protected PointService $pointService;
  protected VoucherService $voucherService;
  protected InvoiceService $invoiceService;

  public function __construct(PointService $pointService, VoucherService $voucherService, InvoiceService $invoiceService)
  {
    $this->pointService = $pointService;
    $this->voucherService = $voucherService;
    $this->invoiceService = $invoiceService;
  }
  public function getOrderByTableId(int $tableId)
  {
    $order = Order::where('table_id', $tableId)
      ->where('order_status', OrderStatus::PENDING)
      ->with(['items.toppings', 'customer.membershipLevel'])
      ->first();

    if (!$order) {
      $branchId = app()->bound('branch_id') ? app('branch_id') : null;

      $order = Order::create([
        'table_id' => $tableId,
        'branch_id' => $branchId,
        'order_status' => OrderStatus::PENDING,
        'total_price' => 0,
      ]);
      $order->loadMissing(['items.toppings', 'customer.membershipLevel']);
    }

    return $order;
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderByCode($code)
  {
    return Order::with(['items.toppings', 'customer.membershipLevel'])->where('order_code', strtoupper($code))->first();
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderById($id)
  {
    return Order::with(['items.toppings', 'customer.membershipLevel'])->where('id', $id)->first();
  }
  /**
   * Lấy danh sách đơn đặt hàng (phân trang)
   */
  public function getOrders($perPage = 10)
  {
    return Order::orderBy('created_at', 'desc')->paginate($perPage);
  }


  /**
   * Tạo đơn đặt hàng
   */
  public function createOrder($data)
  {
    return $this->saveOrder($data);
  }

  /**
   * Cập nhật đơn đặt hàng
   */
  public function updateOrder($orderId, $data)
  {
    return $this->saveOrder($data, $orderId);
  }

  /**
   * Tạo hoặc cập nhật đơn đặt hàng
   */
  public function saveOrder(array $data, $orderId = null)
  {
    return DB::transaction(function () use ($data, $orderId) {
      $order = $this->prepareOrder($data, $orderId);

      if (!empty($data['items'])) {
        $this->updateOrderItems($order, $data['items']);
      } else {
        // Nếu không có sản phẩm, xóa tất cả sản phẩm cũ
        OrderItem::where('order_id', $order->id)->delete();
      }

      $order->refresh();
      // 1️⃣ Tính tổng tiền ban đầu (chưa áp dụng giảm giá)
      $this->updateTotalPrice($order);

      $order->refresh();
      $this->applyDiscounts($order, $data);
      $order->refresh();
      $order->loadMissing(['customer.membershipLevel']);
      return $order;
    });
  }

  /**
   * Tính tiền đơn hàng
   */
  public function updateTotalPrice(Order $order): void
  {
    // 1️⃣ Tính tổng tiền sản phẩm & topping (trước giảm giá)
    $subtotal = $order->items->sum(fn($item) => $item->total_price);

    // 2️⃣ Lấy số tiền giảm giá từ voucher (nếu có)
    $discountAmount = $order->discount_amount ?? 0;

    // 3️⃣ Lấy số tiền giảm từ điểm thưởng (nếu có)
    $rewardDiscount = $order->reward_discount ?? 0;

    // 4️⃣ Tính tổng tiền cần thanh toán
    $totalPrice = max($subtotal - $discountAmount - $rewardDiscount, 0);

    // 5️⃣ Cập nhật vào đơn hàng
    $order->update([
      'subtotal_price' => $subtotal,
      'discount_amount' => $discountAmount,
      'reward_discount' => $rewardDiscount,
      'total_price' => $totalPrice
    ]);
  }


  /**
   * Xác nhận đơn hàng
   */
  public function confirmOrder($orderId): Order
  {
    return $this->updateOrderStatus($orderId, OrderStatus::CONFIRMED);
  }

  /**
   * Hủy đơn hàng
   */
  public function cancelOrder($orderId): Order
  {
    $order = Order::findOrFail($orderId);
    if ($order->order_status == OrderStatus::COMPLETED)
      throw new Exception('Hoá đơn đã được hoàn thành, không thể huỷ');

    return  $this->updateOrderStatus($orderId, OrderStatus::CANCELED);
  }

  /**
   * Hoàn tất đơn hàng
   */
  public function markAsCompleted($orderId): Order
  {
    return DB::transaction(function () use ($orderId) {
      $order = Order::findOrFail($orderId);

      if ($order->order_status !== OrderStatus::COMPLETED) {
        $order->markAsCompleted();
      }
      /* 
      if ($order->total_price > 0 && $paidAmount < $order->total_price) {
        throw new Exception('Số tiền thanh toán không đủ.');
      } */

      $order->refresh();

      $order->loadMissing(['items.toppings', 'customer.membershipLevel']);
      return $order;
    });
  }

  /**
   * Cập nhật trạng thái đơn hàng
   */
  public function updateOrderStatus(int $orderId, OrderStatus $status): Order
  {
    $order = Order::findOrFail($orderId);
    $order->update(['order_status' => $status]);
    return $order;
  }


  /**
   * Kiểm tra và áp dụng điểm thưởng
   */
  public function applyRewardPoints(Order $order, int $requestedPoints): Order
  {
    if (!$order->customer || $requestedPoints < 0) {
      return $order;
    }
    // Kiểm tra và Áp dụng điểm thưởng nếu có
    $this->pointService->useRewardPoints($order, $requestedPoints ?? 0);

    // 4️⃣ Cập nhật tổng tiền sau khi  trừ điểm thưởng
    $this->updateTotalPrice($order);
    $order->refresh();
    return $order;
  }

  public function removeCustomer($orderId): Order
  {
    $order = Order::findOrFail($orderId);
    $this->pointService->restoreTransactionRewardPoints($order);
    $order->customer_id = null;
    $order->save();
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel']);
    return $order;
  }

  public function removeRewardPointsUsed($orderId): Order
  {
    $order = Order::findOrFail($orderId);
    $this->pointService->restoreTransactionRewardPoints($order);
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel']);
    return $order;
  }

  public function removeVoucherUsed($orderId): Order
  {
    $order = Order::findOrFail($orderId);
    $this->voucherService->restoreVoucherUsage($order);
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel']);
    return $order;
  }

  /**
   * Chuẩn bị đơn hàng
   */
  private function prepareOrder(array $data, $orderId = null): Order
  {
    $order = $orderId ? Order::findOrFail($orderId) : new Order();

    $order->fill([
      'customer_id' => $data['customer_id'] ?? $order->customer_id,
      'receiver_id' => $data['receiver_id'] ?? $order->receiver_id,
      'branch_id' => $data['branch_id'] ?? $order->branch_id,
      'table_id' => $data['table_id'] ?? $order->table_id,
      'order_status' => $data['order_status'] ?? OrderStatus::PENDING,
      'payment_method' => $data['payment_method'] ?? 'cash',
      'note' => $data['note'] ?? $order->note,
    ]);

    $order->save();
    return $order;
  }

  /**
   * Cập nhật danh sách sản phẩm trong đơn hàng
   */
  private function updateOrderItems(Order $order, array $items)
  {
    $orderItemIds = [];

    foreach ($items as $item) {
      $product = $this->getProduct($item['product_id']);
      $unitPrice = $product->price;
      if (isset($item['id'])):
        $orderItem = OrderItem::findOrFail($item['id']);
        $orderItem->quantity = $item['quantity'] ?? 1;
        $orderItem->total_price = $unitPrice * $orderItem->quantity;

        $orderItem->total_price = $unitPrice * $orderItem->quantity;
        $orderItem->note = $item['note'];
        $orderItem->save();
        // Xử lý topping
        if (!empty($item['toppings'])) {
          $this->updateOrderToppings($orderItem, $item['product_id'], $item['toppings']);
        } else {
          // Nếu không có topping mới, xóa tất cả topping cũ
          OrderTopping::where('order_item_id', $orderItem->id)->delete();
        }
      else:
        $orderItem = OrderItem::where('product_id', $item['product_id'])
          ->where('order_id', $order->id)
          ->whereDoesntHave('toppings')
          ->first();
        if ($orderItem) :
          // Nếu item có sẵn và không có topping bị xóa, tăng số lượng
          $orderItem->increment('quantity');
          $orderItem->total_price = $unitPrice * $orderItem->quantity;
          $orderItem->save();
        else :
          $orderItem = OrderItem::create(
            [
              'order_id' => $order->id,
              'product_id' => $product->id,
              'product_name' => $product->name,
              'quantity' => $item['quantity'] ?? 1,
              'unit_price' => $unitPrice,
              'total_price' => $unitPrice  * $item['quantity'],
            ]
          );
        endif;
      endif;

      $orderItemIds[] = $orderItem->id;
    }

    // Xóa các mục không có trong danh sách mới
    OrderItem::where('order_id', $order->id)->whereNotIn('id', $orderItemIds)->delete();
  }


  /**
   * Cập nhật danh sách topping của sản phẩm trong đơn hàng
   */
  private function updateOrderToppings(OrderItem $orderItem, $productId, array $toppings)
  {
    $validToppings = ProductTopping::where('product_id', $productId)->pluck('topping_id')->toArray();
    $toppingIds = [];

    foreach ($toppings as $topping) {
      $toppingId = $topping['topping_id'] ?? null;
      $quantity = $topping['quantity'] ?? 1;

      if (!$toppingId || !in_array($toppingId, $validToppings) || $quantity <= 0) {
        continue;
      }

      $topping = $this->getTopping($toppingId);
      $topping_name = $topping->name;
      $unitPrice = $topping->price;
      $totalPrice = $unitPrice * $quantity;

      $orderTopping = OrderTopping::updateOrCreate(
        ['order_item_id' => $orderItem->id, 'topping_id' => $toppingId],
        ['quantity' => $quantity, 'topping_name' => $topping_name, 'unit_price' => $unitPrice, 'total_price' => $totalPrice]
      );

      $toppingIds[] = $orderTopping->id;
    }

    // Xóa các topping không còn trong danh sách mới
    OrderTopping::where('order_item_id', $orderItem->id)->whereNotIn('id', $toppingIds)->delete();

    // Cập nhật tổng tiền sản phẩm kèm topping
    $orderItem->unit_price = $orderItem->product_price + $orderItem->toppings->sum('total_price');
    $orderItem->total_price = $orderItem->unit_price * $orderItem->quantity;
    $orderItem->save();
  }

  /**
   * Áp dụng giảm giá từ voucher và điểm thưởng
   */
  private function applyDiscounts(Order $order, array $data): void
  {
    // 2️⃣ Áp dụng voucher nếu có
    if (!empty($data['voucher_code']) && !$order->discount_amount) {
      $order->refresh();
      $this->voucherService->applyVoucher($order, $data['voucher_code']);
    }
    // 3️⃣ Áp dụng điểm thưởng nếu có
    if (!empty($data['reward_points_used']) && !$order->reward_points_used) {
      $order->refresh();
      $this->pointService->useRewardPoints($order, $data['reward_points_used']);
    }

    $order->refresh();
    // 4️⃣ Cập nhật tổng tiền sau khi giảm giá & trừ điểm thưởng
    $this->updateTotalPrice($order);
  }

  /**
   * Lấy giá sản phẩm từ database
   */
  private function getProduct($productId)
  {
    return Product::findOrFail($productId);
  }

  /**
   * Lấy giá topping từ database
   */
  private function getTopping($toppingId)
  {
    return $this->getProduct($toppingId);
  }
}
