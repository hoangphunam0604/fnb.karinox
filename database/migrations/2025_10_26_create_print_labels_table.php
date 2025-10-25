<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('print_labels', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('invoice_item_id')->constrained()->cascadeOnDelete();
      $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
      $table->string('product_code')->comment('Mã sản phẩm');
      $table->text('toppings_text')->nullable()->comment('Formatted toppings text');
      $table->integer('print_count')->default(0)->comment('Số lần in');
      $table->datetime('last_printed_at')->nullable()->comment('Lần in cuối');
      $table->text('note')->nullable();

      $table->index(['branch_id']);
      $table->index(['invoice_item_id']);
      $table->index(['product_code']);
      $table->index(['last_printed_at']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('print_labels');
  }
};
