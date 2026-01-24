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
      $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete(); // Sản phẩm
      $table->string('product_name'); // Tên sản phẩm
      $table->decimal('product_price', 15, 2)->comment('Giá gốc sản phẩm chưa bao gồm topping');
      $table->decimal('unit_price', 15, 2)->comment('Đơn giá, đã bao gồm topping');
      $table->decimal('sale_price', 15, 2)->comment('Giá bán thực tế do nhân viên điều chỉnh, đã bao gồm topping');
      $table->enum('discount_type', ['percent', 'fixed'])->nullable()->comment('Loại giảm giá: percent hoặc fixed');
      $table->decimal('discount_value', 15, 2)->default(0)->comment('Giá trị giảm tương ứng với discount_type (ví dụ 10 cho 10% hoặc 10000 cho 10k)');
      $table->decimal('discount_amount', 15, 2)->default(0)->comment('Số tiền đã giảm thực tế tính trên sale_price * quantity');
      $table->integer('quantity')->default(1); // Số lượng
      $table->decimal('total_price', 15, 2)->comment('Tổng giá: tính theo sale_price (sale_price * quantity - discount_amount)'); // Tổng giá
      $table->enum('status', ['pending', 'accepted', 'preparing', 'prepared', 'serving', 'served', 'canceled', 'refunded'])->default('pending');
      $table->text('note')->nullable(); // Ghi chú
      $table->boolean('print_label'); // In tem (dán ly/giữ lại)
      $table->boolean('printed_label')->default(false); // Đã từng in tem
      $table->timestamp('printed_label_at')->nullable(); // In lúc
      $table->boolean('print_kitchen'); // In phiếu bếp      
      $table->boolean('printed_kitchen')->default(false); // Đã từng in phiếu bếp
      $table->timestamp('printed_kitchen_at')->nullable(); // In phiếu bếp lúc
    });
  }

  public function down()
  {
    Schema::dropIfExists('order_items');
  }
};
