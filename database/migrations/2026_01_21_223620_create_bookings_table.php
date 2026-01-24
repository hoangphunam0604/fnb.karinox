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
    Schema::create('bookings', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
      $table->foreignId('table_id')->nullable()->constrained('tables_and_rooms')->nullOnDelete(); // Sân
      $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Nhân viên bán hàng
      $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete(); // Người nhận đơn
      $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete(); // Khách hàng
      $table->enum('type', ['full', 'social']); //Bao sân | vé lẻ social
      $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending'); // Trạng thái booking
      $table->dateTime('start_time');
      $table->dateTime('end_time');
      $table->integer('duration_hours');
      $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete(); // Order item tạo booking này
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('bookings');
  }
};
