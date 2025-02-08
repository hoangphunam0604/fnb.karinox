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
    Schema::create('invoice_details', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('invoice_id')->constrained()->nullOnDelete();
      $table->foreignId('product_id')->constrained()->nullOnDelete();
      $table->integer('quantity');
      $table->decimal('price', 15, 2);
      $table->decimal('total_price', 15, 2);
      $table->text('note')->nullable();
      $table->foreignId('parent_id')->nullable()->constrained('invoice_details')->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('invoice_details');
  }
};
