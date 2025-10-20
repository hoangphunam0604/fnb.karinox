<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\ProductBranch;
use App\Models\ProductStockDependency;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockDeductionService
{
  protected ProductDependencyService $dependencyService;

  public function __construct(ProductDependencyService $dependencyService)
  {
    $this->dependencyService = $dependencyService;
  }
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

    InventoryTransactionDetail::create([
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

    InventoryTransactionDetail::create([
      'inventory_transaction_id' => $transactionId,
      'product_id' => $productId,
      'quantity' => $quantity,
      'sale_price' => null,
    ]);
  }

  /**
   * Trừ kho sử dụng pre-computed dependencies (Method mới)
   */
  public function deductStockUsingDependencies(OrderItem $orderItem): void
  {
    DB::transaction(function () use ($orderItem) {
      $transaction = $this->createInventoryTransaction(
        InventoryTransactionType::SALE,
        $orderItem->order_id,
        $orderItem->order->branch_id
      );

      // 🔹 Xử lý main product theo loại
      $this->deductStockForMainProduct($transaction, $orderItem);

      // 🔹 Trừ kho topping sử dụng dependencies
      foreach ($orderItem->toppings as $topping) {
        if ($topping->quantity > 0) {
          $this->deductStockForTopping($transaction, $topping, $orderItem->order->branch_id);
        }
      }

      // Update order item status
      $orderItem->update(['status' => OrderItemStatus::PREPARED]);
    });
  }

  /**
   * Trừ kho cho main product - luôn sử dụng ProductStockDependency
   */
  private function deductStockForMainProduct(InventoryTransaction $transaction, OrderItem $orderItem): void
  {
    $branchId = $orderItem->order->branch_id;

    // 🔹 Lấy tất cả dependencies từ pre-computed table (bao gồm cả self-reference)
    $dependencies = $this->dependencyService->getDependencies($orderItem->product_id);

    Log::info("🔍 Deducting stock for main product", [
      'product_id' => $orderItem->product_id,
      'branch_id' => $branchId,
      'dependencies_count' => $dependencies->count()
    ]);

    foreach ($dependencies as $dependency) {
      $quantityToDeduct = $dependency->quantity * $orderItem->quantity;

      if ($quantityToDeduct > 0) {
        // 🔸 Kiểm tra target product có manage_stock không
        $targetProduct = \App\Models\Product::find($dependency->target_product_id);

        Log::info("📦 Processing dependency", [
          'target_product_id' => $dependency->target_product_id,
          'target_product_name' => $targetProduct?->name,
          'manage_stock' => $targetProduct?->manage_stock ?? false,
          'quantity_to_deduct' => $quantityToDeduct
        ]);

        if ($targetProduct && $targetProduct->manage_stock) {
          $this->deductStock(
            $transaction->id,
            $dependency->target_product_id,
            $quantityToDeduct,
            // Nếu là self-reference thì dùng unit_price, ngược lại null
            $dependency->target_product_id === $orderItem->product_id ? $orderItem->unit_price : null,
            $branchId
          );

          Log::info("✅ Deducted stock", [
            'product_id' => $dependency->target_product_id,
            'quantity' => $quantityToDeduct
          ]);
        }
      }
    }
  }

  /**
   * Kiểm tra kho sử dụng pre-computed dependencies (Method mới)
   */
  public function checkStockUsingDependencies(OrderItem $orderItem): bool
  {
    $branchId = $orderItem->order->branch_id;

    // 🔹 Kiểm tra main product
    if (!$this->checkStockForMainProduct($orderItem, $branchId)) {
      return false;
    }

    // 🔹 Kiểm tra toppings sử dụng dependencies
    foreach ($orderItem->toppings as $topping) {
      if ($topping->quantity > 0) {
        if (!$this->checkStockForTopping($topping, $branchId)) {
          Log::warning("Insufficient stock for topping", [
            'order_item_id' => $orderItem->id,
            'topping_id' => $topping->product_id,
            'required_quantity' => $topping->quantity
          ]);
          return false;
        }
      }
    }

    return true;
  }

  /**
   * Kiểm tra kho cho main product - luôn sử dụng ProductStockDependency
   */
  private function checkStockForMainProduct(OrderItem $orderItem, int $branchId): bool
  {
    // 🔹 Lấy tất cả dependencies từ pre-computed table (bao gồm cả self-reference)
    $dependencies = $this->dependencyService->getDependencies($orderItem->product_id);

    foreach ($dependencies as $dependency) {
      $requiredQuantity = $dependency->quantity * $orderItem->quantity;

      // 🔸 Kiểm tra target product có manage_stock không
      $targetProduct = \App\Models\Product::find($dependency->target_product_id);

      if ($targetProduct && $targetProduct->manage_stock) {
        $productBranch = ProductBranch::where('product_id', $dependency->target_product_id)
          ->where('branch_id', $branchId)
          ->first();

        if (!$productBranch || $productBranch->stock_quantity < $requiredQuantity) {
          Log::warning("Insufficient stock for dependency", [
            'order_item_id' => $orderItem->id,
            'source_product_id' => $orderItem->product_id,
            'target_product_id' => $dependency->target_product_id,
            'required_quantity' => $requiredQuantity,
            'available_quantity' => $productBranch->stock_quantity ?? 0
          ]);
          return false;
        }
      }
    }

    return true;
  }
  /**
   * Trừ kho cho topping sử dụng dependencies
   */
  private function deductStockForTopping(InventoryTransaction $transaction, $topping, int $branchId): void
  {
    // Load topping product với relationships
    $toppingProduct = \App\Models\Product::with(['formulas.ingredient'])->find($topping->topping_id);

    if (!$toppingProduct) {
      Log::warning("Topping product not found", ['topping_id' => $topping->topping_id]);
      return;
    }

    // Lấy dependencies của topping
    $dependencies = $this->dependencyService->getDependencies($topping->topping_id);

    if ($dependencies->isNotEmpty()) {
      // Topping có dependencies - trừ kho theo dependencies
      foreach ($dependencies as $dependency) {
        $quantityToDeduct = $dependency->quantity * $topping->quantity;

        if ($quantityToDeduct > 0) {
          // 🔸 Kiểm tra target product có manage_stock không
          $targetProduct = \App\Models\Product::find($dependency->target_product_id);

          if ($targetProduct && $targetProduct->manage_stock) {
            $this->deductStock(
              $transaction->id,
              $dependency->target_product_id,
              $quantityToDeduct,
              null, // No sale price for dependency components
              $branchId
            );
          }
        }
      }
    }
  }
  private function checkStockForTopping($topping, int $branchId): bool
  {
    // Load topping product
    $toppingProduct = \App\Models\Product::find($topping->product_id);

    if (!$toppingProduct) {
      return false;
    }

    // 🔹 Lấy tất cả dependencies từ pre-computed table
    $dependencies = $this->dependencyService->getDependencies($topping->product_id);

    foreach ($dependencies as $dependency) {
      $requiredQuantity = $dependency->quantity * $topping->quantity;

      // 🔸 Kiểm tra target product có manage_stock không
      $targetProduct = \App\Models\Product::find($dependency->target_product_id);

      if ($targetProduct && $targetProduct->manage_stock) {
        $productBranch = ProductBranch::where('product_id', $dependency->target_product_id)
          ->where('branch_id', $branchId)
          ->first();

        if (!$productBranch || $productBranch->stock_quantity < $requiredQuantity) {
          return false;
        }
      }
    }

    return true;
  }

  /**
   * Trừ kho cho Invoice đã được tạo
   * Method này sẽ được gọi từ DeductStockAfterInvoice listener
   */
  public function deductStockForCompletedInvoice(Invoice $invoice): void
  {
    $order = $invoice->order;

    if (!$order) {
      throw new \Exception("Invoice #{$invoice->id} không có order liên kết");
    }

    // Kiểm tra đã trừ kho chưa
    if ($order->stock_deducted_at) {
      Log::info("Stock already deducted for invoice", [
        'invoice_id' => $invoice->id,
        'order_id' => $order->id,
        'deducted_at' => $order->stock_deducted_at
      ]);
      return;
    }

    DB::transaction(function () use ($invoice, $order) {
      // Tạo inventory transaction cho việc bán hàng
      $inventoryTransaction = InventoryTransaction::create([
        'transaction_type' => InventoryTransactionType::SALE,
        'branch_id' => $order->branch_id,
        'reference_id' => $invoice->id, // Link với Invoice ID
        'user_id' => $order->user_id ?? Auth::id(),
        'note' => "Stock deduction for Invoice #{$invoice->invoice_number} (Order #{$order->id})"
      ]);

      // Load order items với relationships
      $order->loadMissing(['items.product', 'items.toppings']);

      foreach ($order->items as $orderItem) {
        // Trừ kho cho tất cả items (bỏ check status vì đã có invoice)
        $this->deductStockUsingDependencies($orderItem);

        // Nếu sản phẩm không có dependencies, trừ kho trực tiếp
        if ($orderItem->product_id && (!$orderItem->product->formulas || $orderItem->product->formulas->isEmpty())) {
          Log::info("Product has no formulas, deducting stock directly", [
            'product_id' => $orderItem->product_id,
            'quantity' => $orderItem->quantity
          ]);

          // Trừ kho trực tiếp từ product_branches
          ProductBranch::where('product_id', $orderItem->product_id)
            ->where('branch_id', $order->branch_id)
            ->decrement('stock_quantity', $orderItem->quantity);
        }

        // Tạo inventory transaction detail cho mỗi item
        $this->createTransactionDetailForOrderItem($inventoryTransaction, $orderItem);
      }

      // Đánh dấu đã trừ kho
      $order->update(['stock_deducted_at' => now()]);

      Log::info("Stock deducted successfully for invoice", [
        'invoice_id' => $invoice->id,
        'invoice_number' => $invoice->invoice_number,
        'order_id' => $order->id,
        'inventory_transaction_id' => $inventoryTransaction->id
      ]);
    });
  }

  /**
   * Tạo inventory transaction detail cho order item
   */
  private function createTransactionDetailForOrderItem(InventoryTransaction $transaction, OrderItem $orderItem): void
  {
    Log::info("Creating transaction details for order item", [
      'transaction_id' => $transaction->id,
      'order_item_id' => $orderItem->id,
      'product_id' => $orderItem->product_id,
      'quantity' => $orderItem->quantity
    ]);

    // Tạo detail cho sản phẩm chính
    if ($orderItem->product_id) {
      try {
        $detail = $transaction->details()->create([
          'product_id' => $orderItem->product_id,
          'quantity' => -$orderItem->quantity, // Số âm cho xuất kho
          'cost_price' => $orderItem->product->cost_price ?? 0,
          'sale_price' => $orderItem->unit_price
        ]);

        Log::info("Created transaction detail for main product", [
          'detail_id' => $detail->id,
          'product_id' => $orderItem->product_id,
          'quantity' => -$orderItem->quantity
        ]);
      } catch (\Exception $e) {
        Log::error("Failed to create transaction detail for main product", [
          'error' => $e->getMessage(),
          'product_id' => $orderItem->product_id
        ]);
      }
    } else {
      Log::warning("Order item has no product_id", ['order_item_id' => $orderItem->id]);
    }

    // Tạo detail cho toppings
    foreach ($orderItem->toppings as $topping) {
      $transaction->details()->create([
        'product_id' => $topping->product_id,
        'quantity' => -$topping->quantity,
        'cost_price' => $topping->product->cost_price ?? 0,
        'sale_price' => $topping->unit_price
      ]);
    }

    // Tạo detail cho nguyên liệu (nếu có công thức)
    if ($orderItem->product && $orderItem->product->formulas) {
      foreach ($orderItem->product->formulas as $formula) {
        $totalQuantityNeeded = $formula->quantity * $orderItem->quantity;

        $transaction->details()->create([
          'product_id' => $formula->ingredient_id,
          'quantity' => -$totalQuantityNeeded,
          'cost_price' => $formula->ingredient->cost_price ?? 0,
          'sale_price' => null // Nguyên liệu không có giá bán
        ]);
      }
    }
  }
}
