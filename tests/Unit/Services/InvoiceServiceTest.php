<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
  use RefreshDatabase;

  private InvoiceService $invoiceService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->invoiceService = $this->app->make(InvoiceService::class);
  }

  /**
   * @testdox Tạo hóa đơn từ đơn hàng thành công
   * @test
   */
  public function test_create_invoice_from_order_successfully()
  {
    $order = Order::factory()->has(OrderItem::factory()->count(2))->create(['order_status' => 'completed']);

    $invoice = $this->invoiceService->createInvoiceFromOrder($order->id, 100);

    $this->assertInstanceOf(Invoice::class, $invoice);
    $this->assertEquals($order->id, $invoice->order_id);
    $this->assertEquals(100, $invoice->paid_amount);
    $this->assertEquals('pending', $invoice->invoice_status);
    $this->assertEquals('unpaid', $invoice->payment_status);
  }
  /**
   * @testdox Tạo hóa đơn từ đơn hàng có sản phẩm và topping thành công
   * @test
   */
  public function test_create_invoice_from_order_with_toppings()
  {
    $order = Order::factory()
      ->has(
        OrderItem::factory()
          ->has(OrderTopping::factory()->count(2))
          ->count(2)
      )
      ->create(['order_status' => 'completed']);

    $invoice = $this->invoiceService->createInvoiceFromOrder($order->id, 100);

    $this->assertInstanceOf(Invoice::class, $invoice);
    $this->assertEquals($order->id, $invoice->order_id);
    $this->assertEquals(100, $invoice->paid_amount);
    $this->assertEquals('pending', $invoice->invoice_status);
    $this->assertEquals('unpaid', $invoice->payment_status);

    foreach ($invoice->items as $invoiceItem) {
      $this->assertCount(2, $invoiceItem->toppings);
    }
  }

  /**
   * @testdox Tạo hóa đơn thất bại nếu đơn hàng chưa hoàn tất
   * @test
   */
  public function test_create_invoice_fails_if_order_not_completed()
  {
    $order = Order::factory()->create(['order_status' => 'pending']);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");

    $this->invoiceService->createInvoiceFromOrder($order->id);
  }

  /**
   * @testdox Cập nhật phương thức thanh toán thành công
   * @test
   */
  public function test_update_payment_method_successfully()
  {
    $invoice = Invoice::factory()->create();

    $updatedInvoice = $this->invoiceService->updatePaymentMethod($invoice->id, 'credit_card');

    $this->assertEquals('credit_card', $updatedInvoice->payment_method);
  }

  /**
   * @testdox Cập nhật trạng thái thanh toán thành công
   * @test
   */
  public function test_update_payment_status_successfully()
  {
    $invoice = Invoice::factory()->create(['payment_status' => 'unpaid']);

    $updatedInvoice = $this->invoiceService->updatePaymentStatus($invoice->id, 'paid');

    $this->assertEquals('paid', $updatedInvoice->payment_status);
  }

  /**
   * @testdox Không thể cập nhật trạng thái thanh toán với giá trị không hợp lệ
   * @test
   */
  public function test_update_payment_status_fails_with_invalid_status()
  {
    $invoice = Invoice::factory()->create();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Trạng thái thanh toán không hợp lệ.");

    $this->invoiceService->updatePaymentStatus($invoice->id, 'invalid_status');
  }

  /**
   * @testdox Tìm hóa đơn theo mã thành công
   * @test
   */
  public function test_find_invoice_by_code_returns_invoice()
  {
    $invoice = Invoice::factory()->create(['code' => 'INV123']);

    $foundInvoice = $this->invoiceService->findInvoiceByCode('INV123');

    $this->assertNotNull($foundInvoice);
    $this->assertEquals($invoice->id, $foundInvoice->id);
  }

  /**
   * @testdox Tìm hóa đơn theo mã nhưng không tìm thấy
   * @test
   */
  public function test_find_invoice_by_code_returns_null_if_not_found()
  {
    $foundInvoice = $this->invoiceService->findInvoiceByCode('NON_EXISTENT_CODE');

    $this->assertNull($foundInvoice);
  }

  /**
   * @testdox Lấy danh sách hóa đơn với phân trang
   * @test
   */
  public function test_get_invoices_pagination()
  {
    Invoice::factory()->count(15)->create();

    $invoices = $this->invoiceService->getInvoices(10);

    $this->assertCount(10, $invoices);
    $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $invoices);
  }

  /**
   * @testdox Kiểm tra hóa đơn có thể hoàn tiền hay không
   * @test
   */
  public function test_can_be_refunded_returns_true()
  {
    $invoice = Invoice::factory()->create();

    $this->assertTrue($this->invoiceService->canBeRefunded($invoice));
  }
}
