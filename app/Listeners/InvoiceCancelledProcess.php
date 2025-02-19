<?php

namespace App\Listeners;

use App\Events\InvoiceCancelled;
use App\Services\CustomerService;
use App\Services\PointService;
use App\Services\VoucherService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceCancelledProcess
{
  protected PointService $pointService;
  protected CustomerService $customerService;
  protected VoucherService $voucherService;

  public function __construct(PointService $pointService, CustomerService $customerService, VoucherService $voucherService)
  {
    $this->pointService = $pointService;
    $this->customerService = $customerService;
    $this->voucherService = $voucherService;
  }


  public function handle(InvoiceCancelled $event)
  {
    $invoice = $event->invoice;
    $customer = $invoice->customer;

    if (!$customer) {
      return;
    }
    try {
      DB::transaction(function () use ($invoice, $customer) {
        //Khôi phục điểm đã đuọc cộng từ hoá đơn
        $this->pointService->restoreTransactionRewardPoints($invoice);

        //Khôi phục voucher đã sử dụng
        $this->voucherService->refundVoucherFromInvoice($invoice);

        // Cập nhật tổng số tiền đã chi tiêu, trừ đi số tiền hoá đơn đã huỷ
        $this->customerService->updateTotalSpent($customer, -$invoice->total_amount);

        // Giảm cấp độ thành viên
        $this->customerService->downgradeMembershipLevel($customer);
      });
      Log::info("Hóa đơn ID {$invoice->id} bị hủy. Hoàn tất cập nhật điểm, voucher và cấp độ thành viên.");
    } catch (\Exception $e) {
      echo "Lỗi" . $e->getMessage();
      Log::error("Lỗi khi xử lý hủy hóa đơn ID {$invoice->id}: " . $e->getMessage());
    }
  }
}
