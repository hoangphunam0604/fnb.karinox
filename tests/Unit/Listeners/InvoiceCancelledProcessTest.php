<?php

namespace Tests\Unit\Listeners;

use App\Events\InvoiceCancelled;
use App\Listeners\InvoiceCancelledProcess;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerService;
use App\Services\PointService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class InvoiceCancelledProcessTest extends TestCase
{
  use RefreshDatabase;

  protected $pointService;
  protected $customerService;
  protected $voucherService;
  protected $listener;

  protected function setUp(): void
  {
    parent::setUp();

    /** @var PointService $pointService */
    $this->pointService = Mockery::mock(PointService::class);
    /** @var CustomerService $customerService */
    $this->customerService = Mockery::mock(CustomerService::class);
    /** @var VoucherService $voucherService */
    $this->voucherService = Mockery::mock(VoucherService::class);

    $this->listener = new InvoiceCancelledProcess(
      $this->pointService,
      $this->customerService,
      $this->voucherService
    );
  }

  public function test_handle_calls_services_correctly()
  {
    $customer = Mockery::mock(Customer::class);
    $invoice = Mockery::mock(Invoice::class);
    $invoice->shouldReceive('getAttribute')->with('customer')->andReturn($customer);
    $invoice->shouldReceive('getAttribute')->with('total_amount')->andReturn(200);
    $invoice->shouldReceive('getAttribute')->with('id')->andReturn(1);

    /** @var Invoice $invoice */
    $event = new InvoiceCancelled($invoice);

    $this->pointService->shouldReceive('restorePointsOnInvoiceCancellation')->once()->with($invoice);
    $this->voucherService->shouldReceive('refundVoucherFromInvoice')->once()->with($invoice);
    $this->customerService->shouldReceive('updateTotalSpent')->once()->with($customer, -200);
    $this->customerService->shouldReceive('downgradeMembershipLevel')->once()->with($customer);

    $this->listener->handle($event);
  }

  public function test_handle_uses_database_transaction()
  {
    $customer = Mockery::mock(Customer::class);
    $invoice = Mockery::mock(Invoice::class);
    $invoice->shouldReceive('getAttribute')->with('customer')->andReturn($customer);
    $invoice->shouldReceive('getAttribute')->with('total_amount')->andReturn(200);
    $invoice->shouldReceive('getAttribute')->with('id')->andReturn(1);

    /** @var Invoice $invoice */
    $event = new InvoiceCancelled($invoice);

    DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) use ($event) {
      $callback();
    });

    $this->listener->handle($event);
  }

  public function test_handle_catches_exception_and_logs_error()
  {
    $customer = Mockery::mock(Customer::class);
    $invoice = Mockery::mock(Invoice::class);
    $invoice->shouldReceive('getAttribute')->with('customer')->andReturn($customer);
    $invoice->shouldReceive('getAttribute')->with('total_amount')->andReturn(200);
    $invoice->shouldReceive('getAttribute')->with('id')->andReturn(1);

    /** @var Invoice $invoice */
    $event = new InvoiceCancelled($invoice);

    DB::shouldReceive('transaction')->once()->andThrow(new \Exception('Fake error'));

    Log::shouldReceive('error')->once()->with("Lỗi khi xử lý hủy hóa đơn ID 1: Fake error");

    $this->listener->handle($event);
  }

  public function test_handle_logs_success_message()
  {
    $customer = Mockery::mock(Customer::class);
    $invoice = Mockery::mock(Invoice::class);
    $invoice->shouldReceive('getAttribute')->with('customer')->andReturn($customer);
    $invoice->shouldReceive('getAttribute')->with('total_amount')->andReturn(200);
    $invoice->shouldReceive('getAttribute')->with('id')->andReturn(1);

    $this->pointService->shouldReceive('restorePointsOnInvoiceCancellation')->once()->with($invoice);
    $this->voucherService->shouldReceive('refundVoucherFromInvoice')->once()->with($invoice);
    $this->customerService->shouldReceive('updateTotalSpent')->once()->with($customer, -200);
    $this->customerService->shouldReceive('downgradeMembershipLevel')->once()->with($customer);

    /** @var Invoice $invoice */
    $event = new InvoiceCancelled($invoice);

    // Đảm bảo không có lỗi nào được ghi vào log
    Log::shouldReceive('error')->never();
    // Mock log success
    Log::shouldReceive('info')->once()->with("Hóa đơn ID 1 bị hủy. Hoàn tất cập nhật điểm, voucher và cấp độ thành viên.");

    $this->listener->handle($event);
  }
}
