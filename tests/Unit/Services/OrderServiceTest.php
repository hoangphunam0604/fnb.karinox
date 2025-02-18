<?php

/**
 * - Tìm kiếm đơn đặt hàng theo mã
 * - Lấy danh sách đơn đặt hàng có phân trang
 * - Tạo đơn hàng với sản phẩm không có topping thành công
 * - Tạo đơn hàng với sản phẩm có topping thành công
 * - Cập nhật đơn hàng từ sản phẩm không có toppping thành sản phẩm có topping thành công
 * - Cập nhật đơn hàng từ sản phẩm có toppping thành sản phẩm không có topping thành công
 * - Tạo đơn hàng với mã giảm giá hợp lệ thành công
 * - Tạo đơn hàng với mã giảm giá không hợp lệ thành công
 * - Tạo đơn hàng sử dụng điểm thưởng thành công
 * - Tính tiền từ đơn hàng có sản phẩm không có topping
 * - Xác nhận đơn hàng thành công
 * - Huỷ đơn hàng thành công
 * - Huỷ đơn hàng thất bại
 * - Hoàn tất đơn hàng thành công
 * - Hoàn tất đơn hàng thất bại
 * - Cập nhật trạng thái đơn hàng thành công
 * - Áp dụng điểm thưởng cho đơn hàng thành công
 * - Áp dụng điểm thưởng cho đơn hàng thất bại
 */

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\ProductTopping;
use App\Models\Voucher;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PointService;
use App\Services\VoucherService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;

class OrderServiceTest extends TestCase
{
  use RefreshDatabase;

  protected OrderService $orderService;
  protected $voucherServiceMock;
  protected $pointServiceMock;
  protected $invoiceServiceMock;
  protected function setUp(): void
  {
    parent::setUp();
    $this->voucherServiceMock = Mockery::spy(VoucherService::class);
    $this->pointServiceMock = Mockery::spy(PointService::class);
    $this->invoiceServiceMock = Mockery::spy(InvoiceService::class);
    $this->app->instance(VoucherService::class, $this->voucherServiceMock);
    $this->app->instance(PointService::class, $this->pointServiceMock);
    $this->app->instance(InvoiceService::class, $this->invoiceServiceMock);

    $this->orderService = app(OrderService::class);
  }

  /** 
   * @testdox Tìm kiếm đơn đặt hàng theo mã
   * @test 
   */
  public function it_can_find_order_by_code()
  {
    $order = Order::factory()->create(['order_code' => 'ORDER123']);

    $foundOrder = $this->orderService->findOrderByCode('ORDER123');

    $this->assertNotNull($foundOrder);
    $this->assertEquals($order->id, $foundOrder->id);
  }

  /** 
   * @testdox Lấy danh sách đơn đặt hàng có phân trang
   * @test 
   */
  public function it_can_get_paginated_orders()
  {
    Order::factory()->count(15)->create();

    $orders = $this->orderService->getOrders(10);

    $this->assertCount(10, $orders);
    $this->assertEquals(15, $orders->total());
  }

  /** 
   * @testdox Tạo đơn hàng với sản phẩm không có topping thành công
   * @test 
   */
  public function it_can_create_order_without_toppings()
  {
    $product = Product::factory()->create(['price' => 50000]);
    $orderData = [
      'items' => [['product_id' => $product->id, 'quantity' => 1]]
    ];

    $order = $this->orderService->createOrder($orderData);

    $this->assertNotNull($order);
    $this->assertCount(1, $order->items);
    $this->assertEquals(50000, $order->total_price);
  }

