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
    Schema::create('branches', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->string('name');
      $table->string('phone_number')->nullable();
      $table->string('email')->nullable();
      $table->string('address')->nullable();
      $table->unsignedSmallInteger('sort_order')->default(0);
      $table->enum('status', ['active', 'inactive'])->default('active');
      $table->string('print_connection_code', 12)->unique()->nullable()->comment('Mã kết nối cho ứng dụng quản lý máy in');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('branches');
  }
};
