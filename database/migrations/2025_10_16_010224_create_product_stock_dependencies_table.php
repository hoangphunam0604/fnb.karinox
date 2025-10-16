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
    Schema::create('product_stock_dependencies', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('source_product_id')->comment('Sản phẩm gốc (combo/processed/service)');
      $table->unsignedBigInteger('target_product_id')->comment('Sản phẩm cần trừ kho (goods/ingredient)');
      $table->unsignedInteger('quantity')->comment('Số lượng cần trừ cho 1 đơn vị source (đơn vị nhỏ nhất: gram, ml, cái...)');
      $table->timestamps();

      // Indexes
      $table->index('source_product_id');
      $table->index('target_product_id');
      $table->unique(['source_product_id', 'target_product_id'], 'psd_source_target_unique');

      // Foreign keys
      $table->foreign('source_product_id')->references('id')->on('products')->onDelete('cascade');
      $table->foreign('target_product_id')->references('id')->on('products')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('product_stock_dependencies');
  }
};
