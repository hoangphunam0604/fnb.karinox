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
      $table->index('updated_at'); //Index để tối ưu truy vấn lấy danh sách khách hàng cần cập nhật điểm

      $table->foreignId('membership_level_id')->nullable()->constrained()->nullOnDelete(); // Xếp hạng thành viên
      $table->string('loyalty_card_number')->unique()->nullable(); // Mã thẻ khách hàng thân thiết
      $table->integer('loyalty_points')->default(0); // Điểm tích lũy
      $table->integer('reward_points')->default(0); // Điểm thưởng
      $table->integer('used_reward_points')->default(0); // Điểm thưởng đã sử dụng
      $table->decimal('total_spent', 15, 2)->default(0.00); // Tổng tiền khách đã chi tiêu
      $table->timestamp('last_purchase_at')->nullable(); // Ngày mua hàng gần nhất
      $table->date('last_birthday_bonus_date')->nullable(); // Ngày gần nhất nhận X2 điểm

      $table->enum('status', ['active', 'inactive', 'banned'])->default('active'); // Trạng thái khách hàng
      $table->string('fullname')->nullable(); // Tên khách hàng
      $table->string('email')->unique()->nullable(); // Email khách hàng, duy nhất, có thể null
      $table->string('phone')->unique(); // Số điện thoại khách hàng, duy nhất
      $table->string('address')->nullable(); // Địa chỉ khách hàng
      $table->date('birthday')->nullable(); // Ngày sinh khách hàng
      $table->enum('gender', ['male', 'female'])->nullable(); // Giới tính khách hàng

      $table->string('referral_code')->nullable(); // Mã giới thiệu khách hàng
      $table->string('avatar')->nullable(); // Ảnh đại diện khách hàng
      $table->string('company_name')->nullable(); // Tên công ty (nếu là khách hàng doanh nghiệp)
      $table->string('tax_id')->nullable(); // Mã số thuế công ty
      $table->string('facebook_id')->nullable(); // ID Facebook khách hàng
      $table->string('zalo_id')->nullable(); // ID Zalo khách hàng
      $table->string('signup_source')->nullable(); // Nguồn đăng ký tài khoản
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
