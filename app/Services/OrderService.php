<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Product;
use App\Models\ProductTopping;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class OrderService
{

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

      // Cập nhật danh sách sản phẩm trong đơn hàng
      if (!empty($data['items'])) {
        $this->updateOrderItems($order, $data['items']);
      }

      // Xử lý voucher nếu có
      $discountAmount = 0;
      if (!empty($data['voucher_code'])) {
        $discountAmount = $this->applyVoucher($order, $data['voucher_code']);
      }

      // Cập nhật tổng tiền đơn hàng
      $order->refresh();
      $order->total_price = $this->calculateOrderTotal($order) - $discountAmount;

      $order->save();

      return $order;
    });
  }

  /**
   * Cập nhật danh sách sản phẩm trong đơn hàng
   */
  private function updateOrderItems(Order $order, array $items)
  {
    // Xóa các mục cũ nếu cập nhật
    OrderItem::where('order_id', $order->id)->delete();

    foreach ($items as $item) {
      $unit_price = $this->getProductPrice($item['product_id']);
      $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $item['product_id'],
        'quantity' => $item['quantity'],
        'unit_price' => $unit_price,
        'total_price' => $unit_price * $item['quantity']
      ]);

      // Xử lý topping nếu có
      if (!empty($item['toppings'])) {
        $this->updateOrderToppings($orderItem, $item['product_id'], $item['toppings']);
      }
    }
  }

  /**
   * Cập nhật danh sách topping của sản phẩm trong đơn hàng
   */
  private function updateOrderToppings(OrderItem $orderItem, $productId, array $toppings)
  {
    $validToppings = ProductTopping::where('product_id', $productId)->pluck('topping_id')->toArray();

    foreach ($toppings as $toppingId) {
      if (!in_array($toppingId, $validToppings)) {
        continue; // Bỏ qua nếu topping không thuộc về sản phẩm
      }

      $toppingPrice = $this->getToppingPrice($toppingId);

      OrderTopping::create([
        'order_item_id' => $orderItem->id,
        'topping_id' => $toppingId,
        'unit_price' => $toppingPrice,
      ]);
    }
  }

  /**
   * Áp dụng voucher vào đơn hàng
   */
  private function applyVoucher(Order $order, $voucherCode)
  {
    $voucher = Voucher::where('code', $voucherCode)
      ->where('start_date', '<=', now())
      ->where('end_date', '>=', now())
      ->where('remaining_quantity', '>', 0)
      ->first();

    if (!$voucher) {
      return 0; // Không áp dụng nếu không tìm thấy voucher hợp lệ
    }

    $discountAmount = 0;
    $total = $this->calculateOrderTotal($order);

    if ($voucher->discount_type === 'fixed') {
      $discountAmount = min($voucher->discount_value, $total);
    } elseif ($voucher->discount_type === 'percentage') {
      $discountAmount = min(($total * ($voucher->discount_value / 100)), $total);
    }

    // Giảm số lượng voucher có thể sử dụng
    $voucher->decrement('remaining_quantity');

    // Lưu thông tin voucher vào đơn hàng
    $order->voucher_id = $voucher->id;
    $order->voucher_code = $voucher->code;
    $order->discount_amount = $discountAmount;
    $order->save();

    return $discountAmount;
  }

  /**
   * Tính tổng tiền đơn hàng
   */
  private function calculateOrderTotal(Order $order)
  {
    if ($order->items->isEmpty()) {
      return 0;
    }

    return $order->items->sum(function ($item) {
      $toppingTotal = $item->toppings ? $item->toppings->sum(fn($t) => $t->unit_price) : 0;
      return ($item->unit_price * $item->quantity) + $toppingTotal;
    });
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

  /**
   * Xác nhận đơn hàng
   */
  public function confirmOrder($orderId)
  {
    return $this->updateOrderStatus($orderId, 'confirmed');
  }

  /**
   * Hủy đơn hàng
   */
  public function cancelOrder($orderId)
  {
    return $this->updateOrderStatus($orderId, 'canceled');
  }

  /**
   * Hoàn tất đơn hàng
   */
  public function markAsCompleted($orderId, $paidAmount = 0)
  {
    return DB::transaction(function () use ($orderId, $paidAmount) {
      $order = $this->updateOrderStatus($orderId, 'completed');

      // Gọi `InvoiceService` để tạo hóa đơn
      $invoiceService = new InvoiceService();
      $invoiceService->createInvoiceFromOrder($orderId, $paidAmount);

      return $order;
    });
  }

  /**
   * Cập nhật trạng thái đơn hàng
   */
  public function updateOrderStatus($orderId, $status)
  {
    $order = Order::findOrFail($orderId);
    $order->order_status = $status;
    $order->save();

    return $order;
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
}
