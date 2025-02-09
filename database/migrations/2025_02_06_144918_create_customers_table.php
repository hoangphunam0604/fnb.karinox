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
    Schema::create('customers', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->string('name'); // Tên khách hàng
      $table->string('email')->unique()->nullable(); // Email khách hàng, duy nhất, có thể null
      $table->string('phone')->unique(); // Số điện thoại khách hàng, duy nhất
      $table->string('address')->nullable(); // Địa chỉ khách hàng
      $table->date('dob')->nullable(); // Ngày sinh khách hàng
      $table->integer('points')->default(0); // Số điểm tích lũy
      $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Giới tính khách hàng
      $table->string('membership_level')->nullable(); // Hạng thành viên (Silver, Gold, Platinum)
      $table->timestamp('last_purchase_at')->nullable(); // Ngày mua hàng gần nhất
      $table->decimal('total_spent', 15, 2)->default(0.00); // Tổng tiền khách đã chi tiêu
      $table->string('referral_code')->nullable(); // Mã giới thiệu khách hàng
      $table->enum('status', ['active', 'inactive', 'banned'])->default('active'); // Trạng thái khách hàng
      $table->string('avatar')->nullable(); // Ảnh đại diện khách hàng
      $table->string('company_name')->nullable(); // Tên công ty (nếu là khách hàng doanh nghiệp)
      $table->string('tax_id')->nullable(); // Mã số thuế công ty
      $table->string('facebook_id')->nullable(); // ID Facebook khách hàng
      $table->string('zalo_id')->nullable(); // ID Zalo khách hàng
      $table->json('preferences')->nullable(); // Sở thích hoặc tùy chọn khách hàng
      $table->string('loyalty_card_number')->unique()->nullable(); // Mã thẻ khách hàng thân thiết
      $table->enum('signup_source', ['website', 'mobile_app', 'store'])->nullable(); // Nguồn đăng ký tài khoản
      $table->text('note')->nullable(); // Ghi chú nội bộ về khách hàng
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customers');
  }
};
