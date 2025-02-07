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
    Schema::create('vouchers', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->string('code')->unique(); // Mã voucher
      $table->string('description')->unique(); // Mô tả voucher
      $table->decimal('amount', 10, 2); // Số tiền hoặc phần trăm giảm giá
      $table->enum('type', ['fixed', 'percentage']); // Loại voucher (số tiền cố định hoặc phần trăm)
      $table->dateTime('start_date')->nullable(); // Ngày bắt đầu áp dụng
      $table->dateTime('end_date')->nullable(); // Ngày kết thúc áp dụng
      $table->integer('usage_limit')->nullable(); // Giới hạn số lần sử dụng
      $table->integer('used_count')->default(0); // Số lần đã sử dụng
      $table->boolean('is_active')->default(true); // Trạng thái voucher
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('vouchers');
  }
};
