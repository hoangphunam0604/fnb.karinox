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
    Schema::create('voucher_branches', function (Blueprint $table) {
      $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
      $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('voucher_branches');
  }
};
