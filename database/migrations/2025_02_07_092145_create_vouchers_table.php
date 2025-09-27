<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('vouchers', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->foreignId('campaign_id')->nullable()->constrained('voucher_campaigns')->onDelete('set null');
      $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
      $table->string('code')->unique();
      $table->string('description')->nullable();
      $table->enum('voucher_type', ['membership', 'standard'])->default('standard');
      $table->enum('discount_type', ['fixed', 'percentage']);
      $table->unsignedInteger('discount_value');
      $table->unsignedInteger('max_discount')->nullable();
      $table->unsignedInteger('min_order_value')->nullable();
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
      $table->index('campaign_id');
    });
  }

  public function down()
  {
    Schema::dropIfExists('vouchers');
  }
};
