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
    Schema::create('table_and_rooms', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
      $table->string('name');
      $table->integer('capacity')->default(0);
      $table->text('notes')->nullable();
      $table->boolean('is_active')->default(true);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('table_and_rooms');
  }
};
