<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('order_toppings', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete(); // Sản phẩm trong đơn hàng
      $table->foreignId('topping_id')->nullable()->constrained('products')->nullOnDelete(); // Sản phẩm topping
      $table->decimal('unit_price', 15, 2); // Giá topping
    });
  }

  public function down()
  {
    Schema::dropIfExists('order_toppings');
  }
};
