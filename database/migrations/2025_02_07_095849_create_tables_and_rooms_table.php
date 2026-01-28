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
    Schema::create('tables_and_rooms', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('area_id')->constrained()->deleteOnDelete();
      $table->foreignId('branch_id')->constrained()->deleteOnDelete();
      $table->string('name');
      $table->integer('capacity')->default(10);
      $table->integer('slots')->default(0);
      $table->integer('order')->default(0);
      $table->text('note')->nullable();

      $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance'])->default('available'); // Trạng thái phòng/bàn
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tables_and_rooms');
  }
};
