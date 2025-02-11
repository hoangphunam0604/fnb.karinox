<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('invoices', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->string('code')->unique(); // Mã hóa đơn
      $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
      $table->decimal('total_amount', 15, 2)->default(0); //Tổng số tiền khách cần thanh toán.
      $table->decimal('paid_amount', 15, 2)->default(0); //Số tiền thực tế khách đã trả.
      $table->decimal('change_amount', 15, 2)->default(0); //Tiền thừa trả lại cho khách (mặc định = 0).
      $table->decimal('discount_amount', 15, 2)->default(0.00); // Số tiền giảm giá
      $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete(); //Nếu khách hàng dùng mã giảm giá, liên kết với bảng vouchers.
      $table->string('sales_channel')->default('pos'); //Kênh bán hàng, mặc định pos
      $table->enum('invoice_status', ['pending', 'canceled', 'completed'])->default('pending'); // Trạng thái hóa đơn
      $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid'); // Trạng thái thanh toán
      $table->string('payment_method')->default('cash'); //Hình thức thanh toán (tiền mặt, thẻ, ví điện tử, v.v.).
      $table->text('note')->nullable(); //Ghi chú thêm về hóa đơn.

      // Liên kết với khách hàng (có thể null nếu khách vãng lai)
      $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
      $table->string('loyalty_card_number')->nullable(); // Mã thẻ khách hàng thân thiết (nếu có)
      // Thông tin khách hàng tại thời điểm lập hóa đơn
      $table->string('customer_name')->nullable(); // Tên khách hàng
      $table->string('customer_phone')->nullable(); // Số điện thoại khách hàng
      $table->string('customer_email')->nullable(); // Email khách hàng
      $table->string('customer_address')->nullable(); // Địa chỉ khách hàng
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('invoices');
  }
};
