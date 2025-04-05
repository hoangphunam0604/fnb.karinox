<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::dropIfExists('voucher_usages'); // Xóa bảng cũ nếu đã tồn tại

    Schema::create('voucher_usages', function (Blueprint $table) {
      $table->unsignedBigInteger('order_extend_id')->nullable(); // Người tạo đơn
      $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
      $table->foreignId('order_id')->constrained('orders')->onDelete('cascade'); // Lưu voucher sử dụng khi đặt hàng
      $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade'); // Giả sử khách hàng lưu trong bảng `users`
      $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade'); // Lưu voucher vào hóa đơn khi hoàn tất đơn hàng
      $table->timestamp('used_at')->useCurrent(); // Thời điểm sử dụng
      $table->decimal('discount_amount', 10, 2); // Số tiền giảm giá thực tế
      $table->decimal('invoice_total_before_discount', 10, 2);
      $table->decimal('invoice_total_after_discount', 10, 2);

      $table->primary(['voucher_id', 'order_id']); // Đảm bảo mỗi khách hàng chỉ dùng 1 voucher trên mỗi đơn hàng
    });
  }

  public function down()
  {
    Schema::dropIfExists('voucher_usages');
  }
};
