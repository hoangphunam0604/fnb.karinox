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
    Schema::create('product_attributes', function (Blueprint $table) {
      $table->foreignId('product_id')->constrained()->onDelete('cascade');
      $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
      $table->string('value');
      $table->primary(['product_id', 'attribute_id']); // Khóa chính là cặp product_id + attribute_id
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('product_attributes');
  }
};
