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
    Schema::create('products', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->unsignedBigInteger('kiotviet_id')->unique()->nullable();
      $table->enum('status', ['active', 'inactive'])->default('active');
      $table->string('code')->unique();
      $table->string('name');
      $table->unsignedInteger('price')->default(0);
      $table->string('unit', 50)->nullable();
      $table->string('product_type')->nullable();
      $table->string('booking_type', 30)->default('none');
      $table->boolean('allows_sale')->default(false);
      $table->boolean('is_reward_point')->default(false);
      $table->boolean('is_topping')->default(false);
      $table->boolean('is_new')->default(false);
      $table->boolean('print_label')->default(false); // In tem (dán ly/giữ lại)
      $table->boolean('print_kitchen')->default(false); // In phiếu bếp
      $table->foreignId('menu_id')->nullable()->constrained()->onDelete('set null');
      $table->text('description')->nullable();
      $table->longText('arena_discount')->nullable();
      $table->string('thumbnail')->default('https://karinox.vn/medias/logo-karinox.png');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('products');
  }
};
