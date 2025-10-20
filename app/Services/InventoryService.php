<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
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

        InventoryTransactionDetail::create([
          'inventory_transaction_id' => $transaction->id,
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
      ->first();

    if (!$productBranch) {
      throw new Exception("Sản phẩm không tồn tại trong chi nhánh này");
    }

    $currentStock = $productBranch->stock_quantity;
    $newStock = $currentStock;

    switch ($transactionType) {
      case 'import':
      case 'return':
      case 'transfer_in':
        $newStock = $currentStock + $quantity;
        break;
      case 'export':
      case 'sale':
      case 'transfer_out':
        $newStock = $currentStock - $quantity;
        break;
      case 'stocktaking':
        $newStock = $quantity;
        break;
    }

    // Sử dụng update thay vì save để tránh vấn đề composite key
    ProductBranch::where('branch_id', $branchId)
      ->where('product_id', $productId)
      ->update(['stock_quantity' => $newStock]);

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
        $currentStock = $ingredientBranch->stock_quantity;
        $newStock = $currentStock;

        switch ($transactionType) {
          case 'sale':
          case 'export':
          case 'transfer_out':
            $newStock = $currentStock - ($quantity * $ingredient->quantity);
            break;
          case 'return':
          case 'transfer_in':
            $newStock = $currentStock + ($quantity * $ingredient->quantity);
            break;
          case 'stocktaking':
            $newStock = $quantity * $ingredient->quantity;
            break;
        }

        // Sử dụng update thay vì save để tránh vấn đề composite key
        ProductBranch::where('branch_id', $branchId)
          ->where('product_id', $ingredient->ingredient_id)
          ->update(['stock_quantity' => $newStock]);
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
      $items = $invoice->order->items;

      // Lấy danh sách tất cả sản phẩm và topping trong đơn hàng
      $productIds = $items->pluck('product_id')->merge(
        $items->flatMap->toppings->pluck('product_id')
      )->unique();

      // Kiểm tra sản phẩm nào đã được trừ kho trước đó khi bếp chế biến
      $alreadyDeductedProducts = InventoryTransactionDetail::whereHas('inventoryTransaction', function ($query) use ($invoice) {
        $query->where('transaction_type', 'preparation')
          ->where('reference_id', $invoice->order->id);
      })->whereIn('product_id', $productIds)
        ->pluck('product_id')
        ->toArray();

      foreach ($items as $orderItem) {
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

        InventoryTransactionDetail::create([
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

          InventoryTransactionDetail::create([
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
   * Lấy danh sách giao dịch với filtering và pagination
   */
  public function getInventoryTransactions($branchId = null, $transactionType = null, $perPage = 20)
  {
    $query = InventoryTransaction::with(['branch', 'items.product'])
      ->orderBy('created_at', 'desc');

    if ($branchId) {
      $query->where('branch_id', $branchId);
    }

    if ($transactionType) {
      $query->where('transaction_type', $transactionType);
    }

    return $query->paginate($perPage);
  }

  /**
   * Lấy báo cáo tồn kho theo chi nhánh
   */
  public function getStockReport($branchId)
  {
    if (!$branchId) {
      throw new Exception('Branch ID is required');
    }

    return ProductBranch::with('product')
      ->where('branch_id', $branchId)
      ->whereHas('product', function ($query) {
        $query->where('manage_stock', true);
      })
      ->get();
  }

  /**
   * Xử lý kiểm kho với logic tính toán chênh lệch
   */
  public function processStocktaking($branchId, $items, $note = null)
  {
    $stockItems = [];
    $differences = [];

    foreach ($items as $item) {
      $productBranch = ProductBranch::where('branch_id', $branchId)
        ->where('product_id', $item['product_id'])
        ->first();

      if (!$productBranch) {
        continue;
      }

      $systemQuantity = $productBranch->stock_quantity;
      $actualQuantity = $item['actual_quantity'];
      $difference = $actualQuantity - $systemQuantity;

      // Chỉ tạo giao dịch nếu có chênh lệch
      if ($difference != 0) {
        $stockItems[] = [
          'product_id' => $item['product_id'],
          'quantity' => $actualQuantity,
        ];

        $differences[] = [
          'product_id' => $item['product_id'],
          'product_name' => $productBranch->product->name,
          'system_quantity' => $systemQuantity,
          'actual_quantity' => $actualQuantity,
          'difference' => $difference,
        ];
      }
    }

    if (empty($stockItems)) {
      return [
        'transaction' => null,
        'differences' => [],
        'message' => 'Không có chênh lệch nào, không cần điều chỉnh tồn kho'
      ];
    }

    // Tạo giao dịch kiểm kho
    $transaction = $this->stockTaking($branchId, $stockItems, $note);

    return [
      'transaction' => $transaction,
      'differences' => $differences,
      'message' => 'Kiểm kho thành công'
    ];
  }

  /**
   * Lấy thẻ kho cho sản phẩm cụ thể với filtering
   */
  public function getProductStockCard($productId, $branchId, $filters = [])
  {
    // Build query cho giao dịch
    $query = InventoryTransaction::with(['branch', 'user'])
      ->whereHas('items', function ($q) use ($productId) {
        $q->where('product_id', $productId);
      })
      ->where('branch_id', $branchId);

    // Filter theo ngày
    if (!empty($filters['from_date'])) {
      $query->whereDate('created_at', '>=', $filters['from_date']);
    }

    if (!empty($filters['to_date'])) {
      $query->whereDate('created_at', '<=', $filters['to_date']);
    }

    // Filter theo type
    if (!empty($filters['type'])) {
      $query->where('transaction_type', $filters['type']);
    }

    // Sắp xếp theo thời gian mới nhất
    $query->orderBy('created_at', 'desc');

    // Phân trang
    $perPage = $filters['per_page'] ?? 20;
    $transactions = $query->paginate($perPage);

    // Transform data với thông tin quantity
    $transactions->getCollection()->transform(function ($transaction) use ($productId) {
      // Lấy thông tin item của sản phẩm trong transaction này
      $item = $transaction->items()->where('product_id', $productId)->first();

      if ($item) {
        $transaction->pivot = $item;
      }

      return $transaction;
    });

    return $transactions;
  }

  /**
   * Lấy tóm tắt thẻ kho sản phẩm với thống kê
   */
  public function getProductStockSummary($productId, $branchId, $fromDate = null, $toDate = null)
  {
    // Lấy thông tin sản phẩm với category
    $product = Product::with('category')->find($productId);
    if (!$product) {
      throw new Exception('Sản phẩm không tồn tại');
    }

    // Lấy tồn kho hiện tại
    $currentStock = ProductBranch::where('product_id', $productId)
      ->where('branch_id', $branchId)
      ->first();

    $product->current_stock_quantity = $currentStock->stock_quantity ?? 0;
    $product->stock_last_updated = $currentStock->updated_at ?? null;

    // Tính toán thống kê
    $statisticsQuery = InventoryTransactionDetail::whereHas('transaction', function ($q) use ($branchId, $fromDate, $toDate) {
      $q->where('branch_id', $branchId);

      if ($fromDate) {
        $q->whereDate('created_at', '>=', $fromDate);
      }

      if ($toDate) {
        $q->whereDate('created_at', '<=', $toDate);
      }
    })->where('product_id', $productId);

    // Tổng hợp số liệu
    $product->total_imported = (clone $statisticsQuery)
      ->whereHas('transaction', fn($q) => $q->whereIn('transaction_type', ['import', 'transfer_in']))
      ->sum('quantity');

    $product->total_exported = (clone $statisticsQuery)
      ->whereHas('transaction', fn($q) => $q->whereIn('transaction_type', ['export', 'transfer_out', 'sale']))
      ->sum('quantity');

    $product->total_sold = (clone $statisticsQuery)
      ->whereHas('transaction', fn($q) => $q->where('transaction_type', 'sale'))
      ->sum('quantity');

    $product->total_adjusted = (clone $statisticsQuery)
      ->whereHas('transaction', fn($q) => $q->whereIn('transaction_type', ['stocktaking', 'adjustment']))
      ->sum('quantity');

    $product->transactions_count = (clone $statisticsQuery)->count();

    // Tính tồn đầu kỳ và cuối kỳ nếu có filter thời gian
    if ($fromDate && $toDate) {
      $product->period = "{$fromDate} đến {$toDate}";

      // Tồn đầu kỳ = tồn hiện tại - tổng thay đổi trong kỳ
      $netChangeInPeriod = $product->total_imported - $product->total_exported;
      $product->opening_stock = $product->current_stock_quantity - $netChangeInPeriod;
      $product->closing_stock = $product->current_stock_quantity;
    }

    return $product;
  }

  /**
   * Validate sản phẩm tồn tại
   */
  public function validateProductExists($productId)
  {
    $product = Product::find($productId);
    if (!$product) {
      throw new Exception('Sản phẩm không tồn tại');
    }
    return $product;
  }
}
