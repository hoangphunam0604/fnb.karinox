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
    Schema::create('invoice_items', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete(); // Hóa đơn liên kết
      $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // Sản phẩm liên kết
      $table->string('product_name'); // Số lượng
      $table->integer('quantity')->default(1); // Số lượng sản phẩm
      $table->decimal('unit_price', 15, 2); // Giá mỗi sản phẩm
      $table->decimal('total_price', 15, 2);
      $table->text('note')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('invoice_items');
  }
};
