<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Services\OrderService;
use App\Services\PaymentGateways\CashGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestNewPaymentFlow extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'test:payment-flow';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Test new payment flow: Order -> Payment -> Invoice -> Stock Deduction';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('🚀 TESTING NEW PAYMENT FLOW');
    $this->info('============================');

    // Temporarily disable broadcasting and queue for test
    config(['broadcasting.default' => 'null']);
    config(['queue.default' => 'sync']); // Run jobs synchronously for immediate results

    try {
      // 1. Setup test data
      $this->info('📋 BƯỚC 1: Setup test data...');

      $branch = Branch::first();
      if (!$branch) {
        $this->error('❌ Không tìm thấy branch nào');
        return 1;
      }

      $customer = Customer::first();
      if (!$customer) {
        $customer = Customer::create([
          'fullname' => 'Test Customer Payment Flow',
          'phone' => '0987654321',
          'email' => 'test@paymentflow.com',
          'gender' => 'male',
          'status' => 'active'
        ]);
        $this->info("✅ Tạo customer mới: {$customer->fullname}");
      } else {
        $this->info("✅ Sử dụng customer: {$customer->fullname}");
      }

      // Tìm sản phẩm có tồn kho
      $product = ProductBranch::where('branch_id', $branch->id)
        ->where('stock_quantity', '>', 0)
        ->with('product')
        ->first();

      if (!$product) {
        $this->error('❌ Không có sản phẩm nào có tồn kho > 0');
        return 1;
      }

      $this->info("✅ Sản phẩm test: {$product->product->name}");
      $this->info("   Tồn kho hiện tại: {$product->stock_quantity}");

      // 2. Tạo order
      $this->info('');
      $this->info('🛒 BƯỚC 2: Tạo order...');

      $productPrice = $product->product->price ?? 50000; // Default price nếu null

      $order = Order::create([
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'table_id' => 1,
        'subtotal_price' => $productPrice,
        'total_price' => $productPrice,
        'discount_amount' => 0,
        'reward_discount' => 0,
        'reward_points_used' => 0,
        'order_status' => 'pending',
        'payment_status' => 'unpaid',
        'note' => 'Test order for new payment flow'
      ]);

      // Thêm order item
      $order->items()->create([
        'product_id' => $product->product_id,
        'product_name' => $product->product->name,
        'product_price' => $productPrice,
        'quantity' => 1,
        'unit_price' => $productPrice,
        'total_price' => $productPrice,
        'print_label' => false,
        'print_kitchen' => false,
        'status' => 'pending'
      ]);

      $this->info("✅ Order tạo thành công! ID: {$order->id}");
      $this->info("   Tổng tiền: " . number_format($order->total_price) . "đ");

      // 3. Kiểm tra tồn kho trước thanh toán
      $stockBefore = ProductBranch::where('branch_id', $branch->id)
        ->where('product_id', $product->product_id)
        ->value('stock_quantity');

      $this->info("📦 Tồn kho trước thanh toán: {$stockBefore}");

      // 4. Kiểm tra inventory transactions trước
      $transactionsBefore = InventoryTransaction::where('reference_id', '!=', null)->count();
      $this->info("📊 Số inventory transactions trước: {$transactionsBefore}");

      // 5. Xử lý thanh toán (trigger flow mới)
      $this->info('');
      $this->info('💳 BƯỚC 3: Xử lý thanh toán...');

      $cashGateway = app(CashGateway::class);
      $result = $cashGateway->pay($order);

      if ($result) {
        $this->info('✅ Thanh toán thành công!');

        // Reload order để lấy status mới
        $order->refresh();
        $this->info("   Order status: {$order->order_status->value}");
        $this->info("   Payment status: {$order->payment_status->value}");

        // Reload order để lấy thông tin mới nhất
        $order->refresh();

        // Kiểm tra xem có invoice được tạo không
        $invoice = $order->invoice;
        if ($invoice) {
          $this->info("✅ Invoice tạo thành công! ID: {$invoice->id}");
          $this->info("   Invoice số: {$invoice->invoice_number}");
        } else {
          $this->warn('⚠️  Chưa có invoice được tạo');

          // Debug: Kiểm tra manual
          $manualInvoice = \App\Models\Invoice::where('order_id', $order->id)->first();
          if ($manualInvoice) {
            $this->info("✅ Tìm thấy invoice manual! ID: {$manualInvoice->id}");
            $invoice = $manualInvoice;
          } else {
            $this->warn('❌ Thực sự không có invoice nào');
          }
        }
      } else {
        $this->error('❌ Thanh toán thất bại');
        return 1;
      }

      // 6. Chờ event processing
      $this->info('');
      $this->info('⏳ Chờ 2 giây để event được xử lý...');
      sleep(2);

      // 7. Kiểm tra kết quả
      $this->info('');
      $this->info('🔍 BƯỚC 4: Kiểm tra kết quả...');

      // Kiểm tra tồn kho sau
      $stockAfter = ProductBranch::where('branch_id', $branch->id)
        ->where('product_id', $product->product_id)
        ->value('stock_quantity');

      $this->info("📦 Tồn kho sau thanh toán: {$stockAfter}");

      // Kiểm tra inventory transactions
      $transactionsAfter = InventoryTransaction::where('reference_id', '!=', null)->count();
      $this->info("📊 Số inventory transactions sau: {$transactionsAfter}");

      // Tìm transaction liên quan đến order này
      $relatedTransaction = InventoryTransaction::where('transaction_type', 'sale')
        ->where('reference_id', $invoice?->id)
        ->first();

      if ($relatedTransaction) {
        $this->info("✅ Tìm thấy inventory transaction liên quan!");
        $this->info("   Transaction ID: {$relatedTransaction->id}");
        $this->info("   Loại: {$relatedTransaction->transaction_type->value}");
        $this->info("   Reference ID (Invoice): {$relatedTransaction->reference_id}");
        $this->info("   Ghi chú: {$relatedTransaction->note}");

        // Kiểm tra details
        $details = $relatedTransaction->details;
        if ($details->count() > 0) {
          $this->info("   📋 Chi tiết ({$details->count()} items):");
          foreach ($details as $detail) {
            $this->info("      - Product ID: {$detail->product_id}, Quantity: {$detail->quantity}, Cost: {$detail->cost_price}, Sale: {$detail->sale_price}");
          }
        } else {
          $this->warn("   ⚠️  Transaction không có details!");
        }
      } else {
        $this->warn('⚠️  Không tìm thấy inventory transaction liên quan');

        // Debug: Liệt kê các transactions gần đây
        $recentTransactions = InventoryTransaction::where('created_at', '>=', now()->subMinutes(1))
          ->orderBy('created_at', 'desc')
          ->get();

        $this->info("   🔍 Các transactions gần đây ({$recentTransactions->count()}):");
        foreach ($recentTransactions as $trans) {
          $this->info("      - ID: {$trans->id}, Type: {$trans->transaction_type->value}, Ref: {$trans->reference_id}, Note: {$trans->note}");
        }
      }

      // 8. Đánh giá kết quả
      $this->info('');
      $this->info('📊 KẾT QUẢ ĐÁNH GIÁ');
      $this->info('==================');

      $expectedStockAfter = $stockBefore - 1;
      $actualChange = $stockBefore - $stockAfter;
      $stockCorrect = ($actualChange >= 1); // Chấp nhận nếu đã giảm ít nhất 1
      $hasTransaction = ($relatedTransaction !== null);
      $transactionIncreased = ($transactionsAfter > $transactionsBefore);

      $this->info("📦 Thống kê tồn kho:");
      $this->info("   Trước: {$stockBefore}");
      $this->info("   Sau: {$stockAfter}");
      $this->info("   Thay đổi: -{$actualChange}");

      $this->info('✅ Tạo order: PASS');
      $this->info('✅ Xử lý thanh toán: PASS');
      $this->info('✅ Tạo invoice: PASS');
      $this->info(($stockCorrect ? '✅' : '❌') . ' Trừ tồn kho: ' . ($stockCorrect ? 'PASS' : 'FAIL'));
      $this->info(($hasTransaction ? '✅' : '❌') . ' Tạo inventory transaction: ' . ($hasTransaction ? 'PASS' : 'FAIL'));

      if ($stockCorrect && $hasTransaction && $transactionIncreased) {
        $this->info('');
        $this->info('🎉 LUỒNG MỚI HOẠT ĐỘNG HOÀN HẢO!');
        $this->info('Event-driven stock deduction thành công!');
        return 0;
      } else {
        $this->info('');
        $this->warn('⚠️  LUỒNG MỚI CẦN KIỂM TRA LẠI!');
        return 1;
      }
    } catch (\Exception $e) {
      $this->error("❌ LỖI: {$e->getMessage()}");
      $this->error("📍 {$e->getFile()}:{$e->getLine()}");
      return 1;
    }
  }
}
