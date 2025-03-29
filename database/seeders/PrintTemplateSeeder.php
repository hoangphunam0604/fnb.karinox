<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrintTemplate;

class PrintTemplateSeeder extends Seeder
{
  public function run(): void
  {
    // Xoá các template cũ (tuỳ bạn muốn reset hay không)
    PrintTemplate::truncate();

    // Danh sách các loại template
    $types = ['invoice', 'label', 'kitchen'];

    foreach ($types as $type) {
      // Tạo mẫu mặc định cho từng loại
      PrintTemplate::factory()->create([
        'type' => $type,
        'name' => "Mẫu in mặc định cho {$type}",
        'description' => "Đây là mẫu in chính dùng cho loại {$type}",
        'is_default' => true,
        'content' => match ($type) {
          'invoice' => <<<HTML
                        <h3>{{ tenQuan }}</h3>
                        <p>Bàn: {{ ban }}</p>
                        <p>Ngày: {{ ngay }}</p>
                        <ul>
                          {{#each mon}}<li>{{ ten }} x{{ soLuong }}</li>{{/each}}
                        </ul>
                        <strong>Tổng: {{ tongTien }}</strong>
                    HTML,
          'label' => <<<HTML
                        <div style="text-align: center;">
                          <strong>{{ ten }}</strong><br/>
                          SL: {{ soLuong }} - Bàn {{ ban }}
                        </div>
                    HTML,
          'kitchen' => <<<HTML
                        <p><strong>BẾP IN</strong></p>
                        <p>Món: {{ ten }}</p>
                        <p>Số lượng: {{ soLuong }}</p>
                        <p>Bàn: {{ ban }}</p>
                    HTML,
        },
      ]);

      // Tạo thêm 1-2 mẫu phụ cho từng loại
      PrintTemplate::factory()->count(2)->create([
        'type' => $type,
        'is_default' => false,
        'is_active' => true,
      ]);
    }
  }
}
