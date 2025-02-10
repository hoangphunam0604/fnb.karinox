<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Product;
use App\Models\ProductTopping;
use Illuminate\Support\Facades\DB;

class OrderService
{
  /**
   * Tạo hoặc cập nhật đơn đặt hàng
   */
  public function saveOrder(array $data, $orderId = null)
  {
    return DB::transaction(function () use ($data, $orderId) {
      $order = $orderId ? Order::findOrFail($orderId) : new Order();

      $order->fill([
        'code' => $data['code'] ?? $order->code ?? strtoupper(uniqid('ORD')),
        'customer_id' => $data['customer_id'] ?? $order->customer_id,
        'branch_id' => $data['branch_id'] ?? $order->branch_id,
        'status' => $data['status'] ?? 'pending',
        'payment_status' => $data['payment_status'] ?? 'pending',
        'table_id' => $data['table_id'] ?? $order->table_id,
        'note' => $data['note'] ?? $order->note,
      ]);

      $order->save();

      // Cập nhật danh sách sản phẩm trong đơn hàng
      if (!empty($data['items'])) {
        $this->updateOrderItems($order, $data['items']);
      }

      // Cập nhật tổng tiền đơn hàng
      $order->total_price = $this->calculateOrderTotal($order);
      $order->save();

      return $order;
    });
  }

  /**
   *Tạo đơn đặt hàng
   */
  public function createOrder($data)
  {
    return $this->saveOrder($data);
  }

  /**
   *Cập nhật đơn đặt hàng
   */
  public function updateOrder($orderId, $data)
  {
    return $this->saveOrder($data, $orderId);
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
        'total_price' => $unit_price *  $item['quantity']
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
        'price' => $toppingPrice,
      ]);
    }
  }

  /**
   * Tính tổng tiền đơn hàng
   */
  private function calculateOrderTotal(Order $order)
  {
    return $order->items->sum(function ($item) {
      return ($item->unit_price * $item->quantity) + $item->toppings->sum(fn($t) => $t->unit_price);
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
  public function markAsCompleted($orderId)
  {
    return DB::transaction(function () use ($orderId) {
      $order = Order::findOrFail($orderId);
      $order->status = 'completed';
      $order->save();

      // Gọi `InvoiceService` để tạo hóa đơn
      $invoiceService = new InvoiceService();
      $invoiceService->createInvoiceFromOrder($orderId);

      return $order;
    });
  }

  /**
   * Cập nhật trạng thái đơn hàng
   */
  private function updateOrderStatus($orderId, $status)
  {
    $order = Order::findOrFail($orderId);
    $order->status = $status;
    $order->save();

    return $order;
  }

  /**
   * Cập nhật trạng thái thanh toán của đơn hàng
   */
  public function updatePaymentStatus($orderId, $paymentStatus)
  {
    $order = Order::findOrFail($orderId);
    $order->payment_status = $paymentStatus;
    $order->save();

    return $order;
  }

  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderByCode($code)
  {
    return Order::where('code', strtoupper($code))->first();
  }

  /**
   * Lấy danh sách đơn đặt hàng (phân trang)
   */
  public function getOrders($perPage = 10)
  {
    return Order::orderBy('created_at', 'desc')->paginate($perPage);
  }
}
