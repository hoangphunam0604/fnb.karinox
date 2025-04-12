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
    Schema::create('kitchen_ticket_items', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('kitchen_ticket_id')->constrained()->cascadeOnDelete();
      $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
      $table->foreignId('product_id')->constrained()->cascadeOnDelete();
      $table->string('product_name');
      $table->string('toppings_text');
      $table->integer('quantity')->default(1);
      $table->enum('status', ['waiting', 'processing', 'ready', 'completed', 'canceled'])->default('waiting');
      $table->text('note')->nullable(); // Lưu combo & topping ở đây

    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('kitchen_ticket_items');
  }
};
