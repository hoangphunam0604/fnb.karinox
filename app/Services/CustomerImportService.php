<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Exception;

class CustomerImportService
{
  public function import($file)
  {
    try {
      $data = Excel::toArray([], $file);
      $rows = $data[0] ?? [];

      DB::beginTransaction();

      foreach ($rows as $index => $row) {
        if ($index === 0) continue; // Bỏ qua header

        $customerData = [
          'fullname' => $row[3] ?? null,
          'phone' => $row[5] ?? null,
          'address' => $row[6] ?? null,
          'company_name' => $row[9] ?? null,
          'tax_id' => $row[10] ?? null,
          'total_spent' => $row[18] ?? 0,
          'loyalty_points' => $row[15] ?? 0,
          'reward_points' => $row[16] ?? 0,
          'status' => $row[23] ?? 1,
        ];

        Customer::updateOrCreate([
          'phone' => $customerData['phone']
        ], $customerData);
      }

      DB::commit();
      return ['success' => true, 'message' => 'Import thành công'];
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Lỗi import khách hàng: ' . $e->getMessage());
      return ['success' => false, 'message' => 'Lỗi import dữ liệu'];
    }
  }
}
