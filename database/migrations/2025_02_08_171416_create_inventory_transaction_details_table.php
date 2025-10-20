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
    Schema::create('inventory_transaction_details', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('inventory_transaction_id')->constrained()->cascadeOnDelete(); // Giao dịch tồn kho
      $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Sản phẩm
      $table->decimal('quantity', 10, 2); // Số lượng sản phẩm
      $table->decimal('cost_price', 15, 2)->nullable(); // Giá nhập (chỉ dùng cho giao dịch nhập kho)
      $table->decimal('sale_price', 15, 2)->nullable(); // Giá bán (chỉ dùng cho giao dịch bán hàng)
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('inventory_transaction_details');
  }
};
