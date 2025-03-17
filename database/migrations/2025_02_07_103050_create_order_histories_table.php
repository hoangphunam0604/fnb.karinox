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
    Schema::create('order_histories', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Người thực hiện thay đổi
      $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Đơn hàng
      $table->string('old_status')->nullable(); // Trạng thái cũ
      $table->string('new_status'); // Trạng thái mới
      $table->text('note')->nullable(); // Ghi chú về thay đổi
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('order_histories');
  }
};
