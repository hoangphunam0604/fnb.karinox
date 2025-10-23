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
      $table->string('print_id')->unique()->comment('Unique identifier for print job');
      $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
      $table->enum('type', ['invoice', 'kitchen', 'label', 'receipt', 'report', 'other'])->comment('Type of print job');
      $table->json('metadata')->nullable()->comment('Print data (order_id, template_id, data for rendering)');
      $table->enum('status', ['requested', 'printed', 'confirmed', 'failed'])->default('requested');

      // Timestamps
      $table->datetime('requested_at')->comment('When print was requested');
      $table->datetime('printed_at')->nullable()->comment('When frontend finished printing');
      $table->datetime('confirmed_at')->nullable()->comment('When print was confirmed complete');

      $table->timestamps();

      // Indexes
      $table->index(['branch_id', 'status']);
      $table->index(['requested_at']);
      $table->index(['print_id']);
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
