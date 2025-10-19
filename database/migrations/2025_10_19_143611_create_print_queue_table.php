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
    Schema::create('print_queue', function (Blueprint $table) {
      $table->id();
      $table->foreignId('branch_id')->constrained('branches');
      $table->enum('type', ['invoice', 'provisional', 'label', 'kitchen']);
      $table->longText('content'); // HTML content to print
      $table->json('metadata')->nullable(); // order_id, order_item_id, device_id, etc.
      $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
      $table->string('device_id')->nullable(); // Target printer device
      $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
      $table->timestamp('processed_at')->nullable();
      $table->text('error_message')->nullable();
      $table->integer('retry_count')->default(0);
      $table->timestamps();

      // Indexes for performance
      $table->index(['branch_id', 'status', 'priority', 'created_at']);
      $table->index(['device_id', 'status']);
      $table->index('type');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('print_queue');
  }
};
