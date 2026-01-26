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
      $table->string('product_name'); // Tên sản phẩm
      $table->decimal('product_price', 15, 2)->comment('Giá gốc sản phẩm chưa bao gồm topping');
      $table->string('product_type')->nullable();
      $table->string('booking_type', 30)->default('none');
      $table->decimal('unit_price', 15, 2)->comment('Đơn giá, đã bao gồm topping');
      $table->enum('discount_type', ['percent', 'fixed'])->nullable()->comment('Loại giảm giá: percent hoặc fixed');
      $table->decimal('discount_percent', 15, 2)->default(0)->comment('Phần trăm giảm giá (0-100), chỉ dùng khi discount_type = percent');
      $table->decimal('discount_amount', 15, 2)->default(0)->comment('Số tiền giảm giá thực tế: nếu type=percent thì tính từ unit_price * discount_percent/100, nếu type=fixed thì lưu trực tiếp');
      $table->text('discount_note')->nullable(); // Ghi chú giảm giá
      $table->decimal('sale_price', 15, 2)->comment('Giá bán sau giảm giá: unit_price - discount_amount');
      $table->integer('quantity')->default(1); // Số lượng sản phẩm
      $table->decimal('total_price', 15, 2)->comment('Tổng giá: sale_price * quantity');
      $table->enum('status', ['success', 'refunded'])->default('success');
      $table->text('note')->nullable(); // Ghi chú
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
