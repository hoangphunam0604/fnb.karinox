<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCompleted;
use App\Events\OrderPaymentSuccess;
use App\Events\PrintRequested;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Product;
use App\Models\ProductTopping;
use App\Models\TableAndRoom;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
  public function __construct(
    protected PointService $pointService,
    protected VoucherService $voucherService,
    protected InvoiceService $invoiceService,
    protected SystemSettingService $systemSettingService,
    protected StockDeductionService $stockDeductionService,
    protected BookingService $bookingService
  ) {}
  public function getOrdersByTableId(int $branchId, int $tableId)
  {
    return DB::transaction(function () use ($branchId, $tableId) {
      $table = TableAndRoom::where('id', $tableId)
        ->firstOrFail();
      // Lấy tất cả các đơn hàng PENDING
      $orders = Order::where('table_id', $table->id)
        ->where('branch_id', $branchId)
        ->where('order_status', OrderStatus::PENDING)
        ->with(['items.toppings', 'customer.membershipLevel', 'table'])
        ->get();

      // Nếu chưa có đơn nào thì tạo mới
      if ($orders->isEmpty()) {
        $order = Order::create([
          'table_id' => $table->id,
          'table_name' => $table->name,
          'branch_id' => $branchId,
          'order_status' => OrderStatus::PENDING,
          'total_price' => 0,
        ]);

        $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
        $orders->push($order);
      }

      return $orders;
    });
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderByCode($code)
  {
    return Order::with(['items.toppings', 'customer.membershipLevel', 'table'])->where('order_code', strtoupper($code))->first();
  }

  public function loadOrderRelations(Order $order)
  {
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findByCode($orderCode)
  {
    return Order::where('order_code', strtoupper($orderCode))->firstOrFail();
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderById($id)
  {
    return Order::findOrFail($id);
  }
  /**
   * Tìm kiếm đơn đặt hàng theo mã
   */
  public function findOrderInBranchById($id)
  {
    return Order::where('id', $id)
      ->where('branch_id', app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null)
      ->firstOrFail();
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
      /*  $this->applyDiscounts($order, $data);
      $order->refresh(); */
      $order->loadMissing(['customer.membershipLevel', 'table']);
      return $order;
    });
  }

  /**
   * Tính tiền đơn hàng
   */
  public function updateTotalPrice(Order $order): void
  {
    // 1️⃣ Tính tổng tiền sản phẩm (giá gốc chưa giảm) = unit_price * quantity
    $subtotal = $order->items->sum(fn($item) => $item->unit_price * $item->quantity);

    // 2️⃣ Tính tổng giảm giá từ các items (discount + reward)
    $itemDiscounts = $order->items->sum(
      fn($item) => ($item->discount_amount ?? 0) * $item->quantity + ($item->reward_discount ?? 0)
    );

    // 3️⃣ Lấy số tiền giảm giá từ voucher order-level (nếu có)
    $voucherDiscount = $order->voucher_discount ?? 0;

    // 4️⃣ Tính tổng số tiền giảm từ điểm thưởng (để hiển thị)
    $rewardDiscount = $order->items->sum(fn($item) => $item->reward_discount ?? 0);

    // 5️⃣ Tính tổng tiền cần thanh toán
    $totalPrice = max($subtotal - $itemDiscounts - $voucherDiscount, 0);

    // 6️⃣ Cập nhật vào đơn hàng
    $order->update([
      'subtotal_price' => $subtotal,
      'voucher_discount' => $voucherDiscount,
      'reward_discount' => $rewardDiscount,
      'total_price' => $totalPrice
    ]);
  }


  /**
   * Hủy đơn hàng
   */
  public function cancelOrder($orderId): void
  {
    $order = Order::findOrFail($orderId);
    if ($order->order_status == OrderStatus::COMPLETED)
      throw new Exception('Đơn hàng đã được thanh toán, không thể huỷ');

    DB::transaction(function () use ($order) {
      $this->pointService->restoreTransactionRewardPoints($order);
      $this->voucherService->restoreVoucherUsage($order);

      // Hủy bookings nếu có
      $this->bookingService->cancelBookings($order);

      $order->order_status = OrderStatus::CANCELED;
      $order->save();
    });
  }

  public function completePayment(Order $order, string $payment_method = 'cash', $print = false)
  {
    if ($order->payment_status === PaymentStatus::PAID) {/* 
      event(new OrderPaymentSuccess($order));
      // đã trả tiền rồi, không cần ghi đè */
      return true;
    }

    return DB::transaction(function () use ($order, $payment_method, $print) {
      if ($order->payment_started_at === null) {
        $order->payment_started_at = now();
      }
      $order->paid_at = now();
      $order->payment_method = $payment_method;
      $order->payment_status = PaymentStatus::PAID;
      $order->order_status = OrderStatus::COMPLETED;

      $order->save();

      // Xác nhận bookings nếu có
      $this->bookingService->confirmBookings($order);


      //Tạo hoá đơn
      $this->invoiceService->createInvoiceFromOrder($order->id, $print);

      // Fire event sau khi order completed thành công
      event(new OrderPaymentSuccess($order));

      return true;
    });
  }
  public function requestPrintProvisional(int $orderId): Order
  {
    $order = Order::findOrFail($orderId);
    broadcast(new PrintRequested('provisional', ['id' => $order->id], $order->branch_id));
    return $order;
  }
  /**
   * Cập nhật trạng thái đơn hàng
   */
  public function updateOrderStatus(int $orderId, OrderStatus $status): Order
  {
    return DB::transaction(function () use ($orderId, $status) {
      $order = Order::findOrFail($orderId);
      $oldStatus = $order->order_status;

      $order->update(['order_status' => $status]);

      // Note: Stock deduction is now handled by DeductStockAfterInvoice listener
      // when InvoiceCreated event is fired for better data consistency

      return $order;
    });
  }


  public function addCustomer(Order $order, $customerId): Order
  {
    $order->customer_id = $customerId;
    $order->save();

    // Áp dụng giảm giá arena member trước khi áp dụng voucher
    $this->applyArenaMemberDiscount($order);

    $this->voucherService->autoApplyVoucher($order);
    return $this->loadOrderRelations($order);
  }

  public function removeCustomer(Order $order): Order
  {
    $order->customer_id = null;
    $order->save();
    $this->removeArenaMemberDiscount($order);
    $this->removePoint($order);
    $this->removeVoucher($order);
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  /**
   * Áp dụng điểm thưởng cho các items được chọn hoặc tất cả items
   * @param Order $order
   * @param array|null $orderItemIds - Mảng ID các items muốn áp dụng, null = áp dụng tất cả
   * @return Order
   */
  public function applyPoint(Order $order, ?array $orderItemIds = null): Order
  {
    if (!$order->customer) {
      throw new Exception('Đơn hàng chưa có khách hàng');
    }

    $customer = $order->customer;
    $availablePoints = $customer->reward_points - $customer->used_reward_points;

    if ($availablePoints <= 0) {
      throw new Exception('Khách hàng không có đủ điểm thưởng');
    }

    // Lấy tỷ lệ quy đổi điểm sang tiền
    $pointValue = $this->systemSettingService->getRewardPointConversionRate();
    $availableMoney = $availablePoints * $pointValue;

    $order->loadMissing('items');

    // Lọc các items cần áp dụng điểm
    $targetItems = $orderItemIds
      ? $order->items->whereIn('id', $orderItemIds)
      : $order->items;

    // Sắp xếp items theo giá tăng dần để ưu tiên áp dụng cho items đắt trước
    $targetItems = $targetItems->sortBy('total_price', 'desc');

    $totalPointsUsed = 0;
    $totalMoneyDiscounted = 0;

    foreach ($targetItems as $item) {
      // Bỏ qua items đã được áp dụng điểm
      if ($item->reward_points_used > 0) {
        continue;
      }

      $itemPrice = $item->total_price;

      // Chỉ áp dụng nếu còn đủ tiền để đổi hết giá item
      if ($availableMoney >= $itemPrice) {
        // Tính số điểm cần để đổi item này
        $pointsNeeded = ceil($itemPrice / $pointValue);

        $item->reward_points_used = $pointsNeeded;
        $item->reward_discount = $itemPrice;
        $item->save();

        $totalPointsUsed += $pointsNeeded;
        $totalMoneyDiscounted += $itemPrice;
        $availableMoney -= $itemPrice;

        Log::info('Applied reward points to item', [
          'order_id' => $order->id,
          'order_item_id' => $item->id,
          'points_used' => $pointsNeeded,
          'discount' => $itemPrice
        ]);
      }
    }

    if ($totalPointsUsed > 0) {
      // Cập nhật tổng điểm đã sử dụng cho order
      $this->pointService->useRewardPoints($order, $totalPointsUsed);
    }

    // Cập nhật tổng tiền
    $this->updateTotalPrice($order);
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  /**
   * Xóa điểm thưởng đã áp dụng cho các items
   * @param Order $order
   * @param array|null $orderItemIds - Mảng ID các items muốn xóa điểm, null = xóa tất cả
   * @return Order
   */
  public function removePoint(Order $order, ?array $orderItemIds = null): Order
  {
    $order->loadMissing('items');

    // Lọc các items cần xóa điểm
    $targetItems = $orderItemIds
      ? $order->items->whereIn('id', $orderItemIds)
      : $order->items;

    $totalPointsRestored = 0;

    foreach ($targetItems as $item) {
      if ($item->reward_points_used > 0) {
        $totalPointsRestored += $item->reward_points_used;

        $item->reward_points_used = 0;
        $item->reward_discount = 0;
        $item->save();

        Log::info('Removed reward points from item', [
          'order_id' => $order->id,
          'order_item_id' => $item->id
        ]);
      }
    }

    if ($totalPointsRestored > 0) {
      // Hoàn lại điểm cho khách hàng
      $this->pointService->restoreTransactionRewardPoints($order);
    }

    // Cập nhật tổng tiền
    $this->updateTotalPrice($order);
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  public function removeVoucher(Order $order): Order
  {
    $this->voucherService->restoreVoucherUsage($order);
    $order->refresh();
    $order->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
    return $order;
  }

  /**
   * Áp dụng giảm giá cho gói hội viên arena
   */
  private function applyArenaMemberDiscount(Order $order): void
  {
    if (!$order->customer_id) {
      return;
    }

    $customer = $order->customer;

    // Kiểm tra khách hàng có gói hội viên arena và còn hạn không
    if (!$customer->arena_member || $customer->arena_member === 'none') {
      return;
    }

    // Kiểm tra hạn sử dụng
    if ($customer->arena_member_exp && $customer->arena_member_exp->isPast()) {
      return;
    }

    $arenaMemberType = $customer->arena_member;

    // Lấy tất cả items của order
    $order->loadMissing(['items.product']);

    foreach ($order->items as $item) {
      $product = $item->product;

      // Kiểm tra sản phẩm có cấu hình giảm giá arena không
      if (!$product->arena_discount || !is_array($product->arena_discount)) {
        continue;
      }

      // Kiểm tra có giảm giá cho gói hội viên này không
      if (!isset($product->arena_discount[$arenaMemberType])) {
        continue;
      }

      $discountPercent = floatval($product->arena_discount[$arenaMemberType]);

      if ($discountPercent > 0 && $discountPercent <= 100) {
        // Áp dụng giảm giá theo %
        $item->discount_type = DiscountType::PERCENT;
        $item->discount_percent = $discountPercent;
        $item->discount_amount = 0;
        $item->discount_note = "Giảm giá hội viên Arena ({$arenaMemberType})";
        $item->save(); // calculatePrices() sẽ được gọi tự động trong model

        Log::info('Applied arena member discount', [
          'order_id' => $order->id,
          'order_item_id' => $item->id,
          'product_id' => $product->id,
          'arena_member' => $arenaMemberType,
          'discount_percent' => $discountPercent
        ]);
      }
    }

    // Cập nhật tổng tiền sau khi áp dụng giảm giá
    $this->updateTotalPrice($order);
  }

  /**
   * Xóa giảm giá hội viên arena khi xóa khách hàng
   */
  private function removeArenaMemberDiscount(Order $order): void
  {
    $order->loadMissing('items');

    foreach ($order->items as $item) {
      // Kiểm tra nếu discount_note chứa thông tin giảm giá arena member
      if ($item->discount_note && str_contains($item->discount_note, 'Giảm giá hội viên Arena')) {
        // Xóa giảm giá
        $item->discount_type = null;
        $item->discount_percent = 0;
        $item->discount_amount = 0;
        $item->discount_note = null;
        $item->save(); // calculatePrices() sẽ được gọi tự động trong model

        Log::info('Removed arena member discount', [
          'order_id' => $order->id,
          'order_item_id' => $item->id,
          'product_id' => $item->product_id
        ]);
      }
    }

    // Cập nhật tổng tiền sau khi xóa giảm giá
    $this->updateTotalPrice($order);
  }


  public function extend($orderId, $oldOrderCode): Order
  {
    abort(403, "Tính năng tạm thời không được sử dụng nữa");
    $order = Order::findOrFail($orderId);
    $oldOrder = Order::where('order_code', $oldOrderCode)
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

  public function splitOrder(int $orderId, int $tableId, array $splitItems): array
  {
    return DB::transaction(function () use ($orderId, $tableId, $splitItems) {
      $originalOrder = Order::with('items.toppings')->findOrFail($orderId);

      // Tạo đơn hàng mới
      $newOrder = $originalOrder->replicate();
      $newOrder->order_code = null; // Đặt lại mã đơn hàng để hệ thống tự sinh
      $newOrder->table_id = $tableId;
      $newOrder->save();


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
          $orderItem->total_price = $orderItem->sale_price * $orderItem->quantity;
          $orderItem->save();
          // Tạo mới sản phẩm ở đơn mới
          $newItem = $orderItem->replicate();
          $newItem->order_id = $newOrder->id;
          $newItem->quantity = $quantityToSplit;

          $newItem->total_price = $newItem->sale_price * $newItem->quantity;
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
      $originalOrder->refresh();
      $newOrder->refresh();
      $originalOrder->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
      $newOrder->loadMissing(['items.toppings', 'customer.membershipLevel', 'table']);
      return [$originalOrder, $newOrder];
    });
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
      if (isset($item['id'])):
        $orderItem = OrderItem::findOrFail($item['id']);
        $oldNote = $orderItem->note;
        $orderItem->quantity = $item['quantity'] ?? 1;
        $orderItem->unit_price = $item['unit_price'] ??  $orderItem->unit_price;
        $orderItem->discount_type = $item['discount_type'] ?? null;
        $orderItem->discount_note = $item['discount_note'] ?? null;
        // Ghi nhận giảm giá từ thu ngân
        // Các cách giảm giá của thành viên / hội viên sẽ được xử lý trong voucher
        // Cập nhật discount values tùy theo type
        if ($orderItem->discount_type === DiscountType::PERCENT) {
          $orderItem->discount_percent = $item['discount_percent'] ??  0;
        } elseif ($orderItem->discount_type === DiscountType::FIXED) {
          $orderItem->discount_amount = $item['discount_amount'] ??  0;
        }

        $orderItem->note = $item['note'] ?? $orderItem->note;
        $orderItem->save(); // calculatePrices() được gọi tự động trong model

        Log::info('Update order item - checking booking', [
          'product_id' => $product->id,
          'product_type' => $product->product_type,
          'product_type_value' => $product->product_type->value ?? 'null',
          'arena_type' => $product->arena_type,
          'is_booking' => $product->isBookingFullSlot()
        ]);

        // Xử lý booking nếu là sản phẩm đặt sân full slot và note có thay đổi
        if ($product->isBookingFullSlot()) {
          Log::info('Syncing bookings for updated order item', [
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'product_type' => $product->product_type->value,
            'arena_type' => $product->arena_type,
            'old_note' => $oldNote,
            'new_note' => $orderItem->note
          ]);
          $this->bookingService->syncBookingsForItem($orderItem, $product);
        }

        // Xử lý booking nếu là sản phẩm đặt chỗ social
        if ($product->isBookingSocial()) {
          $this->bookingService->createSocial($orderItem, $product);
        }
        // Xử lý topping
        if (!empty($item['toppings'])) {
          $this->updateOrderToppings($orderItem, $item['product_id'], $item['toppings']);
        } else {
          // Nếu không có topping mới, xóa tất cả topping cũ
          OrderTopping::where('order_item_id', $orderItem->id)->delete();
        }
      else:
        $orderItem = OrderItem::create([
          'order_id' => $order->id,
          'product_id' => $product->id,
          'product_name' => $product->name,
          'product_price' => $product->price,
          'product_type' => $product->product_type,
          'arena_type' => $product->arena_type,
          'quantity' => $item['quantity'] ?? 1,
          'unit_price' =>  $item['unit_price'] ??  $product->price,
          'discount_type' => $item['discount_type'] ?? null,
          'discount_percent' => $item['discount_percent'] ?? 0,
          'discount_amount' => $item['discount_amount'] ?? 0,
          'discount_note' => $item['discount_note'] ?? null,
          'print_label' => $product->print_label,
          'print_kitchen' => $product->print_kitchen,
          'note' => $item['note'] ?? null,
        ]); // calculatePrices() được gọi tự động trong model

        Log::info('Create order item - checking booking', [
          'product_id' => $product->id,
          'product_type' => $product->product_type,
          'product_type_value' => $product->product_type->value ?? 'null',
          'arena_type' => $product->arena_type,
          'is_booking' => $product->isBookingProduct(),
          'note' => $item['note'] ?? 'null'
        ]);

        // Tạo bookings nếu là sản phẩm đặt sân
        if ($product->isBookingProduct()) {
          Log::info('Creating bookings for new order item', [
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'product_type' => $product->product_type->value,
            'arena_type' => $product->arena_type,
            'note' => $item['note'] ?? null
          ]);
          $this->bookingService->syncBookingsForItem($orderItem, $product);
        }
      /* endif; */
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

    // Refresh để lấy toppings mới và tính lại prices
    $orderItem->refresh();
    $orderItem->recalculateWithToppings();
    $orderItem->save();
  }

  /**
   * Áp dụng giảm giá từ voucher và điểm thưởng
   */
  private function applyDiscounts(Order $order, array $data): void
  {
    // 2️⃣ Áp dụng voucher nếu có
    if (!empty($data['voucher_code']) && !$order->voucher_discount) {
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
