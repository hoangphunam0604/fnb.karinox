<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use App\Enums\OrderStatus;
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
use Illuminate\Support\Facades\Event;
use App\Events\OrderCompleted;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

  #[Test]
  #[TestDox("Tìm kiếm đơn đặt hàng theo mã")]
  public function it_can_find_order_by_code()
  {
    $order = Order::factory()->create(['order_code' => 'ORDER123']);

    $foundOrder = $this->orderService->findOrderByCode('ORDER123');

    $this->assertNotNull($foundOrder);
    $this->assertEquals($order->id, $foundOrder->id);
  }

  #[Test]
  public function it_can_get_paginated_orders()
  {
    Order::factory()->count(15)->create();

    $orders = $this->orderService->getOrders(10);

    $this->assertCount(10, $orders);
    $this->assertEquals(15, $orders->total());
  }

  #[Test]
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

  #[Test]
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

  #[Test]
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

  #[Test]
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

  #[Test]
  public function it_can_create_order_with_valid_voucher()
  {
    $product = Product::factory()->create(['price' => 100000]);
    $validVoucher = Voucher::factory()->create(['code' => 'DISCOUNT10', 'is_active' => true]);

    $orderData = [
      'items' => [['product_id' => $product->id, 'quantity' => 1]],
      'voucher_code' => $validVoucher->code
    ];
    // Mock applyVoucherToOrder để không gọi thực tế
    $this->voucherServiceMock->shouldReceive('applyVoucher')
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

  #[Test]
  public function it_can_create_order_with_invalid_voucher()
  {
    $product = Product::factory()->create(['price' => 100000]);

    $orderData = [
      'items' => [['product_id' => $product->id, 'quantity' => 1]],
      'voucher_code' => 'INVALIDCODE'
    ];

    // Kiểm tra xem applyVoucher có được gọi không
    // Mock applyVoucher để không gọi thực tế
    $this->voucherServiceMock->shouldReceive('applyVoucher')
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
  #[Test]
  public function it_can_create_order_with_reward_points()
  {
    $customer = Customer::factory()->create(['reward_points' => 2000]);
    $product = Product::factory()->create(['price' => 100000]);

    // Mock useRewardPoints để không gọi thực tế
    $this->pointServiceMock->shouldReceive('useRewardPoints')
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
  #[Test]
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
  #[Test]
  public function it_can_confirm_order()
  {
    $order = Order::factory()->create(['order_status' => OrderStatus::PENDING]);

    $confirmedOrder = $this->orderService->confirmOrder($order->id);

    $this->assertEquals(OrderStatus::CONFIRMED, $confirmedOrder->order_status);
  }

  #[Test]
  public function it_can_cancel_order()
  {
    $order = Order::factory()->create(['order_status' => OrderStatus::PENDING]);

    $cancelledOrder = $this->orderService->cancelOrder($order->id);

    $this->assertEquals(OrderStatus::CANCELED, $cancelledOrder->order_status);
  }

  #[Test]
  public function it_cannot_cancel_completed_order()
  {
    $order = Order::factory()->create(['order_status' => OrderStatus::COMPLETED]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Hoá đơn đã được hoàn thành, không thể huỷ');

    $this->orderService->cancelOrder($order->id);
  }

  #[Test]
  public function test_it_can_mark_order_as_completed()
  {
    // Tạo đơn hàng thực trong database
    $order = Order::factory()->create([
      'order_status' => OrderStatus::PENDING,
      'total_price' => 1000,
    ]);

    // Gọi phương thức
    $updatedOrder = $this->orderService->markAsCompleted($order->id, 1000);

    // Kiểm tra order đã cập nhật trạng thái
    $this->assertEquals(OrderStatus::COMPLETED, $updatedOrder->order_status);
  }


  #[Test]
  public function test_it_throws_exception_if_order_already_completed()
  {
    // Tạo đơn hàng đã hoàn tất
    $order = Order::factory()->create([
      'order_status' => OrderStatus::COMPLETED,
      'total_price' => 1000,
    ]);

    // Kiểm tra ngoại lệ
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Đơn hàng đã hoàn tất trước đó.');

    $this->orderService->markAsCompleted($order->id, 1000);
  }


  #[Test]
  public function test_it_throws_exception_if_paid_amount_is_insufficient()
  {
    // Tạo đơn hàng với giá trị 1000 nhưng chỉ thanh toán 500
    $order = Order::factory()->create([
      'order_status' => OrderStatus::PENDING,
      'total_price' => 1000,
    ]);

    // Kiểm tra ngoại lệ
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Số tiền thanh toán không đủ.');

    $this->orderService->markAsCompleted($order->id, 500);
  }

  #[Test]
  public function test_it_marks_completed_if_order_is_fully_paid()
  {
    // Tạo đơn hàng thực trong database
    $order = Order::factory()->create([
      'order_status' => OrderStatus::PENDING,
      'total_price' => 500,
    ]);

    // Gọi phương thức
    $updatedOrder = $this->orderService->markAsCompleted($order->id, 500);

    // Kiểm tra order đã cập nhật trạng thái
    $this->assertEquals(OrderStatus::COMPLETED, $updatedOrder->order_status);
  }

  #[Test]
  public function test_it_runs_in_transaction()
  {
    // Giả lập transaction
    DB::shouldReceive('transaction')
      ->atLeast()->once()
      ->andReturnUsing(function ($callback) {
        return $callback();
      });

    // Tạo đơn hàng thực
    $order = Order::factory()->create([
      'order_status' => OrderStatus::PENDING,
      'total_price' => 1000,
    ]);

    // Gọi phương thức
    $this->orderService->markAsCompleted($order->id, 1000);
  }

  #[Test]
  public function test_it_dispatches_order_completed_event()
  {
    // Giả lập sự kiện
    Event::fake();

    // Tạo đơn hàng thực
    $order = Order::factory()->create([
      'order_status' => OrderStatus::PENDING,
      'total_price' => 1000,
    ]);

    // Gọi phương thức
    $this->orderService->markAsCompleted($order->id, 1000);

    // Kiểm tra sự kiện được dispatch
    Event::assertDispatched(OrderCompleted::class, function ($event) use ($order) {
      return $event->order->id === $order->id;
    });
  }

  #[Test]
  public function test_it_returns_updated_order()
  {
    // Tạo đơn hàng thực
    $order = Order::factory()->create([
      'order_status' => OrderStatus::PENDING,
      'total_price' => 1000,
    ]);

    // Gọi phương thức
    $updatedOrder = $this->orderService->markAsCompleted($order->id, 1000);

    // Kiểm tra order trả về có cập nhật đúng trạng thái
    $this->assertInstanceOf(Order::class, $updatedOrder);
    $this->assertEquals(OrderStatus::COMPLETED, $updatedOrder->order_status);
  }

  #[Test]

  public function test_it_fails_if_order_does_not_exist()
  {
    // Kiểm tra ngoại lệ khi order không tồn tại
    $this->expectException(ModelNotFoundException::class);

    $this->orderService->markAsCompleted(99999, 1000); // ID không tồn tại
  }


  #[Test]
  public function it_can_update_order_status()
  {
    $order = Order::factory()->create(['order_status' => OrderStatus::PENDING]);

    $updatedOrder = $this->orderService->updateOrderStatus($order->id, OrderStatus::CONFIRMED);

    $this->assertEquals(OrderStatus::CONFIRMED, $updatedOrder->order_status);
  }

  #[Test]
  public function it_can_apply_reward_points()
  {
    $customer = Customer::factory()->create(['reward_points' => 2000]);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'total_price' => 100000]);

    $this->pointServiceMock->shouldReceive('useRewardPoints')
      ->once()
      ->with(\Mockery::on(fn($arg) => $arg instanceof Order), 2000);

    $updatedOrder = $this->orderService->applyRewardPoints($order, 2000);

    $this->assertNotNull($updatedOrder);
  }

  #[Test]
  public function it_cannot_apply_reward_points_without_customer()
  {
    $order = Order::factory()->create(['customer_id' => null, 'total_price' => 100000]);

    $this->pointServiceMock->shouldNotReceive('useRewardPoints');

    $updatedOrder = $this->orderService->applyRewardPoints($order, 2000);

    $this->assertNotNull($updatedOrder);
  }
}
