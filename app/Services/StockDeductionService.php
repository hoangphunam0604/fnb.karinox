<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\ProductBranch;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockDeductionService
{
  /**
   * Kiểm tra kho trước khi chế biến (bao gồm nguyên liệu & topping)
   */
  public function checkStockForPreparation(OrderItem $orderItem): bool
  {
    $branchId = $orderItem->order->branch_id;

    // 🔹 Kiểm tra tồn kho nguyên liệu (product_formulas)
    $insufficientStock = $orderItem->product->formulas?->filter(function ($formula) use ($branchId, $orderItem) {
      $productBranch = ProductBranch::where('product_id', $formula->ingredient_id)
        ->where('branch_id', $branchId)
        ->first();

      return !$productBranch || $productBranch->stock_quantity < ($formula->quantity * $orderItem->quantity);
    });

    // 🔹 Kiểm tra tồn kho topping (orderItem->toppings)
    $toppingInsufficientStock = $orderItem->toppings?->filter(function ($topping) use ($branchId) {
      $productBranch = ProductBranch::where('product_id', $topping->product_id)
        ->where('branch_id', $branchId)
        ->first();

      return !$productBranch || $productBranch->stock_quantity < $topping->quantity;
    });

    return $insufficientStock->isEmpty() && $toppingInsufficientStock->isEmpty(); // Trả về true nếu đủ nguyên liệu & topping
  }

  /**
   * Trừ kho khi bếp đã nhận chế biến (cả nguyên liệu & topping)
   */
  public function deductStockForPreparation(OrderItem $orderItem)
  {
    /* if (!in_array($orderItem->status, [OrderItemStatus::PENDING, OrderItemStatus::ACCEPTED])) {
      return; // Chỉ trừ kho nếu đơn hàng đang ở trạng thái chờ xác nhận hoặc đã xác nhận
    } */

    DB::transaction(function () use ($orderItem) {
      $transaction = $this->createInventoryTransaction(InventoryTransactionType::SALE, $orderItem->order_id, $orderItem->order->branch_id);

      // 🔹 Trừ kho nguyên liệu (product_formulas)
      foreach ($orderItem->product->formulas as $formula) {
        if ($formula->quantity > 0) {
          $this->deductStock($transaction->id, $formula->ingredient_id, $formula->quantity * $orderItem->quantity, null, $orderItem->order->branch_id);
        }
      }

      // 🔹 Trừ kho topping
      foreach ($orderItem->toppings as $topping) {
        if ($topping->quantity > 0) {
          $this->deductStock($transaction->id, $topping->product_id, $topping->quantity, $topping->unit_price, $orderItem->order->branch_id);
        }
      }

      $orderItem->update(['status' => OrderItemStatus::PREPARED]);
    });
  }

  /**
   * Hoàn kho khi đơn hàng bị hoàn tiền
   */
  public function restoreStockForRefundedInvoice(Invoice $invoice)
  {
    if ($invoice->payment_status !== PaymentStatus::REFUNDED) {
      Log::error("Hóa đơn không hợp lệ để hoàn kho: Invoice ID {$invoice->id}");
      throw new Exception("Chỉ có thể nhập kho khi hóa đơn bị hoàn tiền.");
    }

    // Không dùng DB::transaction ở đây - để caller quản lý transaction
    $transaction = $this->createInventoryTransaction(InventoryTransactionType::RETURN, $invoice->id, $invoice->branch_id);

    foreach ($invoice->order->items as $orderItem) {
      $this->restoreStock($transaction->id, $orderItem->product_id, $orderItem->quantity, $invoice->branch_id);

      foreach ($orderItem->toppings as $topping) {
        $this->restoreStock($transaction->id, $topping->product_id, $topping->quantity, $invoice->branch_id);
      }
    }
  }

  /**
   * Tạo giao dịch kho
   */
  private function createInventoryTransaction(InventoryTransactionType $type, int $referenceId, int $branchId): InventoryTransaction
  {
    return InventoryTransaction::create([
      'transaction_type' => $type,
      'reference_id' => $referenceId,
      'branch_id' => $branchId,
      'user_id' =>  Auth::id(),
    ]);
  }

  /**
   * Trừ kho cho một sản phẩm
   */
  private function deductStock(int $transactionId, int $productId, int $quantity, ?float $salePrice, int $branchId)
  {
    ProductBranch::where('product_id', $productId)
      ->where('branch_id', $branchId)
      ->decrement('stock_quantity', $quantity);

    InventoryTransactionItem::create([
      'inventory_transaction_id' => $transactionId,
      'product_id' => $productId,
      'quantity' => -$quantity,
      'sale_price' => $salePrice,
    ]);
  }

  /**
   * Hoàn kho cho một sản phẩm
   */
  private function restoreStock(int $transactionId, int $productId, int $quantity, int $branchId)
  {
    $productBranch = ProductBranch::where('product_id', $productId)->where('branch_id', $branchId)->first();
    if ($productBranch && $quantity > 0) {
      $productBranch->increment('stock_quantity', $quantity);
    }

    InventoryTransactionItem::create([
      'inventory_transaction_id' => $transactionId,
      'product_id' => $productId,
      'quantity' => $quantity,
      'sale_price' => null,
    ]);
  }
}
