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
      $table->text('description')->nullable();
      $table->enum('calendar', ['solar', 'lunar'])->default('solar');
      $table->smallInteger('year')->nullable();
      $table->tinyInteger('month')->nullable();
      $table->tinyInteger('day')->nullable();
      $table->boolean('is_recurring')->default(true); // true nếu lặp lại mỗi năm (solar recurring)
      $table->index(['is_recurring', 'month', 'day']); // indexes to speed up common queries
      $table->index(['calendar']);
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
