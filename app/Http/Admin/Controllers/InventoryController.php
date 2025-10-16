<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Resources\InventoryTransactionResource;
use App\Http\Admin\Resources\StockReportResource;
use App\Services\InventoryService;
use App\Models\InventoryTransaction;
use App\Models\ProductBranch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
  protected InventoryService $inventoryService;

  public function __construct(InventoryService $inventoryService)
  {
    $this->inventoryService = $inventoryService;
  }

  /**
   * Lấy danh sách giao dịch kho
   */
  public function index(Request $request)
  {
    $branchId = $request->input('branch_id');
    $transactionType = $request->input('transaction_type');
    $perPage = $request->input('per_page', 20);

    $query = InventoryTransaction::with(['branch', 'items.product'])
      ->orderBy('created_at', 'desc');

    if ($branchId) {
      $query->where('branch_id', $branchId);
    }

    if ($transactionType) {
      $query->where('transaction_type', $transactionType);
    }

    $transactions = $query->paginate($perPage);

    return InventoryTransactionResource::collection($transactions);
  }

  /**
   * Xem chi tiết một giao dịch kho
   */
  public function show($id)
  {
    $transaction = InventoryTransaction::with(['branch', 'items.product'])
      ->findOrFail($id);

    return new InventoryTransactionResource($transaction);
  }

  /**
   * Lấy báo cáo tồn kho theo chi nhánh
   */
  public function getStockReport(Request $request)
  {
    $branchId = $request->input('branch_id');

    if (!$branchId) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh'], 400);
    }

    $stocks = ProductBranch::with('product')
      ->where('branch_id', $branchId)
      ->whereHas('product', function ($query) {
        $query->where('manage_stock', true);
      })
      ->get();

    return StockReportResource::collection($stocks);
  }

  /**
   * Tạo phiếu kiểm kho
   */
  public function stocktaking(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'branch_id' => 'required|exists:branches,id',
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'required|exists:products,id',
      'items.*.actual_quantity' => 'required|numeric|min:0',
      'note' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $branchId = $request->input('branch_id');
    $items = $request->input('items');
    $note = $request->input('note');

    // Chuẩn bị dữ liệu cho stockTaking
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
      return response()->json([
        'message' => 'Không có chênh lệch nào, không cần điều chỉnh tồn kho',
        'differences' => []
      ], 200);
    }

    // Tạo giao dịch kiểm kho
    $transaction = $this->inventoryService->stockTaking($branchId, $stockItems, $note);

    return response()->json([
      'message' => 'Kiểm kho thành công',
      'transaction' => new InventoryTransactionResource($transaction->load(['branch', 'items.product'])),
      'differences' => $differences
    ], 201);
  }

  /**
   * Nhập kho
   */
  public function import(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'branch_id' => 'required|exists:branches,id',
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'required|exists:products,id',
      'items.*.quantity' => 'required|numeric|min:0',
      'note' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $transaction = $this->inventoryService->importStock(
      $request->input('branch_id'),
      $request->input('items'),
      $request->input('note')
    );

    return response()->json([
      'message' => 'Nhập kho thành công',
      'transaction' => new InventoryTransactionResource($transaction->load(['branch', 'items.product']))
    ], 201);
  }

  /**
   * Xuất kho
   */
  public function export(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'branch_id' => 'required|exists:branches,id',
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'required|exists:products,id',
      'items.*.quantity' => 'required|numeric|min:0',
      'note' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $transaction = $this->inventoryService->exportStock(
      $request->input('branch_id'),
      $request->input('items'),
      $request->input('note')
    );

    return response()->json([
      'message' => 'Xuất kho thành công',
      'transaction' => new InventoryTransactionResource($transaction->load(['branch', 'items.product']))
    ], 201);
  }

  /**
   * Chuyển kho giữa các chi nhánh
   */
  public function transfer(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'from_branch_id' => 'required|exists:branches,id',
      'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'required|exists:products,id',
      'items.*.quantity' => 'required|numeric|min:0',
      'note' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $transaction = $this->inventoryService->transferStock(
      $request->input('from_branch_id'),
      $request->input('to_branch_id'),
      $request->input('items'),
      $request->input('note')
    );

    return response()->json([
      'message' => 'Chuyển kho thành công',
      'transaction' => new InventoryTransactionResource($transaction->load(['branch', 'items.product']))
    ], 201);
  }
}
