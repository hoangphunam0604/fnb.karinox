<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('vouchers', function (Blueprint $table) {
      $table->id(); // ID tự động tăng
      $table->timestamps(); // Thời gian tạo & cập nhật

      $table->string('code')->unique(); // Mã voucher (duy nhất)
      $table->enum('discount_type', ['percentage', 'fixed'])->default('fixed'); // Loại giảm giá
      $table->decimal('discount_value', 10, 2); // Giá trị giảm giá (số tiền hoặc %)
      $table->decimal('min_order_value', 10, 2)->nullable(); // Giá trị đơn hàng tối thiểu
      $table->decimal('max_discount', 10, 2)->nullable(); // Số tiền giảm tối đa (nếu là phần trăm)
      $table->integer('usage_limit')->default(1); // Giới hạn số lần sử dụng
      $table->integer('used_count')->default(0); // Số lần đã sử dụng
      $table->timestamp('expires_at')->nullable(); // Ngày hết hạn
      $table->enum('status', ['active', 'inactive'])->default('active'); // Trạng thái voucher
    });
  }

  public function down()
  {
    Schema::dropIfExists('vouchers');
  }
};
