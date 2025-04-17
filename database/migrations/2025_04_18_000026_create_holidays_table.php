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
    Schema::create('holidays', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->string('name');
      $table->date('date'); // ngày dương lịch
      $table->boolean('is_lunar')->default(false); // true nếu là ngày âm lịch
      $table->text('description')->nullable();
      $table->boolean('is_recurring')->default(true); // true nếu lặp lại mỗi năm
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('holidays');
  }
};
