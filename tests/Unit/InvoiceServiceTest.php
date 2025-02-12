<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTopping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Product;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceServiceTest extends TestCase
{
  use RefreshDatabase;

  protected InvoiceService $invoiceService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->invoiceService = new InvoiceService();
  }

  /** @test */
  public function it_creates_invoice_from_completed_order_with_toppings()
  {
    $customer = Customer::factory()->create();
    $branch = Branch::factory()->create();

    // Tạo sản phẩm chính
    $product = Product::factory()->create(['is_topping' => false, 'price' => 50000]);

    // Tạo sản phẩm topping
    $topping = Product::factory()->create(['is_topping' => true, 'price' => 10000]);

    // Tạo đơn hàng đã hoàn tất
    $order = Order::factory()->create([
      'customer_id' => $customer->id,
      'branch_id' => $branch->id,
      'order_status' => 'completed',
      'total_price' => 60000,
      'discount_amount' => 0,
    ]);

    // Tạo sản phẩm trong đơn hàng
    $orderItem = OrderItem::factory()->create([
      'order_id' => $order->id,
      'product_id' => $product->id,
      'quantity' => 1,
      'unit_price' => 50000,
      'total_price_with_topping' => 70000,
    ]);

    // Tạo topping cho sản phẩm trong đơn hàng
    $orderTopping = OrderTopping::factory()->create([
      'order_item_id' => $orderItem->id,
      'topping_id' => $topping->id,
      'unit_price' => 10000,
      'quantity' => 2,
      'total_price' => 20000,
    ]);

    // Tạo hóa đơn từ đơn hàng
    $invoice = $this->invoiceService->createInvoiceFromOrder($order->id, 60000);

    // Kiểm tra hóa đơn đã được tạo
    $this->assertDatabaseHas('invoices', [
      'id' => $invoice->id,
      'order_id' => $order->id,
      'invoice_status' => 'pending',
      'payment_status' => 'unpaid',
    ]);

    // Kiểm tra sản phẩm trong hóa đơn
    $this->assertDatabaseHas('invoice_items', [
      'invoice_id' => $invoice->id,
      'product_id' => $product->id,
      'unit_price' => 50000,
      'total_price' => 50000,
      'quantity' => 1,
      'total_price_with_topping' => 70000,
    ]);

    // Kiểm tra topping trong hóa đơn
    $this->assertDatabaseHas('invoice_toppings', [
      'invoice_item_id' => $invoice->items()->first()->id,
      'topping_id' => $topping->id,
      'unit_price' => $orderTopping->unit_price,
      'quantity' => $orderTopping->quantity,
      'total_price' => $orderTopping->total_price,
    ]);
  }

  /** @test */
  public function it_throws_exception_if_order_is_not_completed()
  {
    $order = Order::factory()->create(['order_status' => 'pending']);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");

    $this->invoiceService->createInvoiceFromOrder($order->id);
  }

  /** @test */
  public function it_updates_invoice_payment_method_correctly()
  {
    $invoice = Invoice::factory()->create(['payment_method' => 'cash']);

    $updatedInvoice = $this->invoiceService->updatePaymentMethod($invoice->id, 'visa');

    $this->assertEquals('visa', $updatedInvoice->payment_method);
    $this->assertDatabaseHas('invoices', [
      'id' => $invoice->id,
      'payment_method' => 'visa',
    ]);
  }

  /** @test */
  public function it_updates_invoice_payment_status_correctly()
  {
    $invoice = Invoice::factory()->create(['payment_status' => 'unpaid']);

    $updatedInvoice = $this->invoiceService->updatePaymentStatus($invoice->id, 'paid');

    $this->assertEquals('paid', $updatedInvoice->payment_status);
    $this->assertDatabaseHas('invoices', [
      'id' => $invoice->id,
      'payment_status' => 'paid',
    ]);
  }

  /** @test */
  public function it_finds_invoice_by_code()
  {
    $invoice = Invoice::factory()->create(['code' => 'INV123']);

    $foundInvoice = $this->invoiceService->findInvoiceByCode('inv123');

    $this->assertNotNull($foundInvoice);
    $this->assertEquals($invoice->id, $foundInvoice->id);
  }

  /** @test */
  public function it_paginates_invoice_list()
  {
    Invoice::factory()->count(15)->create();

    $invoices = $this->invoiceService->getInvoices(10);

    $this->assertEquals(10, $invoices->count());
  }
}
