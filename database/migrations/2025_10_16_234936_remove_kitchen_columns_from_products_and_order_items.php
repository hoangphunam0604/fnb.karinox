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
    // Xóa cột print_kitchen từ bảng products
    Schema::table('products', function (Blueprint $table) {
      $table->dropColumn('print_kitchen');
    });

    // Xóa các cột kitchen từ bảng order_items
    Schema::table('order_items', function (Blueprint $table) {
      $table->dropColumn(['print_kitchen', 'printed_kitchen', 'printed_kitchen_at']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Khôi phục cột print_kitchen cho bảng products
    Schema::table('products', function (Blueprint $table) {
      $table->boolean('print_kitchen')->default(false)->after('print_label');
    });

    // Khôi phục các cột kitchen cho bảng order_items
    Schema::table('order_items', function (Blueprint $table) {
      $table->boolean('print_kitchen')->default(false)->after('printed_label_at');
      $table->boolean('printed_kitchen')->default(false)->after('print_kitchen');
      $table->timestamp('printed_kitchen_at')->nullable()->after('printed_kitchen');
    });
  }
};
