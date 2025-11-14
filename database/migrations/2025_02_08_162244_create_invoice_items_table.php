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
      $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete(); // Sản phẩm liên kết
      $table->string('product_name'); // Số lượng
      $table->decimal('product_price', 15, 2)->comment('Giá gốc sản phẩm chưa bao gồm topping');
      $table->decimal('unit_price', 15, 2)->comment('Đơn giá, đã bao gồm topping');
      $table->integer('quantity')->default(1); // Số lượng sản phẩm
      $table->decimal('total_price', 15, 2);
      $table->enum('status', ['success', 'refunded'])->default('success');
      $table->string('note')->nullable(); // Ghi chú
      $table->boolean('print_label'); // In tem (dán ly/giữ lại)
      $table->boolean('printed_label')->default(false); // Đã từng in tem
      $table->timestamp('printed_label_at')->nullable(); // In lúc
      $table->boolean('print_kitchen'); // In phiếu bếp      
      $table->boolean('printed_kitchen')->default(false); // Đã từng in phiếu bếp
      $table->timestamp('printed_kitchen_at')->nullable(); // In phiếu bếp lúc
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
