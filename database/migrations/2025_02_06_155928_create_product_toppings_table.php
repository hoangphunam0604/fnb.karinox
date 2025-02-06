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
      $table->id();
      $table->timestamps();
      $table->foreignId('product_id')->constrained()->onDelete('cascade');
      $table->foreignId('topping_id')->constrained('products')->onDelete('cascade');
      $table->decimal('extra_price', 10, 2)->default(0);
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
