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
    Schema::create('categories', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null'); // Hỗ trợ danh mục cha - con           
      $table->string('name');
      $table->string('code_prefix', 10)->unique(); // Prefix cho mã sản phẩm (VD: CF, TEA, MILK)
      $table->text('description')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('categories');
  }
};
