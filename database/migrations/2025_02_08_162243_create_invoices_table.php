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

      $table->decimal('subtotal_price', 15, 2)->default(0.00); // Tổng tiền đơn hàng trước khi giảm giá (chỉ tính sản phẩm và topping, chưa áp dụng voucher hay điểm thưởng).
      $table->decimal('discount_amount', 15, 2)->default(0.00); // Số tiền giảm từ voucher.
      $table->decimal('reward_discount', 15, 2)->default(0.00); // Số tiền giảm từ điểm thưởng.
      $table->decimal('total_price', 15, 2)->default(0.00); // Số tiền cần thanh toán cuối cùng (sau khi trừ cả voucher và điểm thưởng).

      $table->decimal('paid_amount', 15, 2)->default(0); //Số tiền thực tế khách đã trả.
      $table->decimal('change_amount', 15, 2)->default(0); //Tiền thừa trả lại cho khách (mặc định = 0).

      $table->decimal('tax_rate', 5, 2)->nullable(); // Tỷ lệ thuế (%)
      $table->decimal('tax_amount', 15, 2)->default(0); // Tiền thuế phải trả
      $table->decimal('total_price_without_vat', 15, 2)->default(0); // Tổng tiền trước thuế

      $table->integer('reward_points_used')->default(0); // Số điểm thưởng khách muốn dùng      
      $table->integer('earned_loyalty_points')->default(0); // Số điểm tích luỹ đạt được từ đơn hàng này
      $table->integer('earned_reward_points')->default(0); // Số điểm tích thưởng đạt được từ đơn hàng này

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
