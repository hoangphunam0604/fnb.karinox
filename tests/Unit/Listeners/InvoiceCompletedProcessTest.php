<?php

namespace Tests\Unit\Listeners;

use App\Events\InvoiceCompleted;
use App\Listeners\InvoiceCompletedProcess;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerService;
use App\Services\PointService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class InvoiceCompletedProcessTest extends TestCase
{
  use RefreshDatabase;

  protected $pointService;
  protected $customerService;
  protected $systemSettingService;
  protected $listener;

  protected function setUp(): void
  {
    parent::setUp();

    /** @var PointService $pointService */
    $this->pointService = Mockery::mock(PointService::class);
    /** @var CustomerService $customerService */
    $this->customerService = Mockery::mock(CustomerService::class);
    /** @var SystemSettingService $systemSettingService */
    $this->systemSettingService = Mockery::mock(SystemSettingService::class);

    $this->listener = new InvoiceCompletedProcess(
      $this->pointService,
      $this->customerService,
      $this->systemSettingService
    );
  }

  public function test_handle_calls_services_correctly()
  {
    // Mock dữ liệu
    /** @var Customer $customer */
    $customer = Mockery::mock(Customer::class);
    /** @var Invoice $invoice */
    $invoice = Mockery::mock(Invoice::class);
    $invoice->shouldReceive('getAttribute')->with('customer')->andReturn($customer);
    $invoice->shouldReceive('getAttribute')->with('total_amount')->andReturn(200);

    // Giả lập sự kiện
    $event = new InvoiceCompleted($invoice);

    // Định nghĩa hành vi mong đợi của các service
    $this->pointService->shouldReceive('addPointsOnInvoiceCompletion')->once()->with($invoice);
    $this->pointService->shouldReceive('transferUsedPointsToInvoice')->once()->with($invoice);
    $this->customerService->shouldReceive('updateTotalSpent')->once()->with($customer, 200);
    $this->customerService->shouldReceive('updateMembershipLevel')->once()->with($customer);

    // Chạy handle()
    $this->listener->handle($event);
  }

  public function test_handle_uses_database_transaction()
  {
    $customer = Mockery::mock(Customer::class);

    /** @var Invoice $invoice */
    $invoice = Mockery::mock(Invoice::class);
    $invoice->shouldReceive('getAttribute')->with('customer')->andReturn($customer);
    $invoice->shouldReceive('getAttribute')->with('total_amount')->andReturn(200);

    $event = new InvoiceCompleted($invoice);

    DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) use ($event) {
      $callback(); // Gọi callback để kiểm tra logic bên trong transaction
    });

    $this->listener->handle($event);
  }

  public function test_handle_catches_exception_and_logs_error()
  {
    $customer = Mockery::mock(Customer::class);

    /** @var Invoice $invoice */
    $invoice = Mockery::mock(Invoice::class);
    $invoice->shouldReceive('getAttribute')->with('customer')->andReturn($customer);
    $invoice->shouldReceive('getAttribute')->with('total_amount')->andReturn(200);
    $invoice->shouldReceive('getAttribute')->with('id')->andReturn(1);

    $event = new InvoiceCompleted($invoice);

    // Giả lập một lỗi xảy ra trong `DB::transaction()`
    DB::shouldReceive('transaction')->once()->andThrow(new \Exception('Fake error'));

    Log::shouldReceive('error')->once()->with("Lỗi khi xử lý hóa đơn hoàn tất: Fake error");

    $this->listener->handle($event);
  }
}
