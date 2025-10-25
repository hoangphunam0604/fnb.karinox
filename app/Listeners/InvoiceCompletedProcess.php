<?php

namespace App\Listeners;

use App\Events\InvoiceCompleted;
use App\Services\CustomerService;
use App\Services\PointService;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceCompletedProcess
{
  protected PointService $pointService;
  protected CustomerService $customerService;
  protected SystemSettingService $systemSettingService;

  public function __construct(PointService $pointService, CustomerService $customerService, SystemSettingService $systemSettingService)
  {
    $this->pointService = $pointService;
    $this->customerService = $customerService;
    $this->systemSettingService = $systemSettingService;
  }


  public function handle(InvoiceCompleted $event)
  {
    $invoice = $event->invoice;
    $customer = $invoice->customer;

    if (!$customer) {
      return;
    }

    try {
      // Chuyển điểm đã sử dụng từ đơn đặt hàng sang hoá đơn
      $this->pointService->transferUsedPointsToInvoice($invoice);

      // Cập nhật tổng số tiền đã chi tiêu
      $this->customerService->updateTotalSpent($customer, $invoice->total_amount);
      //Cộng điểm từ hoá đơn
      $this->pointService->earnPointsOnTransactionCompletion($invoice);
      // Cập nhật cấp độ thành viên
      $this->customerService->updateMembershipLevel($customer);
    } catch (\Exception $e) {
      Log::error("Lỗi khi xử lý hóa đơn hoàn tất: " . $e->getMessage());
    }
  }
}
