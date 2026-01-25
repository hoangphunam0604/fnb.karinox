<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MembershipLevel;
use App\Client\KiotViet;
use App\Enums\Gender;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Exception;

class CustomerImportService
{
  public function __construct(
    protected KiotViet $kiot_viet
  ) {}
  /* 
  public function import($filePath)
  {
    if (!file_exists($filePath)) {
      return ['success' => false, 'message' => 'Lỗi: File không tồn tại tại ' . $filePath];
    }
    try {
      $data = Excel::toArray([], $filePath)[0];
      $rows = $data ?? [];
      $statusMap = [
        '1' => 'active',
        '0' => 'inactive',
        '-1' => 'banned'
      ];

      $cleanValue = function ($value) {
        if (is_null($value) || trim($value) === '' || $value === '?') {
          return null;
        }
        return mb_convert_encoding(trim($value), 'UTF-8', 'auto');
      };

      $convertBirthday = function ($value) {
        if (is_null($value) || trim($value) === '') {
          return null;
        }
        return is_numeric($value)
          ? date('Y-m-d', ($value - 25569) * 86400)
          : date('Y-m-d', strtotime(str_replace('/', '-', $value)));
      };
      DB::beginTransaction();

      foreach ($rows as $index => $row) {
        if ($index === 0) continue; // Bỏ qua header


        $customerData = [
          'fullname' => $row[3],
          'phone' => $row[4],
          'address' => $row[5],
          'birthday' => $convertBirthday($row[10] ?? null),
          'loyalty_points' => is_numeric($row[18] ?? null) ? $row[18] : 0,
          'reward_points' => is_numeric($row[18] ?? null) ? $row[18] : 0,
          'total_spent' => is_numeric($row[21] ?? null) ? $row[21] : 0, // Cột V (22 theo chỉ mục 1-based, 21 zero-based)
          'status' => $statusMap[$row[23] ?? '1'] ?? 'inactive',
        ];
        Customer::updateOrCreate([
          'phone' => $customerData['phone']
        ], $customerData);
      }

      DB::commit();
      $this->updateMembershipLevels();
      return ['success' => true, 'message' => 'Import thành công'];
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Lỗi import khách hàng: ' . $e->getMessage());
      return ['success' => false, 'message' => 'Lỗi import dữ liệu'];
    }
  } */

  public function importFromKiotViet($pageSize = 100)
  {
    $currentItem = 0;
    $totalItem = 1;
    while ($currentItem < $totalItem) {
      $response = $this->kiot_viet->getCustomers($pageSize, $currentItem);
      $items = $response['data'];
      $totalItem = $response['total'];
      $currentItem += count($items);

      foreach ($items as $item) {
        if (!isset($item['contactNumber']) || empty($item['contactNumber'])) {
          Log::warning('Bỏ qua khách hàng không có số điện thoại: ' . json_encode($item));
          continue; // Bỏ qua nếu không có số điện thoại
        }
        $customerData = [
          'loyalty_card_number' => $item['code'] ?? null,
          'fullname' => $item['name'],
          'phone' => $item['contactNumber'],
          'email' => !empty($item['email']) ? $item['email'] : null,
          'birthday' => Carbon::parse($item['birthDate'] ?? null)->format('Y-m-d'),
          'gender' => ($item['gender'] ?? true) ? Gender::FEMALE : Gender::MALE,
          'address' => $item['address'] ?? null,
          'loyalty_points' => is_numeric($item['totalPoint'] ?? null) ? $item['totalPoint'] : 0,
          'reward_points' => is_numeric($item['rewardPoint'] ?? null) ? $item['rewardPoint'] : 0,
          'total_spent' => is_numeric($item['totalInvoiced'] ?? null) ? $item['totalInvoiced'] : 0
        ];
        Customer::updateOrCreate([
          'phone' => $customerData['phone']
        ], $customerData);
      }
    }

    $this->updateMembershipLevels();
  }

  private function updateMembershipLevels()
  {
    // Lấy danh sách hạng thành viên, sắp xếp theo rank tăng dần
    $membershipLevels = MembershipLevel::orderBy('rank')->get();

    foreach ($membershipLevels as $level) {
      $query = Customer::whereNull('membership_level_id')
        ->where('loyalty_points', '>=', $level->min_spent);

      // Chỉ áp dụng giới hạn max_spent nếu nó không null
      if (!is_null($level->max_spent)) {
        $query->where('loyalty_points', '<=', $level->max_spent);
      }
      $query->update(['membership_level_id' => $level->id]);
    }
  }
}
