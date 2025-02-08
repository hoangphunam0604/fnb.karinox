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
    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->timestamp('ordered_at')->useCurrent(); // Thời gian đặt hàng
      $table->foreignId('creator_id')->constrained('users')->nullOnDelete(); // Người tạo đơn
      $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete(); // Người nhận đơn
      $table->foreignId('branch_id')->constrained()->nullOnDelete(); // Chi nhánh thực hiện đơn hàng
      $table->foreignId('table_id')->nullable()->constrained('tables_and_rooms')->nullOnDelete(); // Bàn / Phòng
      $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // Khách hàng (nếu có)
      $table->string('order_code')->unique(); // Mã đơn hàng
      $table->decimal('total_amount', 15, 2); // Tổng tiền đơn hàng (đã bao gồm thuế)
      $table->decimal('discount_amount', 15, 2)->default(0); // Số tiền giảm giá (nếu có)
      $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
      $table->string('voucher_code')->nullable()->unique(); // Mã đơn hàng
      $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending'); // Trạng thái thanh toán
      $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending'); // Trạng thái đơn hàng
      $table->string('notes')->nullable(); // Ghi chú
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
