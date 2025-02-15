<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTopping;
use App\Models\OrderTopping;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
  use RefreshDatabase;

  protected ProductService $productService;
  protected OrderService $orderService;
  protected InvoiceService $invoiceService;
  protected Branch $branch;

  protected function setUp(): void
  {
    parent::setUp();
    $this->productService = new ProductService();
    $this->orderService = new OrderService();
    $this->invoiceService = new InvoiceService();
    $this->branch = Branch::factory()->create();
  }

  /**
   * Kiểm tra tạo đơn hàng chỉ có sản phẩm
   */
  public function test_create_order_with_products()
  {
    $customer = Customer::factory()->create();
    $product1 = Product::factory()->create(['price' => 50000]);
    $product2 = Product::factory()->create(['price' => 40000]);

    $orderData = [
      'customer_id' => $customer->id,
      'branch_id' => $this->branch->id,
      'order_status' => 'pending',
      'note' => 'Giao hàng nhanh',
      'items' => [
        ['product_id' => $product1->id, 'quantity' => 2],
        ['product_id' => $product2->id, 'quantity' => 1],
      ]
    ];

    $order = $this->orderService->createOrder($orderData);

    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'customer_id' => $customer->id,
      'order_status' => 'pending',
    ]);

    $this->assertDatabaseHas('order_items', [
      'order_id' => $order->id,
      'product_id' => $product1->id,
      'quantity' => 2,
      'unit_price' => 50000,
      'total_price' => 100000,
    ]);

    $this->assertDatabaseHas('order_items', [
      'order_id' => $order->id,
      'product_id' => $product2->id,
      'quantity' => 1,
      'unit_price' => 40000,
      'total_price' => 40000,
    ]);
  }

  /**
   * Kiểm tra tạo đơn hàng có sản phẩm và toppings với số lượng
   */
  public function test_create_order_with_products_and_toppings()
  {
    $customer = Customer::factory()->create();
    $topping1 = Product::factory()->create(['is_topping' => true, 'price' => 10000]);
    $topping2 = Product::factory()->create(['is_topping' => true, 'price' => 15000]);
    $product = Product::factory()->create(['price' => 50000]);

    $this->productService->updateProduct($product->id, [
      'toppings' => [$topping1->id, $topping2->id]
    ]);

    $orderData = [
      'customer_id' => $customer->id,
      'branch_id' => $this->branch->id,
      'order_status' => 'pending',
      'note' => 'Ít đường',
      'items' => [
        [
          'product_id' => $product->id,
          'quantity' => 2,
          'toppings' => [
            ['topping_id' => $topping1->id, 'quantity' => 1],
            ['topping_id' => $topping2->id, 'quantity' => 2],
          ],
        ]
      ]
    ];

    $order = $this->orderService->createOrder($orderData);
    $orderItem = OrderItem::where('order_id', $order->id)->first();

    $this->assertDatabaseHas('order_toppings', [
      'order_item_id' => $orderItem->id,
      'topping_id' => $topping1->id,
      'quantity' => 1,
      'unit_price' => $topping1->price,
      'total_price' => $topping1->price * 1,
    ]);

    $this->assertDatabaseHas('order_toppings', [
      'order_item_id' => $orderItem->id,
      'topping_id' => $topping2->id,
      'quantity' => 2,
      'unit_price' => $topping2->price,
      'total_price' => $topping2->price * 2,
    ]);
  }

  /**
   * Kiểm tra cập nhật toppings với số lượng
   */
  public function test_update_order_toppings()
  {
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price' => 50000]);

    $topping1 = Product::factory()->create(['is_topping' => true, 'price' => 10000]);
    $topping2 = Product::factory()->create(['is_topping' => true, 'price' => 15000]);
    $topping3 = Product::factory()->create(['is_topping' => true, 'price' => 20000]);

    $this->productService->updateProduct($product->id, [
      'toppings' => [$topping1->id, $topping2->id, $topping3->id]
    ]);

    $orderData = [
      'customer_id' => $customer->id,
      'branch_id' => $branch->id,
      'order_status' => 'pending',
      'items' => [
        [
          'product_id' => $product->id,
          'quantity' => 2,
          'toppings' => [
            ['topping_id' => $topping1->id, 'quantity' => 1],
            ['topping_id' => $topping2->id, 'quantity' => 2]
          ],
        ]
      ]
    ];

    $order = $this->orderService->createOrder($orderData);

    $this->orderService->updateOrder($order->id, [
      'items' => [
        [
          'product_id' => $product->id,
          'quantity' => 2,
          'toppings' => [
            ['topping_id' => $topping3->id, 'quantity' => 3]
          ],
        ]
      ]
    ]);

    $orderItem = OrderItem::where('order_id', $order->id)->first();
    $this->assertDatabaseHas('order_toppings', [
      'order_item_id' => $orderItem->id,
      'topping_id' => $topping3->id,
      'quantity' => 3,
      'unit_price' => $topping3->price,
      'total_price' => $topping3->price * 3,
    ]);
  }
  /** @test */
  public function it_can_create_an_order()
  {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price' => 50000]);

    $orderData = [
      'customer_id' => $customer->id,
      'branch_id' => $this->branch->id,
      'items' => [
        ['product_id' => $product->id, 'quantity' => 2]
      ]
    ];

    $order = $this->orderService->createOrder($orderData);

    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'customer_id' => $customer->id
    ]);

    $this->assertDatabaseHas('order_items', [
      'order_id' => $order->id,
      'product_id' => $product->id,
      'quantity' => 2
    ]);
  }

  /** @test */
  public function it_can_update_an_order()
  {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $product = Product::factory()->create(['price' => 40000]);

    $updateData = [
      'items' => [['product_id' => $product->id, 'quantity' => 1]]
    ];

    $updatedOrder = $this->orderService->updateOrder($order->id, $updateData);

    $this->assertDatabaseHas('order_items', [
      'order_id' => $updatedOrder->id,
      'product_id' => $product->id,
      'quantity' => 1
    ]);
  }

  /** @test */
  public function it_can_confirm_an_order()
  {
    $order = Order::factory()->create(['order_status' => 'pending']);
    $this->orderService->confirmOrder($order->id);

    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'order_status' => 'confirmed'
    ]);
  }

  /** @test */
  public function it_can_cancel_an_order()
  {
    $order = Order::factory()->create(['order_status' => 'confirmed']);
    $this->orderService->cancelOrder($order->id);

    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'order_status' => 'cancelled'
    ]);
  }

  /** @test */
  public function it_can_mark_an_order_as_completed_and_create_invoice()
  {
    $order = Order::factory()->create(['order_status' => 'confirmed']);
    $this->orderService->markAsCompleted($order->id, 100000);

    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'order_status' => 'completed'
    ]);

    $this->assertDatabaseHas('invoices', [
      'order_id' => $order->id,
      'paid_amount' => 100000
    ]);
  }

  /** @test */
  public function it_can_update_order_status()
  {
    $order = Order::factory()->create(['order_status' => 'pending']);
    $this->orderService->updateOrderStatus($order->id, 'completed');

    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'order_status' => 'completed'
    ]);
  }

  /** @test */
  public function it_can_find_an_order_by_code()
  {
    $order = Order::factory()->create(['order_code' => 'ORD-001']);
    $foundOrder = $this->orderService->findOrderByCode('ORD-001');

    $this->assertNotNull($foundOrder);
    $this->assertEquals('ORD-001', $foundOrder->order_code);
  }

  /** @test */
  public function it_can_get_orders_with_pagination()
  {
    Order::factory()->count(15)->create();
    $orders = $this->orderService->getOrders(10);

    $this->assertEquals(10, $orders->count());
  }



  /** @test */
  public function it_can_apply_a_voucher()
  {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price' => 100000]);
    $voucher = Voucher::factory()->create([
      'code' => 'DISCOUNT10K',
      'discount_value' => 10000,
      'discount_type' => 'fixed',
      'usage_limit' => 5,
      'applied_count' => 0,
      'min_order_value' =>  0
    ]);

    $orderData = [
      'customer_id' => $customer->id,
      'branch_id' => $this->branch->id,
      'voucher_code' => 'DISCOUNT10K',
      'items' => [['product_id' => $product->id, 'quantity' => 2]]
    ];

    $order = $this->orderService->createOrder($orderData);

    $this->assertEquals(10000, $order->discount_amount);
    $this->assertEquals(190000, $order->total_price);
    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'voucher_code' => 'DISCOUNT10K'
    ]);

    // Kiểm tra voucher đã được sử dụng một lần
    $this->assertDatabaseHas('vouchers', [
      'id' => $voucher->id,
      'applied_count' => 1
    ]);
  }

  /** @test */
  public function it_cannot_apply_an_invalid_voucher()
  {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price' => 100000]);
    $voucher = Voucher::factory()->create([
      'code' => 'INVALID',
      'discount_value' => 10000,
      'discount_type' => 'fixed',
      'is_active' => false // Voucher không hoạt động
    ]);

    $orderData = [
      'customer_id' => $customer->id,
      'branch_id' => $this->branch->id,
      'voucher_code' => $voucher->code,
      'items' => [['product_id' => $product->id, 'quantity' => 2]]
    ];
    $order = $this->orderService->createOrder($orderData);

    $this->assertEquals(0, $order->discount_amount);
    $this->assertEquals(200000, $order->total_price); // Giá không thay đổi
    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'voucher_code' => null // Không áp dụng voucher
    ]);
  }
}
