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
      $table->foreignId('order_id')->constrained()->nullOnDelete();
      $table->decimal('total_amount', 15, 2); //Tổng số tiền khách cần thanh toán.
      $table->decimal('paid_amount', 15, 2); //Số tiền thực tế khách đã trả.
      $table->decimal('change_amount', 15, 2)->default(0); //Tiền thừa trả lại cho khách (mặc định = 0).
      $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete(); //Nếu khách hàng dùng mã giảm giá, liên kết với bảng vouchers.
      $table->string('payment_method'); //Hình thức thanh toán (tiền mặt, thẻ, ví điện tử, v.v.).
      $table->string('status')->default('paid'); // Trạng thái của hóa đơn (paid, refunded, pending, v.v.).
      $table->text('note')->nullable(); //Ghi chú thêm về hóa đơn.
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
