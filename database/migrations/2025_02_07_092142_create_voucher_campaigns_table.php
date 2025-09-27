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
    Schema::create('voucher_campaigns', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->string('name');
      $table->text('description')->nullable();
      $table->enum('campaign_type', ['event', 'promotion', 'loyalty', 'seasonal', 'birthday', 'grand_opening'])->default('promotion');
      $table->datetime('start_date')->nullable();
      $table->datetime('end_date')->nullable();
      $table->integer('target_quantity')->comment('Số voucher dự kiến tạo');
      $table->integer('generated_quantity')->default(0)->comment('Số voucher đã tạo');
      $table->integer('used_quantity')->default(0)->comment('Số voucher đã sử dụng');

      // Template cho voucher sẽ được tạo từ campaign này
      $table->json('voucher_template')->comment('Template configuration for vouchers');

      // Code generation settings
      $table->string('code_prefix', 20)->comment('Prefix for voucher codes');
      $table->string('code_format', 50)->default('{PREFIX}_{RANDOM_8}')->comment('Format pattern for codes');
      $table->integer('code_length')->default(8)->comment('Length of random part');

      $table->boolean('is_active')->default(true);
      $table->boolean('auto_generate')->default(false)->comment('Tự động tạo voucher khi campaign active');

      $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

      // Indexes for performance
      $table->index(['is_active', 'start_date', 'end_date']);
      $table->index('campaign_type');
      $table->index('created_by');
      $table->unique('code_prefix'); // Đảm bảo prefix unique
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('voucher_campaigns');
  }
};
