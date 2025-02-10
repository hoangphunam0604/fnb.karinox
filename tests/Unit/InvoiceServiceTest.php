<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Branch;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $invoiceService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->invoiceService = new InvoiceService();
  }

  /**
   * Test tạo hóa đơn mới
   */
  public function test_create_invoice()
  {
    $customer = Customer::factory()->create();
    $branch = Branch::factory()->create();
    $order = Order::factory()->create();

    $invoiceData = [
      'customer_id' => $customer->id,
      'branch_id' => $branch->id,
      'order_id' => $order->id,
      'total_price' => 150000,
    ];

    $invoice = $this->invoiceService->saveInvoice($invoiceData);

    $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
  }

  /**
   * Test cập nhật trạng thái thanh toán của hóa đơn
   */
  public function test_update_invoice_payment_status()
  {
    $invoice = Invoice::factory()->create(['status' => 'unpaid']);

    $updatedInvoice = $this->invoiceService->updatePaymentStatus($invoice->id, 'paid');

    $this->assertEquals('paid', $updatedInvoice->status);
  }

  /**
   * Test xóa hóa đơn
   */
  public function test_delete_invoice()
  {
    $invoice = Invoice::factory()->create();

    $this->invoiceService->deleteInvoice($invoice->id);

    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
  }

  /**
   * Test tìm kiếm hóa đơn theo mã
   */
  public function test_find_invoice_by_code()
  {
    $invoice = Invoice::factory()->create(['code' => 'INV123']);

    $foundInvoice = $this->invoiceService->findInvoiceByCode('INV123');

    $this->assertNotNull($foundInvoice);
    $this->assertEquals('INV123', $foundInvoice->code);
  }

  /**
   * Test lấy danh sách hóa đơn có phân trang
   */
  public function test_get_invoices_pagination()
  {
    Invoice::factory()->count(15)->create();

    $invoices = $this->invoiceService->getInvoices(10);

    $this->assertEquals(10, $invoices->count());
  }
}
