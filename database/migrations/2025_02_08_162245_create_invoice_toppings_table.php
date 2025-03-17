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
    Schema::create('invoice_toppings', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('invoice_item_id')->constrained('invoice_items')->cascadeOnDelete(); // Sản phẩm trong hóa đơn
      $table->foreignId('topping_id')->nullable()->constrained('products')->nullOnDelete(); // Topping liên kết
      $table->integer('quantity')->default(1); // Số lượng
      $table->decimal('unit_price', 15, 2); // Giá topping
      $table->decimal('total_price', 15, 2); // Tổng giá
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('invoice_toppings');
  }
};
