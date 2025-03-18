<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('vouchers', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique();
      $table->enum('discount_type', ['fixed', 'percentage']);
      $table->decimal('discount_value', 10, 2);
      $table->decimal('max_discount', 10, 2)->nullable();
      $table->decimal('min_order_value', 10, 2)->nullable();
      $table->dateTime('start_date')->nullable();
      $table->dateTime('end_date')->nullable();
      $table->unsignedInteger('applied_count')->default(0);
      $table->unsignedInteger('usage_limit')->nullable();
      $table->unsignedInteger('per_customer_limit')->nullable();
      $table->unsignedInteger('per_customer_daily_limit')->nullable(); // Giới hạn số lần dùng voucher trong ngày cho từng khách hàng
      $table->boolean('is_active')->default(true);
      $table->boolean('disable_holiday')->default(false);
      $table->json('applicable_membership_levels')->nullable(); // Hỗ trợ nhiều hạng thành viên
      $table->json('valid_days_of_week')->nullable();
      $table->json('valid_weeks_of_month')->nullable();
      $table->json('valid_months')->nullable();
      $table->json('valid_time_ranges')->nullable();
      $table->json('excluded_dates')->nullable();
      $table->boolean('warn_if_used')->default(false);
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('vouchers');
  }
};
