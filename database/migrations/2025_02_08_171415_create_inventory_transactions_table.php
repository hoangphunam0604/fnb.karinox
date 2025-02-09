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
    Schema::create('inventory_transactions', function (Blueprint $table) {
      $table->id();
      $table->timestamps();

      /**
       * import – Nhập kho (khi mua hàng hoặc bổ sung hàng tồn kho)
       * export – Xuất kho (chuyển hàng giữa các chi nhánh hoặc loại bỏ hàng hỏng)
       * sale – Bán hàng (giảm tồn kho khi đơn hàng hoàn tất)
       * return – Trả hàng (khách trả lại hàng, tăng tồn kho)
       * transfer_out - Xuất kho để chuyển đến chi nhánh khác.
       * transfer_in - Nhập kho từ một chi nhánh khác.
       * stocktaking - Điều chỉnh tồn kho dựa trên kết quả kiểm kho.
       */
      $table->enum('transaction_type', ['import', 'export', 'sale', 'return', 'transfer_out', 'transfer_in', 'stocktaking']);
      $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // Chi nhánh thực hiện giao dịch
      $table->foreignId('destination_branch_id')->nullable()->constrained('branches')->nullOnDelete(); // Dành cho chuyển kho
      $table->foreignId('reference_id')->nullable(); // Liên kết đến orders, invoices, inventory_transactions...
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Người thực hiện giao dịch
      $table->text('note')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('inventory_transactions');
  }
};
