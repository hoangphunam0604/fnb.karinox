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
    Schema::create('print_histories', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
      $table->enum('type', ['invoice', 'provisional', 'kitchen', 'label', 'receipt', 'report', 'other'])->comment('Type of print job');
      $table->enum('status', ['requested', 'printed', 'failed'])->default('requested'); // Timestamps
      $table->datetime('requested_at')->comment('When print was requested');
      $table->datetime('printed_at')->nullable()->comment('When frontend finished printing');
      $table->json('metadata')->nullable()->comment('Print data (order_id, template_id, data for rendering)');
      // Indexes
      $table->index(['branch_id', 'status']);
      $table->index(['requested_at']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('print_histories');
  }
};
