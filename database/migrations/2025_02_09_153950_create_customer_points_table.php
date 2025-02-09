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
    Schema::create('customer_points', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('customer_id')->constrained()->onDelete('cascade'); // Liên kết đến bảng customers
      $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete(); // Liên kết đến invoices (hoá đơn)
      $table->integer('points'); // Số điểm cộng/trừ
      $table->enum('type', ['earn', 'redeem', 'expired'])->default('earn'); // Loại điểm: tích lũy, sử dụng, hết hạn
      $table->text('description')->nullable(); // Mô tả giao dịch điểm
      $table->dateTime('expired_at')->nullable(); // Ngày hết hạn điểm (nếu có)
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customer_points');
  }
};
