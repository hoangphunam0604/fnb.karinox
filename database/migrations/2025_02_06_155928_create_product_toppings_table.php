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
    Schema::create('product_toppings', function (Blueprint $table) {
      $table->foreignId('product_id')->constrained()->onDelete('cascade');
      $table->foreignId('topping_id')->constrained('products')->onDelete('cascade');
      $table->primary(['product_id', 'topping_id']); // Khóa chính là cặp product_id + topping_id
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('product_toppings');
  }
};
