<?php

namespace Tests\Unit\Services;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\ProductBranch;
use App\Services\StockDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class StockDeductionServiceTest extends TestCase
{
  use RefreshDatabase;

  private StockDeductionService $service;

  protected function setUp(): void
  {
    parent::setUp();
    $this->service = new StockDeductionService();
  }

  #[Test]
  #[TestDox("Kiểm tra kho trước khi chế biến với đủ nguyên liệu và topping")]
  public function checkStockForPreparation_with_sufficient_stock(): void
  {
    $orderItem = OrderItem::factory()->create();

    foreach ($orderItem->product->formulas as $formula) {
      ProductBranch::factory()->create([
        'product_id' => $formula->ingredient_id,
        'branch_id' => $orderItem->order->branch_id,
        'stock_quantity' => $formula->quantity * $orderItem->quantity + 10,
      ]);
    }

    foreach ($orderItem->toppings as $topping) {
      ProductBranch::factory()->create([
        'product_id' => $topping->product_id,
        'branch_id' => $orderItem->order->branch_id,
        'stock_quantity' => $topping->quantity + 10,
      ]);
    }

    $result = $this->service->checkStockForPreparation($orderItem);
    $this->assertTrue($result);
  }

  #[Test]
  #[TestDox("Kiểm tra kho trước khi chế biến khi thiếu nguyên liệu")]
  public function checkStockForPreparation_with_insufficient_ingredient_stock(): void
  {
    $orderItem = OrderItem::factory()->create();

    foreach ($orderItem->product->formulas as $formula) {
      ProductBranch::factory()->create([
        'product_id' => $formula->ingredient_id,
        'branch_id' => $orderItem->order->branch_id,
        'stock_quantity' => 0,
      ]);
    }

    $result = $this->service->checkStockForPreparation($orderItem);
    $this->assertFalse($result);
  }

  #[Test]
  #[TestDox("Kiểm tra kho trước khi chế biến khi thiếu topping")]
  public function checkStockForPreparation_with_insufficient_topping_stock(): void
  {
    $orderItem = OrderItem::factory()->create();

    foreach ($orderItem->toppings as $topping) {
      ProductBranch::factory()->create([
        'product_id' => $topping->product_id,
        'branch_id' => $orderItem->order->branch_id,
        'stock_quantity' => 0,
      ]);
    }

    $result = $this->service->checkStockForPreparation($orderItem);
    $this->assertFalse($result);
  }

  #[Test]
  #[TestDox("Kiểm tra kho trước khi chế biến khi thiếu cả nguyên liệu và topping")]
  public function checkStockForPreparation_with_insufficient_ingredient_and_topping_stock(): void
  {
    $orderItem = OrderItem::factory()->create();

    foreach ($orderItem->product->formulas as $formula) {
      ProductBranch::factory()->create([
        'product_id' => $formula->ingredient_id,
        'branch_id' => $orderItem->order->branch_id,
        'stock_quantity' => 0,
      ]);
    }

    foreach ($orderItem->toppings as $topping) {
      ProductBranch::factory()->create([
        'product_id' => $topping->product_id,
        'branch_id' => $orderItem->order->branch_id,
        'stock_quantity' => 0,
      ]);
    }

    $result = $this->service->checkStockForPreparation($orderItem);
    $this->assertFalse($result);
  }

  #[Test]
  #[TestDox("Trừ kho khi bếp đã nhận chế biến - món chưa chế biến")]
  public function deductStockForPreparation_when_order_not_prepared(): void
  {
    $orderItem1 = OrderItem::factory()->create(['status' => OrderItemStatus::PENDING]);
    $branchId1 = $orderItem1->order->branch_id;

    ProductBranch::factory()->create([
      'product_id' => $orderItem1->product_id,
      'branch_id' => $branchId1,
      'stock_quantity' => 50,
    ]);
    $this->service->deductStockForPreparation($orderItem1);

    $orderItem2 = OrderItem::factory()->create(['status' => OrderItemStatus::ACCEPTED]);
    $branchId2 = $orderItem1->order->branch_id;

    ProductBranch::factory()->create([
      'product_id' => $orderItem2->product_id,
      'branch_id' => $branchId2,
      'stock_quantity' => 50,
    ]);

    $this->service->deductStockForPreparation($orderItem2);
    $this->assertDatabaseHas('order_items', [
      'id' => $orderItem1->id,
      'status' => OrderItemStatus::PREPARED,
    ]);
    $this->assertDatabaseHas('order_items', [
      'id' => $orderItem2->id,
      'status' => OrderItemStatus::PREPARED,
    ]);
  }

  #[Test]
  #[TestDox("Không trừ kho khi món đã chế biến")]
  public function deductStockForPreparation_when_order_already_prepared(): void
  {
    $orderItem = OrderItem::factory()->create(['status' => OrderItemStatus::PREPARED]);

    $this->service->deductStockForPreparation($orderItem);

    $this->assertDatabaseHas('order_items', [
      'id' => $orderItem->id,
      'status' => OrderItemStatus::PREPARED,
    ]);
  }

  #[Test]
  #[TestDox("Trừ kho nguyên liệu khi chế biến")]
  public function deductStockForPreparation_deducts_ingredients(): void
  {
    $orderItem = OrderItem::factory()->create(['status' => OrderItemStatus::PENDING]);
    $branchId = $orderItem->order->branch_id;

    foreach ($orderItem->product->formulas as $formula) {
      ProductBranch::factory()->create([
        'product_id' => $formula->ingredient_id,
        'branch_id' => $branchId,
        'stock_quantity' => 100,
      ]);
    }

    $this->service->deductStockForPreparation($orderItem);

    foreach ($orderItem->product->formulas as $formula) {
      $this->assertDatabaseHas('product_branches', [
        'product_id' => $formula->ingredient_id,
        'branch_id' => $branchId,
        'stock_quantity' => 100 - ($formula->quantity * $orderItem->quantity),
      ]);
    }
  }

  #[Test]
  #[TestDox("Trừ kho topping khi chế biến")]
  public function deductStockForPreparation_deducts_toppings(): void
  {
    $orderItem = OrderItem::factory()->create(['status' => OrderItemStatus::PENDING]);
    $branchId = $orderItem->order->branch_id;

    foreach ($orderItem->toppings as $topping) {
      ProductBranch::factory()->create([
        'product_id' => $topping->product_id,
        'branch_id' => $branchId,
        'stock_quantity' => 50,
      ]);
    }

    $this->service->deductStockForPreparation($orderItem);

    foreach ($orderItem->toppings as $topping) {
      $this->assertDatabaseHas('product_branches', [
        'product_id' => $topping->product_id,
        'branch_id' => $branchId,
        'stock_quantity' => 50 - $topping->quantity,
      ]);
    }
  }

  #[Test]
  #[TestDox("Hoàn kho khi đơn hàng bị hoàn tiền")]
  public function restoreStockForRefundedInvoice_when_invoice_is_refunded(): void
  {
    $invoice = Invoice::factory()->create(['payment_status' => PaymentStatus::REFUNDED]);
    $branchId = $invoice->branch_id;

    foreach ($invoice->order->items as $orderItem) {
      ProductBranch::factory()->create([
        'product_id' => $orderItem->product_id,
        'branch_id' => $branchId,
        'stock_quantity' => 10,
      ]);
    }

    $this->service->restoreStockForRefundedInvoice($invoice);

    foreach ($invoice->order->items as $orderItem) {
      $this->assertDatabaseHas('product_branches', [
        'product_id' => $orderItem->product_id,
        'branch_id' => $branchId,
        'stock_quantity' => 10 + $orderItem->quantity,
      ]);
    }
  }

  #[Test]
  #[TestDox("Không hoàn kho nếu hóa đơn không bị hoàn tiền")]
  public function restoreStockForRefundedInvoice_when_invoice_is_not_refunded(): void
  {
    $invoice = Invoice::factory()->create(['payment_status' => PaymentStatus::PAID]);

    $this->expectException(\Exception::class);
    $this->service->restoreStockForRefundedInvoice($invoice);
  }

  #[Test]
  #[TestDox("Hoàn kho topping khi đơn hàng bị hoàn tiền")]
  public function restoreStockForRefundedInvoice_restores_toppings(): void
  {
    $invoice = Invoice::factory()->create(['payment_status' => PaymentStatus::REFUNDED]);
    $branchId = $invoice->branch_id;

    foreach ($invoice->order->items as $orderItem) {
      foreach ($orderItem->toppings as $topping) {
        ProductBranch::factory()->create([
          'product_id' => $topping->product_id,
          'branch_id' => $branchId,
          'stock_quantity' => 5,
        ]);
      }
    }

    $this->service->restoreStockForRefundedInvoice($invoice);

    foreach ($invoice->order->items as $orderItem) {
      foreach ($orderItem->toppings as $topping) {
        $this->assertDatabaseHas('product_branches', [
          'product_id' => $topping->product_id,
          'branch_id' => $branchId,
          'stock_quantity' => 5 + $topping->quantity,
        ]);
      }
    }
  }
}
