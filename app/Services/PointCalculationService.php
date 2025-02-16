<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PointHistory;
use App\Services\Interfaces\PointCalculationServiceInterface;
use Illuminate\Pagination\Paginator;

class PointCalculationService implements PointCalculationServiceInterface
{

  /**
   * Lấy lịch sử điểm của khách hàng
   */
  public function getCustomerPointHistory(Customer $customer, int $limit = 10): Paginator
  {
    return PointHistory::where('customer_id', $customer->id)
      ->orderBy('created_at', 'desc')
      ->limit($limit)
      ->get();
  }
}
