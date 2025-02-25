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
