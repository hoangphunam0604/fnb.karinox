<?php

namespace Tests\Unit\Listeners;

use App\Events\InvoiceCompleted;
use App\Listeners\InvoicePointsProcess;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerService;
use App\Services\PointService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InvoicePointsProcessTest extends TestCase
{
  use RefreshDatabase;

  protected $pointService;
  protected $customerService;
  protected $systemSettingService;
  protected $listener;

  protected function setUp(): void
  {
    parent::setUp();

    $this->pointService = Mockery::mock(PointService::class);
    $this->customerService = Mockery::mock(CustomerService::class);
    $this->systemSettingService = Mockery::mock(SystemSettingService::class);

    $this->listener = new InvoicePointsProcess(
      $this->pointService,
      $this->customerService,
      $this->systemSettingService
    );
  }

  public function test_handle_does_nothing_if_customer_is_null()
  {
    $invoice = Invoice::factory()->make(['customer_id' => null]);
    $event = new InvoiceCompleted($invoice);

    $this->systemSettingService->shouldReceive('getPointConversionRate')->andReturn(100);
    $this->pointService->shouldNotReceive('addPoints');

    $this->listener->handle($event);
  }

  public function test_handle_does_nothing_if_total_amount_is_zero()
  {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->make(['customer_id' => $customer->id, 'total_amount' => 0]);
    $event = new InvoiceCompleted($invoice);

    $this->systemSettingService->shouldReceive('getPointConversionRate')->andReturn(100);
    $this->pointService->shouldNotReceive('addPoints');

    $this->listener->handle($event);
  }

  public function test_handle_correctly_calculates_loyalty_points()
  {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->make(['customer_id' => $customer->id, 'total_amount' => 500]);
    $event = new InvoiceCompleted($invoice);

    $this->systemSettingService->shouldReceive('getPointConversionRate')->andReturn(100);

    $this->pointService->shouldReceive('addPoints')->once()->with(
      $customer,
      5,
      5,
      'invoice',
      $invoice->id,
      "Cộng điểm từ đơn hàng: {$invoice->code}"
    );

    $this->listener->handle($event);
  }

  public function test_handle_updates_customer_total_spent()
  {
    $customer = Customer::factory()->create(['total_spent' => 1000]);
    $invoice = Invoice::factory()->make(['customer_id' => $customer->id, 'total_amount' => 500]);
    $event = new InvoiceCompleted($invoice);

    $this->systemSettingService->shouldReceive('getPointConversionRate')->andReturn(100);
    $this->pointService->shouldReceive('addPoints')->once()->with(
      $customer,
      Mockery::any(),
      Mockery::any(),
      'invoice',
      $invoice->id
    );

    $this->listener->handle($event);

    $this->assertEquals(1500, $customer->fresh()->total_spent);
  }
}
