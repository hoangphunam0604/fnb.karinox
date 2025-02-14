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
    Schema::create('point_histories', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');

      // Xác định loại giao dịch: cộng điểm hoặc sử dụng điểm
      $table->enum('transaction_type', ['earn', 'redeem']);
      // Số điểm trước khi thay đổi
      $table->integer('previous_loyalty_points')->default(0);
      $table->integer('previous_reward_points')->default(0);


      // Số điểm tích lũy & thưởng thay đổi
      $table->integer('loyalty_points_changed')->default(0);
      $table->integer('reward_points_changed')->default(0);


      // Số điểm sau khi thay đổi
      $table->integer('loyalty_points_after')->default(0);
      $table->integer('reward_points_after')->default(0);

      // Nguồn điểm (hóa đơn, thưởng sự kiện, ...)
      $table->string('source_type')->nullable(); // VD: "invoice", "event"
      $table->unsignedBigInteger('source_id')->nullable();

      // Điểm sử dụng vào đâu (đổi ưu đãi, giảm giá, ...)
      $table->string('usage_type')->nullable(); // VD: "voucher", "discount"
      $table->unsignedBigInteger('usage_id')->nullable();
      $table->text('note')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('point_histories');
  }
};
