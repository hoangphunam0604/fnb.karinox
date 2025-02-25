<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductTopping;
use App\Models\ProductFormula;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
  /**
   * Nhập kho sản phẩm
   */
  public function importStock($branchId, $items, $note = null, $referenceId = null)
  {
    return $this->processTransaction($branchId, 'import', $items, $note, $referenceId);
  }

  /**
   * Xuất kho sản phẩm (không liên quan đến bán hàng)
   */
  public function exportStock($branchId, $items, $note = null, $referenceId = null)
  {
    return $this->processTransaction($branchId, 'export', $items, $note, $referenceId);
  }

  /**
   * Bán hàng - Giảm tồn kho sản phẩm, topping, nguyên liệu
   */
  public function saleStock($branchId, $items, $note = null, $referenceId = null)
  {
    return $this->processTransaction($branchId, 'sale', $items, $note, $referenceId);
  }

  /**
   * Trả hàng - Tăng tồn kho sản phẩm, topping, nguyên liệu
   */
  public function returnStock($branchId, $items, $note = null, $referenceId = null)
  {
    return $this->processTransaction($branchId, 'return', $items, $note, $referenceId);
  }

  /**
   * Chuyển hàng giữa các chi nhánh
   */
  public function transferStock($fromBranchId, $toBranchId, $items, $note = null, $referenceId = null)
  {
    // Giảm tồn kho chi nhánh nguồn
    $this->processTransaction($fromBranchId, 'transfer_out', $items, $note, $referenceId, $toBranchId);

    // Tăng tồn kho chi nhánh đích
    return $this->processTransaction($toBranchId, 'transfer_in', $items, $note, $referenceId, $fromBranchId);
  }

  /**
   * Kiểm kho - Cập nhật tồn kho theo số lượng thực tế
   */
  public function stockTaking($branchId, $items, $note = null, $referenceId = null)
  {
    return $this->processTransaction($branchId, 'stocktaking', $items, $note, $referenceId);
  }

  /**
   * Xử lý giao dịch kho chung
   */
  private function processTransaction($branchId, $transactionType, $items, $note = null, $referenceId = null, $destination_branch_id = null)
  {
    return DB::transaction(function () use ($branchId, $transactionType, $items, $note, $referenceId, $destination_branch_id) {
      $transaction = InventoryTransaction::create([
        'branch_id'       => $branchId,
        'transaction_type' => $transactionType,
        'note'    => $note,
        'reference_id'    => $referenceId,
        'destination_branch_id' => $destination_branch_id,
      ]);

      foreach ($items as $item) {
        $this->updateStock($branchId, $item['product_id'], $item['quantity'], $transactionType);

        InventoryTransactionItem::create([
          'transaction_id' => $transaction->id,
          'product_id'     => $item['product_id'],
          'quantity'       => $item['quantity']
        ]);
        // Nếu có topping, cập nhật tồn kho cho topping
        if (!empty($item['toppings'])) {
          foreach ($item['toppings'] as $topping) {
            $this->updateStock($branchId, $topping['product_id'], $topping['quantity'], $transactionType);
          }
        }
      }

      return $transaction;
    });
  }

  /**
   * Cập nhật tồn kho sản phẩm, topping, nguyên liệu, xử lý cả combo
   */
  private function updateStock($branchId, $productId, $quantity, $transactionType)
  {
    $productBranch = ProductBranch::where('branch_id', $branchId)
      ->where('product_id', $productId)
      ->firstOrFail();

    switch ($transactionType) {
      case 'import':
      case 'return':
      case 'transfer_in':
        $productBranch->stock_quantity += $quantity;
        break;
      case 'export':
      case 'sale':
      case 'transfer_out':
        $productBranch->stock_quantity -= $quantity;
        break;
      case 'stocktaking':
        $productBranch->stock_quantity = $quantity;
        break;
    }
    $productBranch->save();

    // Kiểm tra nếu sản phẩm là combo, xử lý các sản phẩm thành phần
    $product = Product::find($productId);
    if ($product && $product->product_type === 'combo') {
      $comboItems = ProductFormula::where('product_id', $productId)->get();
      foreach ($comboItems as $item) {
        $this->updateStock($branchId, $item->ingredient_id, $quantity * $item->quantity, $transactionType);
      }
    }

    // Cập nhật nguyên liệu nếu có
    $this->updateIngredientStock($branchId, $productId, $quantity, $transactionType);
  }


  /**
   * Cập nhật tồn kho nguyên liệu
   */
  private function updateIngredientStock($branchId, $productId, $quantity, $transactionType)
  {
    $ingredients = ProductFormula::where('product_id', $productId)->get();

    foreach ($ingredients as $ingredient) {
      $ingredientBranch = ProductBranch::where('branch_id', $branchId)
        ->where('product_id', $ingredient->ingredient_id)
        ->first();

      if ($ingredientBranch) {
        switch ($transactionType) {
          case 'sale':
          case 'export':
          case 'transfer_out':
            $ingredientBranch->stock_quantity -= $quantity * $ingredient->quantity;
            break;
          case 'return':
          case 'transfer_in':
            $ingredientBranch->stock_quantity += $quantity * $ingredient->quantity;
            break;
          case 'stocktaking':
            $ingredientBranch->stock_quantity = $quantity * $ingredient->quantity;
            break;
        }
        $ingredientBranch->save();
      }
    }
  }
  /**
   * Cập nhật tồn kho khi hoá đơn hoàn tất
   */
  public function deductStockForCompletedInvoice(Invoice $invoice)
  {
    if ($invoice->status !== 'completed') {
      throw new Exception("Chỉ có thể trừ kho khi hóa đơn hoàn tất.");
    }

    DB::transaction(function () use ($invoice) {
      $transaction = InventoryTransaction::create([
        'transaction_type' => 'sale',
        'reference_id' => $invoice->id,
        'branch_id' => $invoice->branch_id,
        'user_id' => Auth::id(),
      ]);

      // Lấy danh sách các món trong đơn hàng
      $orderItems = $invoice->order->items;

      // Lấy danh sách tất cả sản phẩm và topping trong đơn hàng
      $productIds = $orderItems->pluck('product_id')->merge(
        $orderItems->flatMap->toppings->pluck('product_id')
      )->unique();

      // Kiểm tra sản phẩm nào đã được trừ kho trước đó khi bếp chế biến
      $alreadyDeductedProducts = InventoryTransactionItem::whereHas('inventoryTransaction', function ($query) use ($invoice) {
        $query->where('transaction_type', 'preparation')
          ->where('reference_id', $invoice->order->id);
      })->whereIn('product_id', $productIds)
        ->pluck('product_id')
        ->toArray();

      foreach ($orderItems as $orderItem) {
        // Nếu món chính đã được trừ kho trước đó, bỏ qua
        if (in_array($orderItem->product_id, $alreadyDeductedProducts)) {
          continue;
        }

        // Trừ kho cho sản phẩm chính
        $productBranch = ProductBranch::where('product_id', $orderItem->product_id)
          ->where('branch_id', $invoice->branch_id)
          ->first();

        if ($productBranch) {
          $productBranch->decrement('stock_quantity', $orderItem->quantity);
        }

        InventoryTransactionItem::create([
          'inventory_transaction_id' => $transaction->id,
          'product_id' => $orderItem->product_id,
          'quantity' => -$orderItem->quantity,
          'sale_price' => $orderItem->unit_price,
        ]);

        // Trừ kho cho topping nếu có
        foreach ($orderItem->toppings as $topping) {
          // Nếu topping đã được trừ kho trước đó, bỏ qua
          if (in_array($topping->product_id, $alreadyDeductedProducts)) {
            continue;
          }

          $toppingBranch = ProductBranch::where('product_id', $topping->product_id)
            ->where('branch_id', $invoice->branch_id)
            ->first();

          if ($toppingBranch) {
            $toppingBranch->decrement('stock_quantity', $topping->quantity);
          }

          InventoryTransactionItem::create([
            'inventory_transaction_id' => $transaction->id,
            'product_id' => $topping->product_id,
            'quantity' => -$topping->quantity,
            'sale_price' => $topping->unit_price,
          ]);
        }
      }
    });
  }

  /**
   * Nếu đơn hàng bị hoàn tiền (refunded), hệ thống nhập lại kho.
   */
  public function restoreStockForRefundedInvoice(Invoice $invoice)
  {
    if ($invoice->status !== 'refunded') {
      throw new Exception("Chỉ có thể nhập kho khi hóa đơn bị hoàn tiền.");
    }

    DB::transaction(function () use ($invoice) {
      $transaction = InventoryTransaction::create([
        'transaction_type' => 'return',
        'reference_id' => $invoice->id,
        'branch_id' => $invoice->branch_id,
        'user_id' => Auth::id(),
      ]);

      foreach ($invoice->order->items as $orderItem) {
        ProductBranch::where('product_id', $orderItem->product_id)
          ->where('branch_id', $invoice->branch_id)
          ->increment('stock_quantity', $orderItem->quantity);

        InventoryTransactionItem::create([
          'inventory_transaction_id' => $transaction->id,
          'product_id' => $orderItem->product_id,
          'quantity' => $orderItem->quantity,
          'sale_price' => null,
        ]);

        // Hoàn lại kho topping nếu có
        foreach ($orderItem->toppings as $topping) {
          ProductBranch::where('product_id', $topping->product_id)
            ->where('branch_id', $invoice->branch_id)
            ->increment('stock_quantity', $topping->quantity);

          InventoryTransactionItem::create([
            'inventory_transaction_id' => $transaction->id,
            'product_id' => $topping->product_id,
            'quantity' => $topping->quantity,
            'sale_price' => null,
          ]);
        }
      }
    });
  }

  //Kiểm tra kho trước khi chế biến

  public function checkStockForPreparation(OrderItem $orderItem): bool
  {
    foreach ($orderItem->product->formulas as $formula) {
      $productBranch = ProductBranch::where('product_id', $formula->ingredient_id)
        ->where('branch_id', $orderItem->order->branch_id)
        ->first();

      if (!$productBranch || $productBranch->stock_quantity < ($formula->quantity * $orderItem->quantity)) {
        return false; // Không đủ nguyên liệu
      }
    }
    return true; // Đủ nguyên liệu
  }

  //Trừ kho khi bếp đã nhận chế biến
  public function deductStockForPreparation(OrderItem $orderItem)
  {
    if ($orderItem->status === 'prepared') {
      return; // Món đã chế biến, không cần trừ kho nữa
    }

    DB::transaction(function () use ($orderItem) {
      $transaction = InventoryTransaction::create([
        'transaction_type' => 'preparation',
        'reference_id' => $orderItem->order_id,
        'branch_id' => $orderItem->order->branch_id,
        'user_id' => Auth::id(),
      ]);

      foreach ($orderItem->product->formulas as $formula) {
        $quantityToDeduct = $formula->quantity * $orderItem->quantity;

        InventoryTransactionItem::create([
          'inventory_transaction_id' => $transaction->id,
          'product_id' => $formula->ingredient_id,
          'quantity' => -$quantityToDeduct,
          'sale_price' => null,
        ]);

        // Trừ tồn kho thực tế
        ProductBranch::where('product_id', $formula->ingredient_id)
          ->where('branch_id', $orderItem->order->branch_id)
          ->decrement('stock_quantity', $quantityToDeduct);
      }

      // Đánh dấu món đã được chế biến
      $orderItem->update(['status' => 'prepared']);
    });
  }
}
