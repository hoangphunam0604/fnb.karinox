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
    Schema::create('print_templates', function (Blueprint $table) {
      $table->id();
      $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete(); // Chi nhánh
      $table->string('type'); // bill, label, kitchen, etc.
      $table->string('name'); // tên gợi nhớ
      $table->text('description')->nullable();
      $table->longText('content'); // nội dung HTML có thể dùng {{ }}
      $table->boolean('is_default')->default(false);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('print_templates');
  }
};
