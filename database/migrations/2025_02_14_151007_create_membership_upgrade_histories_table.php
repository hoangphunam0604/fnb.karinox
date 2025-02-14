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
    Schema::create('membership_upgrade_histories', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
      $table->foreignId('old_membership_level_id')->nullable()->constrained('membership_levels')->onDelete('set null');
      $table->foreignId('new_membership_level_id')->constrained('membership_levels')->onDelete('cascade');
      $table->dateTime('upgraded_at')->useCurrent(); // Ngày thăng hạng
      $table->text('upgrade_reward_content')->nullable();
      $table->boolean('reward_claimed')->default(false); // Đánh dấu đã nhận quà chưa
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('membership_upgrade_histories');
  }
};
