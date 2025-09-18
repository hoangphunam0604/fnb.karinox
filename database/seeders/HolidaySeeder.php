<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;

class HolidaySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $holidays = [
      [
        'name' => 'Tết dương',
        'calendar' => 'solar',
        'year' => null,
        'month' => 1,
        'day' => 1,
        'description' => 'Ngày đầu năm theo Dương lịch, nhiều nơi nghỉ lễ.',
        'is_recurring' => true,
      ],
      [
        'name' => 'Valentine',
        'calendar' => 'solar',
        'year' => null,
        'month' => 2,
        'day' => 14,
        'description' => 'Ngày Lễ tình nhân, thường dành cho các cặp đôi.',
        'is_recurring' => true,
      ],
      [
        'name' => 'Quốc tế phụ nữ',
        'calendar' => 'solar',
        'year' => null,
        'month' => 3,
        'day' => 8,
        'description' => 'Ngày tôn vinh phụ nữ trên toàn thế giới.',
        'is_recurring' => true,
      ],
      [
        'name' => 'Quốc khánh',
        'calendar' => 'solar',
        'year' => null,
        'month' => 9,
        'day' => 2,
        'description' => 'Kỷ niệm ngày Quốc khánh.',
        'is_recurring' => true,
      ],
      [
        'name' => 'Giáng sinh',
        'calendar' => 'solar',
        'year' => null,
        'month' => 12,
        'day' => 25,
        'description' => 'Lễ Giáng sinh, được tổ chức vào cuối năm.',
        'is_recurring' => true,
      ],
    ];

    foreach ($holidays as $data) {
      Holiday::updateOrCreate([
        'calendar' => $data['calendar'],
        'month' => $data['month'],
        'day' => $data['day'],
      ], $data);
    }
  }
}
