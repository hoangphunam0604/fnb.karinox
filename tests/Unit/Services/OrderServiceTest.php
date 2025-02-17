<?php

/**
 * - Tìm kiếm đơn đặt hàng theo mã
 * - Lấy danh sách đơn đặt hàng có phân trang
 * - Tạo đơn hàng với sản phẩm không có topping thành công
 * - Tạo đơn hàng với sản phẩm có topping thành công
 * - Cập nhật đơn hàng từ sản phẩm không có toppping thành sản phẩm có topping thành công
 * - Cập nhật đơn hàng từ sản phẩm có toppping thành sản phẩm không có topping thành công
 * - Tính tiền từ đơn hàng có sản phẩm có topping
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

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\ProductTopping;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
  use RefreshDatabase;

  protected OrderService $orderService;

  protected function setUp(): void
  {
    parent::setUp();
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
    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => 30000, 'is_topping' => false]);
    $product2 = Product::factory()->create(['price' => 50000, 'is_topping' => false]);
    $topping = Product::factory()->create(['price' => 10000, 'is_topping' => true]);

    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id, 'quantity' => 1]);
    $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id, 'quantity' => 1]);

    ProductTopping::factory()->create(['product_id' => $product2->id, 'topping_id' => $topping->id]);

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
    $topping = Product::factory()->create(['price' => 10000, 'is_topping' => true]);

    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id, 'quantity' => 1]);
    $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id, 'quantity' => 1]);
    OrderTopping::factory()->create(['order_item_id' => $orderItem->id, 'topping_id' => $topping->id]);

    $updateData = [
      'items' => [
        ['product_id' => $product1->id, 'quantity' => 1],
        ['product_id' => $product2->id, 'quantity' => 1, 'toppings' => []]
      ]
    ];

    $updatedOrder = $this->orderService->updateOrder($order->id, $updateData);

    $this->assertCount(2, $updatedOrder->items);
    $this->assertCount(0, $updatedOrder->items->where('product_id', $product2->id)->first()->toppings);
    $this->assertEquals(80000, $updatedOrder->total_price);
  }
}
