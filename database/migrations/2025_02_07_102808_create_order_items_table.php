<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('order_items', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete(); // Đơn hàng
      $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // Sản phẩm
      $table->string('product_name'); // Số lượng
      $table->integer('quantity')->default(1); // Số lượng
      $table->decimal('unit_price', 15, 2); // Giá mỗi sản phẩm
      $table->decimal('total_price', 15, 2); // Tổng giá
      $table->decimal('total_price_with_topping', 15, 2)->default(0);
      $table->enum('status', ['pending', 'accepted', 'preparing', 'prepared', 'serving', 'served', 'canceled', 'refunded'])->default('pending');
      $table->string('note')->nullable(); // Ghi chú
    });
  }

  public function down()
  {
    Schema::dropIfExists('order_items');
  }
};
