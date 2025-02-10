<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
  use RefreshDatabase;

  protected ProductService $productService;
  protected OrderService $orderService;
  protected Branch $branch;

  protected function setUp(): void
  {
    parent::setUp();
    $this->productService = new ProductService();
    $this->orderService = new OrderService();

    $this->branch = Branch::factory()->create();
  }

  public function test_order_code_is_generated_correctly()
  {
    $order = Order::factory()->create(['branch_id' => $this->branch->id]);

    $this->assertMatchesRegularExpression('/ORD-\d{2}-\d{6}-\d{4}/', $order->order_code);
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
      'status' => 'pending',
      'note' => 'Giao hàng nhanh',
      'items' => [
        ['product_id' => $product1->id, 'quantity' => 2],
        ['product_id' => $product2->id, 'quantity' => 1],
      ]
    ];

    $order = $this->orderService->createOrder($orderData);

    // Kiểm tra đơn hàng được tạo
    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'customer_id' => $customer->id,
      'status' => 'pending',
    ]);

    // Kiểm tra sản phẩm có trong đơn hàng
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
   * Kiểm tra tạo đơn hàng có sản phẩm và topping
   */
  public function test_create_order_with_products_and_toppings()
  {
    $customer = Customer::factory()->create();
    $topping1 = Product::factory()->create(['price' => 10000]);
    $topping2 = Product::factory()->create(['price' => 15000]);
    $product = Product::factory()->create(['price' => 50000]);
    $this->productService->updateProduct($product->id, [
      'toppings' =>  [$topping1->id, $topping2->id]
    ]);

    $orderData = [
      'customer_id' => $customer->id,
      'branch_id' => $this->branch->id,
      'status' => 'pending',
      'note' => 'Ít đá',
      'items' => [
        [
          'product_id' => $product->id,
          'quantity' => 2,
          'toppings' => [$topping1->id, $topping2->id],
        ]
      ]
    ];

    $order = $this->orderService->createOrder($orderData);

    // Kiểm tra đơn hàng được tạo
    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'customer_id' => $customer->id,
      'status' => 'pending',
    ]);

    // Kiểm tra sản phẩm có trong đơn hàng
    $orderItem = OrderItem::where('order_id', $order->id)->first();
    $this->assertNotNull($orderItem);

    $this->assertDatabaseHas('order_items', [
      'order_id' => $order->id,
      'product_id' => $product->id,
      'quantity' => 2,
      'unit_price' => 50000,
      'total_price' => 100000,
    ]);

    // Kiểm tra topping có trong đơn hàng
    $this->assertDatabaseHas('order_toppings', [
      'order_item_id' => $orderItem->id,
      'topping_id' => $topping1->id,
      'unit_price' => $topping1->price,
    ]);

    $this->assertDatabaseHas('order_toppings', [
      'order_item_id' => $orderItem->id,
      'topping_id' => $topping2->id,
      'unit_price' => $topping2->price,
    ]);
  }

  /**
   * Kiểm tra cập nhật thông tin đơn hàng (status, note, branch)
   */
  public function test_update_order_info()
  {
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price' => 50000]);
    // Tạo đơn hàng ban đầu
    $order = $this->orderService->saveOrder([
      'customer_id' => $customer->id,
      'branch_id' => $branch->id,
      'status' => 'pending',
      'items' => [
        ['product_id' => $product->id, 'quantity' => 2],
      ]
    ]);
    $newBrand = Branch::factory()->create();
    $updateData = [
      'status' => 'confirmed',
      'note' => 'Giao vào buổi sáng',
      'branch_id' => $newBrand->id
    ];

    $this->orderService->updateOrder($order->id, $updateData);

    // Kiểm tra thông tin đơn hàng đã được cập nhật
    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'status' => 'confirmed',
      'note' => 'Giao vào buổi sáng',
      'branch_id' => $newBrand->id,
    ]);
  }

  /**
   * Kiểm tra cập nhật danh sách sản phẩm trong đơn hàng
   */
  public function test_update_order_items()
  {
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->create();
    $product1 = Product::factory()->create(['price' => 50000]);
    $product2 = Product::factory()->create(['price' => 40000]);

    // Tạo đơn hàng ban đầu
    $order = $this->orderService->saveOrder([
      'customer_id' => $customer->id,
      'branch_id' => $branch->id,
      'status' => 'pending',
      'items' => [
        ['product_id' => $product1->id, 'quantity' => 2],
      ]
    ]);

    // Cập nhật danh sách sản phẩm (xóa product1, thêm product2)
    $updateData = [
      'items' => [
        ['product_id' => $product2->id, 'quantity' => 3], // Thay sản phẩm
      ]
    ];

    $this->orderService->updateOrder($order->id, $updateData);

    // Kiểm tra sản phẩm cũ đã bị xóa
    $this->assertDatabaseMissing('order_items', [
      'order_id' => $order->id,
      'product_id' => $product1->id,
    ]);

    // Kiểm tra sản phẩm mới đã được thêm
    $this->assertDatabaseHas('order_items', [
      'order_id' => $order->id,
      'product_id' => $product2->id,
      'quantity' => 3,
      'unit_price' => $product2->price,
      'total_price' =>  $product2->price * 3,
    ]);
  }

  /**
   * Kiểm tra cập nhật topping của sản phẩm trong đơn hàng
   */
  public function test_update_order_toppings()
  {
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->create();

    // Tạo sản phẩm chính
    $product = Product::factory()->create(['price' => 50000]);

    // Tạo topping và gán vào sản phẩm chính
    $topping1 = Product::factory()->create(['price' => 10000]);
    $topping2 = Product::factory()->create(['price' => 15000]);
    $topping3 = Product::factory()->create(['price' => 20000]);

    // Gán toppings vào sản phẩm chính thông qua ProductService
    $this->productService->updateProduct($product->id, [
      'toppings' => [$topping1->id, $topping2->id, $topping3->id]
    ]);

    $dataOrderItems = [
      [
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 50000,
        'toppings' => [
          $topping1->id,
          $topping2->id
        ]
      ]
    ];
    // Tạo đơn hàng với sản phẩm và topping ban đầu
    $order = $this->orderService->createOrder([
      'customer_id' => $customer->id,
      'branch_id' => $branch->id,
      'status' => 'pending',
      'items' => $dataOrderItems
    ]);

    $orderItem = OrderItem::where('order_id', $order->id)->first();
    // Cập nhật topping: Thay topping1,topping2 bằng topping3
    $dataUpdateOrderItems = [
      [
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 50000,
        'toppings' => [
          $topping3->id
        ]
      ]
    ];

    $this->orderService->updateOrder($order->id, [
      'items' => $dataUpdateOrderItems
    ]);
    // Kiểm tra topping cũ bị xóa
    $this->assertDatabaseMissing('order_toppings', [
      'topping_id' => $topping1->id,
    ]);
    $this->assertDatabaseMissing('order_toppings', [
      'topping_id' => $topping2->id,
    ]);

    // Kiểm tra topping mới đã được thêm
    $this->assertDatabaseHas('order_toppings', [
      'topping_id' => $topping3->id,
      'unit_price' => $topping3->price,
    ]);
  }

  public function test_order_history_is_recorded_when_status_changes()
  {
    $order = Order::factory()->create(['status' => 'pending']);

    // Cập nhật trạng thái đơn hàng
    $this->orderService->updateOrderStatus($order->id, 'confirmed');

    // Kiểm tra lịch sử thay đổi trạng thái
    $this->assertDatabaseHas('order_histories', [
      'order_id' => $order->id,
      'old_status' => 'pending',
      'new_status' => 'confirmed',
      'note'      => 'Cập nhật trạng thái đơn hàng.'
    ]);
  }

  public function test_mark_order_as_completed_creates_invoice_with_items_and_toppings()
  {

    // Tạo sản phẩm chính
    $product = Product::factory()->create(['price' => 50000]);

    // Tạo topping và gán vào sản phẩm chính
    $topping = Product::factory()->create(['price' => 10000]);

    // Gán toppings vào sản phẩm chính thông qua ProductService
    $this->productService->updateProduct($product->id, [
      'toppings' => [$topping->id]
    ]);

    $dataOrderItems = [
      [
        'product_id' => $product->id,
        'quantity' => 2,
        'toppings' => [
          $topping->id
        ]
      ]
    ];

    // Tạo đơn hàng với sản phẩm và topping ban đầu
    $order = Order::factory()->create(['status' => 'confirmed']);
    $order = $this->orderService->updateOrder($order->id, [
      'items' => $dataOrderItems
    ]);
    $paymentMethod = 'momo';
    $paidAmount = 100000;
    $total_amount = ($product->price + $topping->price) * 2;
    // Hoàn tất đơn hàng
    $this->orderService->markAsCompleted($order->id, $paymentMethod, $paidAmount);

    // Kiểm tra trạng thái đơn hàng đã chuyển sang `completed`
    $this->assertDatabaseHas('orders', [
      'id' => $order->id,
      'status' => 'completed',
    ]);

    // Kiểm tra hóa đơn đã được tạo
    $invoice = \App\Models\Invoice::where('order_id', $order->id)->first();
    $this->assertNotNull($invoice);
    $this->assertEquals('pending', $invoice->invoice_status);

    // Kiểm tra hoá đơn
    $this->assertDatabaseHas('invoices', [
      'order_id' => $order->id,
      'customer_id' => $order->customer_id,
      'branch_id' => $order->branch_id,
      'discount_amount' => $order->discount_amount,
      'paid_amount' => $paidAmount,
      'payment_method'  =>  $paymentMethod,
      'note' => $order->note,
      'total_amount' => $total_amount,
    ]);

    // Kiểm tra sản phẩm có trong hóa đơn
    $this->assertDatabaseHas('invoice_items', [
      'invoice_id' => $invoice->id,
      'product_id' => $product->id,
      'quantity' => 2,
      'unit_price' => $product->price,
      'total_price' => $product->price * 2,
      'total_price_with_topping' => $total_amount
    ]);

    // Kiểm tra topping có trong hóa đơn
    $invoiceItem = \App\Models\InvoiceItem::where('invoice_id', $invoice->id)->first();
    $this->assertNotNull($invoiceItem);

    $this->assertDatabaseHas('invoice_toppings', [
      'invoice_item_id' => $invoiceItem->id,
      'topping_id' => $topping->id,
      'unit_price' => $topping->price,
    ]);
  }
}
