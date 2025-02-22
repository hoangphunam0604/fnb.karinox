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

  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderByCode($code)
  {
    return Order::where('order_code', strtoupper($code))->first();
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

      return $order->refresh();
    });
  }

  /**
   * Tính tiền đơn hàng
   */
  public function updateTotalPrice(Order $order): void
  {
    // 1️⃣ Tính tổng tiền sản phẩm & topping (trước giảm giá)
    $subtotal = $order->items->sum(fn($item) => $item->total_price_with_topping);

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
  public function markAsCompleted($orderId, $paidAmount = 0): Order
  {
    return DB::transaction(function () use ($orderId, $paidAmount) {
      $order = Order::findOrFail($orderId);

      if ($order->order_status === OrderStatus::COMPLETED) {
        throw new Exception('Đơn hàng đã hoàn tất trước đó.');
      }

      if ($order->total_price > 0 && $paidAmount < $order->total_price) {
        throw new Exception('Số tiền thanh toán không đủ.');
      }
      $order->markAsCompleted();

      return $order->refresh();
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
      'order_status' => $data['order_status'] ?? 'pending',
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
      $unitPrice = $this->getProductPrice($item['product_id']);
      $orderItem = OrderItem::updateOrCreate(
        ['order_id' => $order->id, 'product_id' => $item['product_id']],
        [
          'quantity' => $item['quantity'],
          'unit_price' => $unitPrice,
          'total_price' => $unitPrice  * $item['quantity'],
          'total_price_with_topping' => $unitPrice  * $item['quantity'],
        ]
      );

      $orderItemIds[] = $orderItem->id;

      // Xử lý topping
      if (!empty($item['toppings'])) {
        $this->updateOrderToppings($orderItem, $item['product_id'], $item['toppings']);
      } else {
        // Nếu không có topping mới, xóa tất cả topping cũ
        OrderTopping::where('order_item_id', $orderItem->id)->delete();
      }
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

      $unitPrice = $this->getToppingPrice($toppingId);
      $totalPrice = $unitPrice * $quantity;

      $orderTopping = OrderTopping::updateOrCreate(
        ['order_item_id' => $orderItem->id, 'topping_id' => $toppingId],
        ['quantity' => $quantity, 'unit_price' => $unitPrice, 'total_price' => $totalPrice]
      );

      $toppingIds[] = $orderTopping->id;
    }

    // Xóa các topping không còn trong danh sách mới
    OrderTopping::where('order_item_id', $orderItem->id)->whereNotIn('id', $toppingIds)->delete();

    // Cập nhật tổng tiền sản phẩm kèm topping
    $orderItem->total_price_with_topping = $orderItem->toppings->sum('total_price') + $orderItem->total_price;
    $orderItem->save();
  }

  /**
   * Áp dụng giảm giá từ voucher và điểm thưởng
   */
  private function applyDiscounts(Order $order, array $data): void
  {
    // 2️⃣ Áp dụng voucher nếu có
    if (!empty($data['voucher_code'])) {
      $order->refresh();
      $this->voucherService->applyVoucherToOrder($order, $data['voucher_code']);
    }
    // 3️⃣ Áp dụng điểm thưởng nếu có
    if (!empty($data['reward_points_used'])) {
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
  private function getProductPrice($productId)
  {
    return Product::findOrFail($productId)->price;
  }

  /**
   * Lấy giá topping từ database
   */
  private function getToppingPrice($toppingId)
  {
    return $this->getProductPrice($toppingId);
  }
}
