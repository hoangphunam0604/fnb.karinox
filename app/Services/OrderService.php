<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Product;
use App\Models\ProductTopping;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
  protected PointService $pointService;
  protected VoucherService $voucherService;
  protected InvoiceService $invoiceService;
  protected SystemSettingService $systemSettingService;
  protected StockDeductionService $stockDeductionService;

  public function __construct(
    PointService $pointService,
    VoucherService $voucherService,
    InvoiceService $invoiceService,
    SystemSettingService $systemSettingService,
    StockDeductionService $stockDeductionService
  ) {
    $this->pointService = $pointService;
    $this->voucherService = $voucherService;
    $this->invoiceService = $invoiceService;
    $this->systemSettingService = $systemSettingService;
    $this->stockDeductionService = $stockDeductionService;
  }
  public function getOrdersByTableId(int $tableId)
  {
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null;

    // Lấy tất cả các đơn hàng PENDING
    $orders = Order::where('table_id', $tableId)
      ->where('branch_id', $branchId)
      ->where('order_status', OrderStatus::PENDING)
      ->with(['items.toppings', 'customer.membershipLevel', 'table'])
      ->get();

    // Nếu chưa có đơn nào thì tạo mới
    if ($orders->isEmpty()) {
      $order = Order::create([
        'table_id' => $tableId,
        'branch_id' => $branchId,
        'order_status' => OrderStatus::PENDING,
        'total_price' => 0,
      ]);

      $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
      $orders->push($order);
    }

    return $orders;
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderByCode($code)
  {
    return Order::with(['items.toppings', 'customer.membershipLevel', 'table'])->where('code', strtoupper($code))->first();
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderById($id)
  {
    return Order::with(['items.toppings', 'customer.membershipLevel', 'table'])->where('id', $id)->first();
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
        $this->updateItems($order, $data['items']);
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
      $order->loadMissing(['customer.membershipLevel', 'table']);
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

    $this->pointService->restoreTransactionRewardPoints($order);
    $this->voucherService->restoreVoucherUsage($order);
    return $this->updateOrderStatus($orderId, OrderStatus::CANCELED);
  }

  public function prevPay(string $code, $payment_method)
  {

    $order = Order::where('code', $code)->firstOrFail();
    $order->payment_method = $payment_method;
    return $order;
  }

  public function pay(Order $order, string $payment_method = 'cash')
  {
    if ($order->payment_status === PaymentStatus::PAID) {
      // đã trả tiền rồi, không cần ghi đè
      return;
    }

    return DB::transaction(function () use ($order, $payment_method) {
      $oldStatus = $order->order_status;

      $order->paid_at = now();
      $order->payment_method = $payment_method;
      $order->payment_status = PaymentStatus::PAID;
      $order->order_status = OrderStatus::COMPLETED;
      $order->save();

      // Trừ kho khi đơn hàng chuyển sang COMPLETED
      if ($oldStatus !== OrderStatus::COMPLETED) {
        $this->deductStockForCompletedOrder($order);
      }
    });
  }
  /**
   * Hoàn tất đơn hàng
   */
  /* public function markAsCompleted($orderId): Order
  {
    return DB::transaction(function () use ($orderId) {
      $order = Order::findOrFail($orderId);

      if ($order->order_status !== OrderStatus::COMPLETED) {
        $order->markAsCompleted();
      }
      

      $order->refresh();
      return $order;
    });
  } */

  /**
   * Cập nhật trạng thái đơn hàng
   */
  public function updateOrderStatus(int $orderId, OrderStatus $status): Order
  {
    return DB::transaction(function () use ($orderId, $status) {
      $order = Order::findOrFail($orderId);
      $oldStatus = $order->order_status;

      $order->update(['order_status' => $status]);

      // Trừ kho khi đơn hàng chuyển sang COMPLETED
      if ($status === OrderStatus::COMPLETED && $oldStatus !== OrderStatus::COMPLETED) {
        $this->deductStockForCompletedOrder($order);
      }

      return $order;
    });
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
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  public function removeRewardPointsUsed($orderId): Order
  {
    $order = Order::findOrFail($orderId);
    $this->pointService->restoreTransactionRewardPoints($order);
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  public function removeVoucherUsed($orderId): Order
  {
    $order = Order::findOrFail($orderId);
    $this->voucherService->restoreVoucherUsage($order);
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  public function getPrintData($orderId)
  {
    return DB::transaction(function () use ($orderId) {
      $now = now()->toDateTimeString();
      $order = Order::with(['items.toppings', 'customer.membershipLevel', 'table'])
        ->findOrFail($orderId);

      // Cập nhật toàn bộ item chưa in tem
      $order->items()
        ->where('print_label', true)
        ->where('printed_label', false)
        ->update([
          'printed_label' => true,
          'printed_label_at' => $now,
        ]);

      // Cập nhật toàn bộ item chưa in phiếu bếp
      $order->items()
        ->where('print_kitchen', true)
        ->where('printed_kitchen', false)
        ->update([
          'printed_kitchen' => true,
          'printed_kitchen_at' => $now,
        ]);

      // Lấy lại dữ liệu để in (sau khi đã cập nhật)
      $allItems = $order->items()->with('toppings')->get();
      // Tách lại dữ liệu theo mục đích in (phân loại in trước/sau)
      $labels = $allItems->filter(fn($item) => optional($item->printed_label_at)?->toDateTimeString() === $now);
      $kitchenItems = $allItems->filter(fn($item) => optional($item->printed_kitchen_at)?->toDateTimeString() === $now);

      // Set lại danh sách items để in hoá đơn
      return [$order, $kitchenItems, $labels];
    });
  }
  /* 
  public function payment($orderId)
  {
    $order = Order::findOrFail($orderId);
    $now = now();
    $order->payment_started_at = $now;
    $order->paid_at = $now;
    $order->save();
    if ($order->order_status !== OrderStatus::COMPLETED) {
      $order->markAsCompleted();
    }
    return $this->notifyKitchen($orderId);
  } */

  public function extend($orderId, $oldOrderCode): Order
  {
    abort(403, "Tính năng tạm thời không được sử dụng nữa");
    $order = Order::findOrFail($orderId);
    $oldOrder = Order::where('code', $oldOrderCode)
      ->where('customer_id', $order->customer_id)
      ->firstOrFail();
    if (!$oldOrder->voucher_code) {
      abort(403, "Đơn hàng cũ không sử dụng voucher, không cần tiếp tục!");
    }
    if (!$oldOrder->paid_at) {
      abort(403, "Đơn hàng cũ chưa được thanh toán, không thể tiếp tục!");
    }
    $extendSubHours = $this->systemSettingService->getExtendSubHours();
    if (!$oldOrder->paid_at ||  $oldOrder->paid_at->lessThan(Carbon::now()->subHour($extendSubHours))) {
      abort(403, "Đã quá thời gian, hoá đơn chỉ được tiếp tục trong vòng {$extendSubHours} giờ!");
    }
    $hasExtend = Order::where('extend_id', $oldOrder->id)->exists();
    if ($hasExtend) {
      abort(403, "Hoá đơn này đã được kế thừa, không thể dùng lại");
    }
    $order->extend_id = $oldOrder->id;
    $order->save();
    $this->voucherService->applyVoucher($order, $oldOrder->voucher_code);
    $this->updateTotalPrice($order);
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  public function splitOrder(int $orderId, array $splitItems): array
  {
    DB::transaction(function () use ($orderId, $splitItems, &$originalOrder, &$newOrder) {
      $originalOrder = Order::with('items.toppings')->findOrFail($orderId);

      // Tạo đơn hàng mới
      $newOrder = $originalOrder->replicate();
      $newOrder->code = $originalOrder->code . "-2";
      $newOrder->save();
      $originalOrder->code .= "-1";


      foreach ($splitItems as $itemId => $quantityToSplit) {
        $orderItem = $originalOrder->items->find($itemId);
        if (!$orderItem || $quantityToSplit > $orderItem->quantity) {
          throw ValidationException::withMessages(['splitItems' => 'Invalid split quantity']);
        }

        // Nếu sản chuyển hết sản phẩm thì đổi id
        if ($orderItem->quantity == $quantityToSplit) {
          $orderItem->order_id =  $newOrder->id;
          $orderItem->save();
        } else {
          $orderItem->quantity -= $quantityToSplit;
          $orderItem->total_price = $orderItem->unit_price * $orderItem->quantity;
          $orderItem->save();
          // Tạo mới sản phẩm ở đơn mới
          $newItem = $orderItem->replicate();
          $newItem->order_id = $newOrder->id;
          $newItem->quantity = $quantityToSplit;

          $newItem->total_price = $newItem->unit_price * $newItem->quantity;
          $newItem->save();

          // Copy topping nếu có
          foreach ($orderItem->toppings as $topping) {
            $newTopping = $topping->replicate();
            $newTopping->order_item_id = $newItem->id;
            $newTopping->save();
          }
        }
      }

      // Cập nhật lại tổng tiền đơn gốc
      $originalOrder->save();
      $originalOrder->refresh();

      $this->updateTotalPrice($originalOrder);
      // Cập nhật tổng tiền đơn mới
      $newOrder->save();
      $newOrder->refresh();
      $this->updateTotalPrice($newOrder);
    });
    $originalOrder->refresh();
    $newOrder->refresh();
    $originalOrder->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    $newOrder->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return [$originalOrder, $newOrder];
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
  private function updateItems(Order $order, array $items)
  {
    $orderItemIds = [];

    foreach ($items as $item) {
      $product = $this->getProduct($item['product_id']);
      $unitPrice = $product->price;
      if (isset($item['id'])):
        $orderItem = OrderItem::findOrFail($item['id']);
        $orderItem->quantity = $item['quantity'] ?? 1;
        $orderItem->unit_price = $unitPrice;
        $orderItem->total_price = $orderItem->unit_price * $orderItem->quantity;
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
          ->where('printed_label', false)
          ->where('printed_kitchen', false)
          ->whereDoesntHave('toppings')
          ->first();
        if ($orderItem) :
          // Nếu item có sẵn và không có topping, chưa in tem, chưa in phiếu bếp => tăng số lượng
          $orderItem->increment('quantity');
          $orderItem->total_price = $unitPrice * $orderItem->quantity;
          $orderItem->save();
        else :
          $orderItem = OrderItem::create(
            [
              'order_id' => $order->id,
              'product_id' => $product->id,
              'product_name' => $product->name,
              'product_price' => $product->price,
              'quantity' => $item['quantity'] ?? 1,
              'unit_price' => $unitPrice,
              'total_price' => $unitPrice  * $item['quantity'],
              'print_label' =>  $product->print_label,
              'print_kitchen' =>  $product->print_kitchen,
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
   * Trừ kho cho đơn hàng đã hoàn tất
   */
  private function deductStockForCompletedOrder(Order $order): void
  {
    // Load order items với relationships cần thiết
    $order->loadMissing(['items.product', 'items.toppings']);

    foreach ($order->items as $orderItem) {
      try {
        // Kiểm tra kho trước khi trừ sử dụng dependencies
        if ($this->stockDeductionService->checkStockUsingDependencies($orderItem)) {
          // Sử dụng method mới với pre-computed dependencies
          $this->stockDeductionService->deductStockUsingDependencies($orderItem);
        } else {
          // Log cảnh báo thiếu kho nhưng không block đơn hàng
          Log::warning("Insufficient stock for order item using dependencies", [
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'product_name' => $orderItem->product->name,
            'product_type' => $orderItem->product->product_type->value,
            'quantity' => $orderItem->quantity
          ]);
        }
      } catch (\Exception $e) {
        // Log lỗi nhưng không block đơn hàng
        Log::error("Error deducting stock for order item using dependencies", [
          'order_id' => $order->id,
          'order_item_id' => $orderItem->id,
          'error' => $e->getMessage()
        ]);
      }
    }
  }

  /**
   * Lấy giá topping từ database
   */
  private function getTopping($toppingId)
  {
    return $this->getProduct($toppingId);
  }
}
