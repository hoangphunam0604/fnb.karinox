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
    Schema::create('kitchen_tickets', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('order_id')->constrained()->cascadeOnDelete();
      $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
      $table->foreignId('table_id')->nullable()->constrained('tables_and_rooms')->nullOnDelete();
      $table->enum('status', ['waiting', 'processing', 'completed', 'canceled'])->default('waiting');
      $table->tinyInteger('priority')->default(0);
      $table->text('note')->nullable();
      $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
      $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
      $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('kitchen_tickets');
  }
};
