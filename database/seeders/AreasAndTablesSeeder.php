<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreasAndTablesSeeder extends Seeder
{
  public function run()
  {
    // Vô hiệu hóa kiểm tra khóa ngoại
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    // Xóa dữ liệu cũ thay vì truncate
    DB::table('tables_and_rooms')->delete();
    DB::table('areas')->delete();
    DB::table('branches')->delete();

    // Kích hoạt lại kiểm tra khóa ngoại
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    // Tạo dữ liệu cho các chi nhánh với mã kết nối
    $branches = [
      [
        'name' => 'Gà Rán Pippy Kids',
        'address' => 'Lô TM27-1 Hoàng Diệu',
        'email' => 'pippykids@domain.com',
        'phone_number' => '0123456789',
        'connection_code' => 'PIPPYKIDS'
      ],
      [
        'name' => 'Karinox Coffee',
        'address' => 'Lô TM27-1 Hoàng Diệu',
        'email' => 'karinox@domain.com',
        'phone_number' => '0987654321',
        'connection_code' => 'KARINOX'
      ],
      [
        'name' => 'Khu Vui Chơi Pippy Kids',
        'address' => 'Lô TM27-1 Hoàng Diệu',
        'email' => 'playground@domain.com',
        'phone_number' => '0123987654',
        'connection_code' => 'PLAYGROUND'
      ],
    ];

    $branchIds = [];
    foreach ($branches as $branch) {
      $branchIds[] = DB::table('branches')->insertGetId($branch);
    }

    // Tạo dữ liệu cho các khu vực, mỗi khu vực gắn với một chi nhánh cụ thể
    $areas = [
      ['name' => $branches[0]['name'] . ' - Tầng 1', 'note' => 'Tiếp khách chính', 'branch_id' => $branchIds[0]],
      ['name' => $branches[0]['name'] . ' - Tầng 2', 'note' => 'Khu vực yên tĩnh, phù hợp làm việc', 'branch_id' => $branchIds[0]],
      ['name' => $branches[1]['name'] . ' - Sân thượng', 'note' => 'Không gian mở, thoáng mát', 'branch_id' => $branchIds[1]],
      ['name' => $branches[1]['name'] . ' - Ngoài trời', 'note' => 'Phù hợp cho các buổi tiệc ngoài trời', 'branch_id' => $branchIds[1]],
      ['name' => $branches[2]['name'] . ' - VIP', 'note' => 'Khu vực dành cho khách hàng VIP', 'branch_id' => $branchIds[2]],
    ];

    // Chèn dữ liệu vào bảng `areas` và lưu lại ID
    $areaIds = [];
    foreach ($areas as $area) {
      $areaIds[] = DB::table('areas')->insertGetId($area);
    }

    // Tạo 30 bàn/phòng cho mỗi khu vực
    $tablesAndRooms = [];
    foreach ($areaIds as $areaId) {
      for ($i = 1; $i <= 30; $i++) {
        $tablesAndRooms[] = [
          'name' => "Bàn $i",
          'area_id' => $areaId,
          'branch_id' => $areas[array_search($areaId, $areaIds)]['branch_id'],
          'capacity' => rand(2, 10),
          'status' => 'available',
        ];
      }
    }

    // Chèn dữ liệu vào bảng `tables_and_rooms`
    DB::table('tables_and_rooms')->insert($tablesAndRooms);

    $this->command->info('Branches, Areas, and Tables/Rooms seeded successfully!');
  }
}
