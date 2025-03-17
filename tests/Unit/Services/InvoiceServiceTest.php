<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Product;
use App\Services\InvoiceService;
use App\Services\PointService;
use App\Services\StockDeductionService;
use App\Services\TaxService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use PHPUnit\Framework\Attributes\Test;
use Mockery;

class InvoiceServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $taxServiceMock;
  protected $pointServiceMock;
  protected $voucherServiceMock;
  protected $stockDeductionServiceMock;
  protected $invoiceService;
  protected function setUp(): void
  {
    parent::setUp();
    $this->taxServiceMock = Mockery::spy(TaxService::class);
    $this->app->instance(TaxService::class, $this->taxServiceMock);

    $this->pointServiceMock = Mockery::spy(PointService::class);
    $this->app->instance(PointService::class, $this->pointServiceMock);

    $this->voucherServiceMock = Mockery::spy(VoucherService::class);
    $this->app->instance(VoucherService::class, $this->voucherServiceMock);

    $this->stockDeductionServiceMock = Mockery::spy(StockDeductionService::class);
    $this->app->instance(StockDeductionService::class, $this->stockDeductionServiceMock);

    $this->invoiceService = app(InvoiceService::class);
  }

  #[Test]
  public function it_finds_invoice_by_code()
  {
    $invoice = Invoice::factory()->create(['code' => 'INV123']);

    $foundInvoice = $this->invoiceService->findInvoiceByCode('inv123');

    $this->assertNotNull($foundInvoice);
    $this->assertEquals($invoice->id, $foundInvoice->id);
  }

  #[Test]
  public function it_paginates_invoice_list()
  {
    Invoice::factory()->count(5)->create();

    $invoices = $this->invoiceService->getInvoices(5);

    $this->assertEquals(5, $invoices->count());
  }


  #[Test]
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
      'order_status' => OrderStatus::COMPLETED,
      'subtotal_price' => 70000, //sản phẩm 50k, 2 topping 20k
      'discount_amount' => 20000, // Dùng mã giảm giá 20k
      'reward_discount' =>  10000, // Dùng điểm giảm 10k
      'total_price' =>  40000 // Dùng điểm giảm 10k
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
    // Mock applyVoucherToOrder để không gọi thực tế
    $this->taxServiceMock->shouldReceive('calculateTax')
      ->once()
      ->andReturnUsing(function () {
        return [
          'tax_rate' => 5,
          'tax_amount' => 10000,
          'total_price_without_vat' => 30000
        ];
      });
    // Mock VoucherService::transferUsedPointsToInvoice để không gọi thực tế
    $this->voucherServiceMock->shouldReceive('transferUsedPointsToInvoice')
      ->once()
      ->andReturn(true);

    // Mock earnPointsOnTransactionCompletion để không gọi thực tế
    $this->pointServiceMock->shouldReceive('earnPointsOnTransactionCompletion')
      ->once()
      ->andReturn(true);

    // Tạo hóa đơn từ đơn hàng
    $invoice = $this->invoiceService->createInvoiceFromOrder($order->id);

    //Kiểm tra giá trị đơn hàng hợp lệ
    $this->assertEquals(70000, $invoice->subtotal_price);
    $this->assertEquals(20000, $invoice->discount_amount);
    $this->assertEquals(10000, $invoice->reward_discount);
    $this->assertEquals(40000, $invoice->total_price);
    $this->assertEquals(5, $invoice->tax_rate);
    $this->assertEquals(10000, $invoice->tax_amount);
    $this->assertEquals(30000, $invoice->total_price_without_vat);

    // Kiểm tra hóa đơn đã được tạo
    $this->assertDatabaseHas('invoices', [
      'id' => $invoice->id,
      'order_id' => $order->id,
      'invoice_status' => InvoiceStatus::PENDING,
      'payment_status' => PaymentStatus::UNPAID,
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


  #[Test]
  public function it_throws_exception_if_order_is_not_completed()
  {
    $order = Order::factory()->create(['order_status' => OrderStatus::PENDING]);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");

    $this->invoiceService->createInvoiceFromOrder($order->id);
  }


  #[Test]
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


  #[Test]
  public function it_updates_invoice_payment_status_correctly()
  {
    $invoice = Invoice::factory()->create(['payment_status' => PaymentStatus::UNPAID]);

    $updatedInvoice = $this->invoiceService->updatePaymentStatus($invoice->id, PaymentStatus::PAID);

    $this->assertEquals(PaymentStatus::PAID, $updatedInvoice->payment_status);
    $this->assertDatabaseHas('invoices', [
      'id' => $invoice->id,
      'payment_status' => PaymentStatus::PAID,
    ]);
  }


  #[Test]
  public function it_returns_true_if_invoice_can_be_refunded(): void
  {
    // Tạo một hóa đơn đã thanh toán
    $invoice = Invoice::factory()->make(['payment_status' => PaymentStatus::PAID]);

    // Gọi hàm kiểm tra
    $result = $this->invoiceService->canBeRefunded($invoice);

    // Đảm bảo kết quả đúng
    self::assertTrue($result);
  }


  #[Test]
  public function it_returns_false_if_invoice_cannot_be_refunded(): void
  {
    // Tạo một hóa đơn chưa thanh toán
    $invoice = Invoice::factory()->make(['payment_status' => PaymentStatus::UNPAID]);

    // Gọi hàm kiểm tra
    $result = $this->invoiceService->canBeRefunded($invoice);

    // Đảm bảo kết quả đúng
    self::assertFalse($result);
  }


  #[Test]
  public function it_successfully_refunds_a_paid_invoice(): void
  {
    // Tạo một hóa đơn đã thanh toán
    $invoice = Invoice::factory()->create(['payment_status' => PaymentStatus::PAID]);

    // Gọi hàm hoàn tiền
    $refundedInvoice = $this->invoiceService->refunded($invoice->id);

    // Kiểm tra hóa đơn đã chuyển sang trạng thái REFUNDED
    self::assertEquals(PaymentStatus::REFUNDED, $refundedInvoice->payment_status);
  }


  #[Test]
  public function it_throws_exception_if_invoice_cannot_be_refunded(): void
  {
    // Tạo một hóa đơn chưa thanh toán
    $invoice = Invoice::factory()->create(['payment_status' => PaymentStatus::UNPAID]);

    // Mong đợi Exception xảy ra
    self::expectException(\Exception::class);
    self::expectExceptionMessage("Đơn hàng chưa thanh toán, không thể hoàn tiền.");

    // Gọi hàm hoàn tiền (nên ném lỗi)
    $this->invoiceService->refunded($invoice->id);
  }
}