  /** 
   * @testdox Tạo đơn hàng với sản phẩm có topping thành công
   * @test 
   */
  public function it_can_create_order_with_toppings()
  {
    $product = Product::factory()->create(['price' => 50000, 'is_topping' => false]);
    $topping = Product::factory()->create(['price' => 10000, 'is_topping' => true]);

    // Liên kết sản phẩm với topping
    ProductTopping::factory()->create(['product_id' => $product->id, 'topping_id' => $topping->id]);

    $orderData = [
      'items' => [['product_id' => $product->id, 'quantity' => 1, 'toppings' => [['topping_id' => $topping->id, 'quantity' => 1]]]]
    ];

    $order = $this->orderService->createOrder($orderData);

    $this->assertNotNull($order);
    $this->assertCount(1, $order->items);
    $this->assertCount(1, $order->items->first()->toppings);
    $this->assertEquals(60000, $order->total_price);
  }

  /** 
   * @testdox Cập nhật đơn hàng từ sản phẩm không có topping thành sản phẩm có topping thành công
   * @test 
   */
  public function it_can_update_order_from_no_toppings_to_with_toppings()
  {
    $product1 = Product::factory()->create(['price' => 30000, 'is_topping' => false]);
    $product2 = Product::factory()->create(['price' => 50000, 'is_topping' => false]);
    $topping = Product::factory()->create(['price' => 10000, 'is_topping' => true]);

    ProductTopping::factory()->create(['product_id' => $product2->id, 'topping_id' => $topping->id]);

    $order = Order::factory()->create();

    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id, 'quantity' => 1]);
    $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id, 'quantity' => 1]);


    $updateData = [
      'items' => [
        ['product_id' => $product1->id, 'quantity' => 1],
        ['product_id' => $product2->id, 'quantity' => 1, 'toppings' => [['topping_id' => $topping->id, 'quantity' => 1]]]
      ]
    ];

    $updatedOrder = $this->orderService->updateOrder($order->id, $updateData);

    $this->assertCount(2, $updatedOrder->items);
    $this->assertCount(1, $updatedOrder->items->where('product_id', $product2->id)->first()->toppings);
    $this->assertEquals(90000, $updatedOrder->total_price);
  }

  /** 
   * @testdox Cập nhật đơn hàng từ sản phẩm có topping thành sản phẩm không có topping thành công
   * @test 
   */
  public function it_can_update_order_from_with_toppings_to_no_toppings()
  {
    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => 30000, 'is_topping' => false]);
    $product2 = Product::factory()->create(['price' => 50000, 'is_topping' => false]);
    $product3 = Product::factory()->create(['price' => 60000, 'is_topping' => false]);
    $topping = Product::factory()->create(['price' => 10000, 'is_topping' => true]);

    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id, 'quantity' => 1]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product3->id, 'quantity' => 1]);
    $orderItem = OrderItem::factory()->create([
      'order_id' => $order->id,
      'product_id' => $product2->id,
      'quantity' => 1
    ]);
    OrderTopping::factory()->create([
      'order_item_id' => $orderItem->id,
      'topping_id' => $topping->id,
      'unit_price' => $topping->price,
      'quantity'  => 2,
      'total_price' =>  $topping->price * 2
    ]);

    $updateData = [
      'items' => [
        ['product_id' => $product1->id, 'quantity' => 1],
        ['product_id' => $product2->id, 'quantity' => 1]
      ]
    ];

    $updatedOrder = $this->orderService->updateOrder($order->id, $updateData);
    $updatedOrder->loadMissing(['items', 'items.toppings']);
    $this->assertCount(2, $updatedOrder->items);
    $this->assertCount(0, $updatedOrder->items->where('product_id', $product2->id)->first()->toppings);
    $this->assertEquals(80000, $updatedOrder->total_price);
  }

  /** 
   * @testdox Tạo đơn hàng với mã giảm giá hợp lệ thành công
   * @test 
   */
  public function it_can_create_order_with_valid_voucher()
  {
    $product = Product::factory()->create(['price' => 100000]);
    $validVoucher = Voucher::factory()->create(['code' => 'DISCOUNT10', 'is_active' => true]);

    $orderData = [
      'items' => [['product_id' => $product->id, 'quantity' => 1]],
      'voucher_code' => $validVoucher->code
    ];
    // Mock applyVoucherToOrder để không gọi thực tế
    $this->voucherServiceMock->shouldReceive('applyVoucherToOrder')
      ->once()
      ->with(\Mockery::type(Order::class), 'DISCOUNT10')
      ->andReturnUsing(function ($order) {
        $order->update([
          'voucher_code' => 'DISCOUNT10',
          'discount_amount' => 10000,
          'total_price' => 90000
        ]);
      });

    $order = $this->orderService->createOrder($orderData);
    $this->assertNotNull($order);
    $this->assertEquals(90000, $order->total_price); // 10% giảm từ 100000
  }

  /** 
   * @testdox Tạo đơn hàng với mã giảm giá không hợp lệ thành công
   * @test 
   */
  public function it_can_create_order_with_invalid_voucher()
  {
    $product = Product::factory()->create(['price' => 100000]);

    $orderData = [
      'items' => [['product_id' => $product->id, 'quantity' => 1]],
      'voucher_code' => 'INVALIDCODE'
    ];

    // Kiểm tra xem applyVoucherToOrder có được gọi không
    // Mock applyVoucherToOrder để không gọi thực tế
    $this->voucherServiceMock->shouldReceive('applyVoucherToOrder')
      ->once()
      ->with(\Mockery::on(function ($arg) {
        return $arg instanceof Order;
      }), 'INVALIDCODE')
      ->andReturnUsing(function ($order) {
        // Không cập nhật giá trị đơn hàng nếu voucher không hợp lệ
      });

    $order = $this->orderService->createOrder($orderData);


    $this->assertNotNull($order);
    $this->assertNull($order->voucher_code);
    $this->assertEquals(0, $order->discount_amount);
    $this->assertEquals(100000, $order->total_price); // Không áp dụng giảm giá
  }
  /**
   * @testdox Tạo đơn hàng sử dụng điểm thưởng thành công
   * @test
   */
  public function it_can_create_order_with_reward_points()
  {
    $customer = Customer::factory()->create(['reward_points' => 2000]);
    $product = Product::factory()->create(['price' => 100000]);

    // Mock applyVoucherToOrder để không gọi thực tế
    $this->pointServiceMock->shouldReceive('useRewardPointsForOrder')
      ->once()
      ->with(\Mockery::type(Order::class), 20)
      ->andReturnUsing(function ($order) {
        $order->update([
          'reward_points_used'  =>  20,
          'reward_discount' => 20000,
          'total_price' => 80000
        ]);
      });


    $orderData = [
      'customer_id' => $customer->id,
      'items' => [['product_id' => $product->id, 'quantity' => 1]],
      'reward_points_used' => 20 //dùng 20 điểm đổi 20.000đ
    ];

    $order = $this->orderService->createOrder($orderData);

    $this->assertNotNull($order);
    $this->assertEquals(20, $order->reward_points_used);
    $this->assertEquals(20000, $order->reward_discount);
    $this->assertEquals(80000, $order->total_price);
  }
  /**
   * @testdox Tính tiền đơn hàng thành công
   * @test
   */
  public function it_can_update_total_price()
  {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => 50000]);

    $orderItem = OrderItem::factory()->create([
      'order_id' => $order->id,
      'product_id' => $product->id,
      'quantity' => 2,
      'total_price_with_topping' => 100000
    ]);

    // Giả lập dữ liệu giảm giá
    $order->update([
      'discount_amount' => 5000,
      'reward_discount' => 2000
    ]);

    $this->orderService->updateTotalPrice($order);
    $order->refresh();

    $this->assertEquals(100000, $order->subtotal_price);
    $this->assertEquals(5000, $order->discount_amount);
    $this->assertEquals(2000, $order->reward_discount);
    $this->assertEquals(93000, $order->total_price); // 100000 - 5000 - 2000
  }
  /** 
   * @testdox Xác nhận đơn hàng thành công
   * @test 
   */
  public function it_can_confirm_order()
  {
    $order = Order::factory()->create(['order_status' => 'pending']);

    $confirmedOrder = $this->orderService->confirmOrder($order->id);

    $this->assertEquals('confirmed', $confirmedOrder->order_status);
  }

  /** 
   * @testdox Hủy đơn hàng thành công
   * @test 
   */
  public function it_can_cancel_order()
  {
    $order = Order::factory()->create(['order_status' => 'pending']);

    $cancelledOrder = $this->orderService->cancelOrder($order->id);

    $this->assertEquals('cancelled', $cancelledOrder->order_status);
  }

  /** 
   * @testdox Hủy đơn hàng thất bại khi đơn hàng đã hoàn tất
   * @test 
   */
  public function it_cannot_cancel_completed_order()
  {
    $order = Order::factory()->create(['order_status' => 'completed']);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Hoá đơn đã được hoàn thành, không thể huỷ');

    $this->orderService->cancelOrder($order->id);
  }

  /** 
   * @testdox Hoàn tất đơn hàng thành công
   * @test 
   */
  public function it_can_mark_order_as_completed()
  {
    $order = Order::factory()->create(['order_status' => 'pending', 'total_price' => 50000]);

    DB::shouldReceive('transaction')->andReturnUsing(function ($callback) use ($order) {
      return $callback();
    });

    $this->invoiceServiceMock->shouldReceive('createInvoiceFromOrder')->once()->with($order->id, 50000);

    $completedOrder = $this->orderService->markAsCompleted($order->id, 50000);

    $this->assertEquals('completed', $completedOrder->order_status);
  }

  /** 
   * @testdox Hoàn tất đơn hàng thất bại khi đã hoàn tất trước đó
   * @test 
   */
  public function it_cannot_complete_order_already_completed()
  {
    $order = Order::factory()->create(['order_status' => 'completed', 'total_price' => 50000]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Đơn hàng đã hoàn tất trước đó.');

    $this->orderService->markAsCompleted($order->id, 50000);
  }

  /** 
   * @testdox Hoàn tất đơn hàng thất bại khi số tiền thanh toán không đủ
   * @test 
   */
  public function it_cannot_complete_order_with_insufficient_payment()
  {
    $order = Order::factory()->create(['order_status' => 'pending', 'total_price' => 50000]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Số tiền thanh toán không đủ.');

    $this->orderService->markAsCompleted($order->id, 30000);
  }
  /** 
   * @testdox Cập nhật trạng thái đơn hàng thành công
   * @test 
   */
  public function it_can_update_order_status()
  {
    $order = Order::factory()->create(['order_status' => 'pending']);

    $updatedOrder = $this->orderService->updateOrderStatus($order->id, 'confirmed');

    $this->assertEquals('confirmed', $updatedOrder->order_status);
  }

  /** 
   * @testdox Kiểm tra và áp dụng điểm thưởng thành công
   * @test 
   */
  public function it_can_apply_reward_points()
  {
    $customer = Customer::factory()->create(['reward_points' => 2000]);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'total_price' => 100000]);

    $this->pointServiceMock->shouldReceive('useRewardPointsForOrder')
      ->once()
      ->with(\Mockery::on(fn($arg) => $arg instanceof Order), 2000);

    $updatedOrder = $this->orderService->applyRewardPoints($order, 2000);

    $this->assertNotNull($updatedOrder);
  }

  /** 
   * @testdox Kiểm tra và áp dụng điểm thưởng thất bại khi không có khách hàng
   * @test 
   */
  public function it_cannot_apply_reward_points_without_customer()
  {
    $order = Order::factory()->create(['customer_id' => null, 'total_price' => 100000]);

    $this->pointServiceMock->shouldNotReceive('useRewardPointsForOrder');

    $updatedOrder = $this->orderService->applyRewardPoints($order, 2000);

    $this->assertNotNull($updatedOrder);
  }
}
