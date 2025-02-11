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
      $table->enum('type', ['fixed', 'percentage']);
      $table->decimal('discount_amount', 10, 2);
      $table->decimal('max_discount', 10, 2)->nullable();
      $table->decimal('min_order_value', 10, 2)->nullable();
      $table->dateTime('start_date');
      $table->dateTime('end_date');
      $table->integer('usage_limit')->nullable();
      $table->integer('per_customer_limit')->nullable();
      $table->boolean('is_active')->default(true);
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
