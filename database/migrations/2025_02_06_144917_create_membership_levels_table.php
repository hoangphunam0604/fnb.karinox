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
    Schema::create('membership_levels', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->integer('rank')->unique(); // Thứ tự xếp hạng của cấp độ thành viên;
      $table->string('name')->unique(); //Tên hạng khách hàng (VD: Silver, Gold, Platinum).
      $table->decimal('min_spent', 10, 2); //Tổng tiền chi tiêu tối thiểu để đạt hạng này.
      $table->decimal('max_spent', 10, 2)->nullable(); //Tổng tiền chi tiêu tối đa cho hạng này (nếu có).
      $table->decimal('reward_multiplier', 5, 2)->nullable(); //Hệ số nhân điểm thưởng.
      $table->text('upgrade_reward_content')->nullable(); //Nội dung thưởng thăng hạng này
      $table->text('birthday_gift')->nullable(); //Nội dung quà sinh nhật hạng này
      $table->text('shopping_entertainment_offers')->nullable(); //Ưu đãi mua sắm & vui chơi
      $table->text('party_booking_offer')->nullable(); //Ưu đãi đặt tiệc    
      $table->text('new_product_offers')->nullable(); //Ưu đãi sản phẩm mới
      $table->text('end_of_year_gifts')->nullable(); //Quà tặng cuối năm

    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('membership_levels');
  }
};
