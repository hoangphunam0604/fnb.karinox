<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductTopping;
use App\Models\ProductFormula;
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

    // Cập nhật topping và nguyên liệu nếu có
    $this->updateToppingStock($branchId, $productId, $quantity, $transactionType);
    $this->updateIngredientStock($branchId, $productId, $quantity, $transactionType);
  }

  /**
   * Cập nhật tồn kho topping
   */
  private function updateToppingStock($branchId, $productId, $quantity, $transactionType)
  {
    $toppings = ProductTopping::where('product_id', $productId)->get();

    foreach ($toppings as $topping) {
      $toppingBranch = ProductBranch::where('branch_id', $branchId)
        ->where('product_id', $topping->topping_id)
        ->first();

      if ($toppingBranch) {
        switch ($transactionType) {
          case 'sale':
          case 'export':
          case 'transfer_out':
            $toppingBranch->stock_quantity -= $quantity * $topping->quantity;
            break;
          case 'return':
          case 'transfer_in':
            $toppingBranch->stock_quantity += $quantity * $topping->quantity;
            break;
          case 'stocktaking':
            $toppingBranch->stock_quantity = $quantity * $topping->quantity;
            break;
        }
        $toppingBranch->save();
      }
    }
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
}
