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

    /** status
     * pending -	Đơn hàng mới, chưa xác nhận.
     * confirmed -	Đã xác nhận bởi nhân viên.
     * completed -	Đơn hàng hoàn tất, có thể tạo hóa đơn.
     * canceled -	Đã hủy bởi khách hoặc nhân viên.
     */
    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->string('order_code')->unique(); // Mã đơn hàng
      $table->timestamp('ordered_at')->useCurrent(); // Thời gian đặt hàng
      $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete(); // Người tạo đơn
      $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete(); // Người nhận đơn
      $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete(); // Khách hàng
      $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete(); // Chi nhánh
      $table->foreignId('table_id')->nullable()->constrained('tables_and_rooms')->nullOnDelete(); // Bàn/phòng

      $table->decimal('subtotal_price', 15, 2)->default(0.00); // Tổng tiền đơn hàng trước khi giảm giá (chỉ tính sản phẩm và topping, chưa áp dụng voucher hay điểm thưởng).
      $table->decimal('discount_amount', 15, 2)->default(0.00); // Số tiền giảm từ voucher.
      $table->integer('reward_points_used')->default(0); // Số điểm thưởng khách muốn dùng      
      $table->decimal('reward_discount', 15, 2)->default(0.00); // Số tiền giảm từ điểm thưởng.
      $table->decimal('total_price', 15, 2)->default(0.00); // Số tiền cần thanh toán cuối cùng (sau khi trừ cả voucher và điểm thưởng).

      $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete(); // Mã giảm giá
      $table->string('voucher_code')->nullable();
      $table->enum('order_status', ['pending', 'confirmed', 'completed', 'canceled'])->default('pending'); // Trạng thái đơn hàng
      $table->string('payment_method')->default('cash'); //Hình thức thanh toán (tiền mặt, thẻ, ví điện tử, v.v.).
      $table->text('note')->nullable(); // Ghi chú
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('orders');
  }
};
