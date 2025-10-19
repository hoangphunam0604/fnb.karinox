<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Customer;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestPrintDataSeeder extends Seeder
{
  /**
   * Run the database seeder.
   */
  public function run(): void
  {
    $this->command->info('Creating test data for print system...');

    // Lấy chi nhánh đầu tiên
    $branch = Branch::first();

    if (!$branch) {
      $this->command->error('No branches found. Please create branches first.');
      return;
    }

    // Lấy các sản phẩm theo loại
    $processedProducts = Product::where('product_type', 'processed')->limit(2)->get();
    $comboProducts = Product::where('product_type', 'combo')->limit(1)->get();
    $goodsProducts = Product::where('product_type', 'goods')->limit(1)->get();

    if ($processedProducts->isEmpty()) {
      $this->command->warn('No processed products found. Creating some...');
      // Tạo sản phẩm processed test
      $processedProducts = collect([
        Product::create([
          'name' => 'Cà phê đen đá',
          'description' => 'Cà phê đen truyền thống',
          'product_type' => 'processed',
          'regular_price' => 25000,
          'sale_price' => 22000,
          'cost_price' => 15000,
          'status' => 'active',
          'creator_id' => 1
        ]),
        Product::create([
          'name' => 'Bánh mì thịt nướng',
          'description' => 'Bánh mì thịt nướng BBQ',
          'product_type' => 'processed',
          'regular_price' => 35000,
          'sale_price' => 32000,
          'cost_price' => 20000,
          'status' => 'active',
          'creator_id' => 1
        ])
      ]);
    }

    if ($goodsProducts->isEmpty()) {
      $goodsProducts = collect([
        Product::create([
          'name' => 'Nước suối Aquafina',
          'description' => 'Nước suối đóng chai 500ml',
          'product_type' => 'goods',
          'regular_price' => 15000,
          'sale_price' => 12000,
          'cost_price' => 8000,
          'status' => 'active',
          'creator_id' => 1
        ])
      ]);
    }

    // Tạo khách hàng test
    $customer = Customer::first() ?? Customer::create([
      'name' => 'Khách hàng test in',
      'phone' => '0987654321',
      'email' => 'test.print@example.com',
      'branch_id' => $branch->id,
      'creator_id' => 1
    ]);

    // Tạo đơn hàng test 1 - Chưa thanh toán (test provisional)
    $order1 = Order::create([
      'order_code' => 'TEST001',
      'branch_id' => $branch->id,
      'customer_id' => $customer->id,
      'table_name' => 'Bàn 05',
      'customer_name' => $customer->name,
      'customer_phone' => $customer->phone,
      'order_status' => OrderStatus::CONFIRMED,
      'payment_status' => PaymentStatus::UNPAID,
      'subtotal' => 0,
      'discount_amount' => 0,
      'total_amount' => 0,
      'creator_id' => 1,
      'notes' => 'Đơn hàng test hệ thống in'
    ]);

    // Tạo items cho order 1
    $items1 = [];

    // Sản phẩm processed - cần in cả tem và phiếu bếp
    $items1[] = OrderItem::create([
      'order_id' => $order1->id,
      'product_id' => $processedProducts->first()->id,
      'product_name' => $processedProducts->first()->name,
      'quantity' => 2,
      'price' => $processedProducts->first()->sale_price,
      'total_amount' => $processedProducts->first()->sale_price * 2,
      'print_label' => true,
      'print_kitchen' => true,
      'printed_label' => false,
      'printed_kitchen' => false
    ]);

    // Sản phẩm goods - chỉ cần in tem
    $items1[] = OrderItem::create([
      'order_id' => $order1->id,
      'product_id' => $goodsProducts->first()->id,
      'product_name' => $goodsProducts->first()->name,
      'quantity' => 1,
      'price' => $goodsProducts->first()->sale_price,
      'total_amount' => $goodsProducts->first()->sale_price,
      'print_label' => true,
      'print_kitchen' => false,
      'printed_label' => false,
      'printed_kitchen' => false
    ]);

    // Cập nhật tổng tiền order 1
    $order1->update([
      'subtotal' => $items1[0]->total_amount + $items1[1]->total_amount,
      'total_amount' => $items1[0]->total_amount + $items1[1]->total_amount
    ]);

    // Tạo đơn hàng test 2 - Đã thanh toán (test invoice)
    $order2 = Order::create([
      'order_code' => 'TEST002',
      'branch_id' => $branch->id,
      'customer_id' => $customer->id,
      'table_name' => 'Bàn 10',
      'customer_name' => $customer->name,
      'customer_phone' => $customer->phone,
      'order_status' => OrderStatus::COMPLETED,
      'payment_status' => PaymentStatus::PAID,
      'subtotal' => 0,
      'discount_amount' => 5000,
      'total_amount' => 0,
      'creator_id' => 1,
      'notes' => 'Đơn hàng đã thanh toán - test in hóa đơn'
    ]);

    // Items cho order 2
    $items2 = [];

    if ($processedProducts->count() > 1) {
      $items2[] = OrderItem::create([
        'order_id' => $order2->id,
        'product_id' => $processedProducts->get(1)->id,
        'product_name' => $processedProducts->get(1)->name,
        'quantity' => 1,
        'price' => $processedProducts->get(1)->sale_price,
        'total_amount' => $processedProducts->get(1)->sale_price,
        'print_label' => true,
        'print_kitchen' => true,
        'printed_label' => false,
        'printed_kitchen' => false,
        'notes' => 'Ít đường, nhiều đá'
      ]);

      // Cập nhật tổng tiền order 2
      $order2->update([
        'subtotal' => $items2[0]->total_amount,
        'total_amount' => $items2[0]->total_amount - 5000 // trừ discount
      ]);
    }

    $this->command->info('✅ Created test orders:');
    $this->command->line("  - Order 1: {$order1->order_code} (Unpaid) - {$order1->items->count()} items");
    $this->command->line("  - Order 2: {$order2->order_code} (Paid) - {$order2->items->count()} items");

    $this->command->info('🧪 Test with these order IDs:');
    $this->command->line("  - Provisional/Labels/Kitchen: {$order1->id}");
    $this->command->line("  - Invoice: {$order2->id}");
  }
}
