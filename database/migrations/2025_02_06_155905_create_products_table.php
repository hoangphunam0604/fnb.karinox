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
    Schema::create('products', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->unsignedTinyInteger('product_group')->default(1);
      $table->enum('product_type', ['goods', 'processed', 'service', 'combo'])->default('goods');
      $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
      $table->string('code');
      $table->string('barcode')->nullable();
      $table->string('name');
      $table->text('description')->nullable();
      $table->decimal('cost_price', 10, 2)->default(0);
      $table->decimal('price', 10, 2)->nullable();
      $table->string('unit', 50)->nullable();
      $table->boolean('allows_sale')->default(true);
      $table->boolean('is_reward_point')->default(true);
      $table->boolean('is_topping')->default(false);
      $table->json('images')->nullable();
      $table->enum('status', ['active', 'inactive'])->default('active');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('products');
  }
};
