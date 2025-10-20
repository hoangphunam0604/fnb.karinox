<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PrintService;
use App\Models\Order;
use App\Models\PrintHistory;
use App\Events\PrintRequested;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PrintServiceTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();
    Event::fake();

    // Disable foreign key checks for testing
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    $this->createTestBranch();
  }

  protected function tearDown(): void
  {
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    parent::tearDown();
  }

  private function createTestBranch(): void
  {
    DB::table('branches')->insert([
      'id' => 1,
      'name' => 'Test Branch',
      'address' => 'Test Address',
      'phone_number' => '0123456789',
      'email' => 'test@test.com',
      'status' => 'active',
      'sort_order' => 1,
      'created_at' => now(),
      'updated_at' => now()
    ]);
  }

  /**
   * Test printViaSocket method
   */
  public function test_print_via_socket_creates_history_and_dispatches_event(): void
  {

    $printData = [
      'type' => 'invoice',
      'content' => 'Test Invoice #12345',
      'metadata' => [
        'order_id' => 1,
        'order_code' => 'TEST001'
      ]
    ];

    $printId = PrintService::printViaSocket($printData, 1, 'pos-station');

    // Assert print ID is generated
    $this->assertIsString($printId);
    $this->assertStringStartsWith('print_', $printId);

    // Assert PrintRequested event was dispatched
    Event::assertDispatched(PrintRequested::class, function ($event) use ($printData) {
      return $event->printData['type'] === $printData['type'] &&
        $event->printData['content'] === $printData['content'] &&
        $event->branchId === 1 &&
        $event->deviceId === 'pos-station';
    });

    // Assert print history was created
    $this->assertDatabaseHas('print_histories', [
      'print_id' => $printId,
      'type' => 'invoice',
      'content' => 'Test Invoice #12345',
      'status' => 'requested',
      'device_id' => 'pos-station',
      'branch_id' => 1
    ]);
  }

  /**
   * Test printInvoiceViaSocket method
   */
  public function test_print_invoice_via_socket(): void
  {
    // Create order manually without factory
    $order = new Order([
      'id' => 1,
      'code' => 'TEST001',
      'total_price' => 50000,
      'payment_method' => 'cash',
      'branch_id' => 1
    ]);

    $printId = PrintService::printInvoiceViaSocket($order, 'pos-printer');

    // Assert correct event was dispatched
    Event::assertDispatched(PrintRequested::class, function ($event) use ($order) {
      return $event->printData['type'] === 'invoice' &&
        strpos($event->printData['content'], $order->code) !== false &&
        $event->printData['metadata']['order_id'] === $order->id &&
        $event->deviceId === 'pos-printer';
    });
  }

  /**
   * Test confirmPrinted method
   */
  public function test_confirm_printed_updates_history(): void
  {

    // Create a print history record
    $printHistory = PrintHistory::create([
      'print_id' => 'test_print_123',
      'type' => 'invoice',
      'content' => 'Test content',
      'status' => 'requested',
      'branch_id' => 1,
      'device_id' => 'test-device',
      'metadata' => ['test' => true],
      'priority' => 'normal',
      'requested_at' => now()
    ]);

    $result = PrintService::confirmPrinted('test_print_123');

    $this->assertTrue($result);

    // Assert status was updated
    $printHistory->refresh();
    $this->assertEquals('printed', $printHistory->status);
    $this->assertNotNull($printHistory->printed_at);
  }

  /**
   * Test reportPrintError method
   */
  public function test_report_print_error_updates_history(): void
  {

    // Create a print history record
    $printHistory = PrintHistory::create([
      'print_id' => 'test_print_456',
      'type' => 'kitchen',
      'content' => 'Test kitchen ticket',
      'status' => 'requested',
      'branch_id' => 1,
      'device_id' => 'kitchen-printer',
      'metadata' => ['test' => true],
      'priority' => 'normal',
      'requested_at' => now()
    ]);

    $errorMessage = 'Printer offline';
    $result = PrintService::reportPrintError('test_print_456', $errorMessage);

    $this->assertTrue($result);

    // Assert status and error were updated
    $printHistory->refresh();
    $this->assertEquals('failed', $printHistory->status);
    $this->assertEquals($errorMessage, $printHistory->error_message);
  }

  /**
   * Test confirmPrinted with nonexistent print ID
   */
  public function test_confirm_printed_nonexistent_print_id_returns_false(): void
  {
    $result = PrintService::confirmPrinted('nonexistent_print_id');

    $this->assertFalse($result);
  }

  /**
   * Test reportPrintError with nonexistent print ID
   */
  public function test_report_print_error_nonexistent_print_id_returns_false(): void
  {
    $result = PrintService::reportPrintError('nonexistent_print_id', 'Some error');

    $this->assertFalse($result);
  }
}
