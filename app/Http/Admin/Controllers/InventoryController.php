<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Resources\InventoryTransactionResource;
use App\Http\Admin\Resources\StockReportResource;
use App\Http\Admin\Resources\ProductStockCardResource;
use App\Http\Admin\Resources\ProductStockSummaryResource;
use App\Services\InventoryService;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\ProductBranch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

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
    $branchId = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);
    
    if (!$branchId) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh'], 400);
    }
    $transactionType = $request->input('transaction_type');
    $perPage = $request->input('per_page', 20);

    $transactions = $this->inventoryService->getInventoryTransactions($branchId, $transactionType, $perPage);

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
    $branchId = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);

    if (!$branchId) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh'], 400);
    }

    try {
      $stocks = $this->inventoryService->getStockReport($branchId);
      return StockReportResource::collection($stocks);
    } catch (Exception $e) {
      return response()->json(['error' => $e->getMessage()], 400);
    }
  }

  /**
   * Tạo phiếu kiểm kho
   */
  public function stocktaking(Request $request)
  {
    $branchId = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);
    $request->merge(['branch_id' => $branchId]);

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

    try {
      $result = $this->inventoryService->processStocktaking(
        $branchId,
        $request->input('items'),
        $request->input('note')
      );

      if (!$result['transaction']) {
        return response()->json([
          'message' => $result['message'],
          'differences' => $result['differences']
        ], 200);
      }

      return response()->json([
        'message' => $result['message'],
        'transaction' => new InventoryTransactionResource($result['transaction']->load(['branch', 'items.product'])),
        'differences' => $result['differences']
      ], 201);
    } catch (Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Nhập kho
   */
  public function import(Request $request)
  {
    $branchId = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);
    $request->merge(['branch_id' => $branchId]);

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

    try {
      $transaction = $this->inventoryService->importStock(
        $branchId,
        $request->input('items'),
        $request->input('note')
      );

      return response()->json([
        'message' => 'Nhập kho thành công',
        'transaction' => new InventoryTransactionResource($transaction->load(['branch', 'items.product']))
      ], 201);
    } catch (Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Xuất kho
   */
  public function export(Request $request)
  {
    $branchId = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);
    $request->merge(['branch_id' => $branchId]);

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

    try {
      $transaction = $this->inventoryService->exportStock(
        $branchId,
        $request->input('items'),
        $request->input('note')
      );

      return response()->json([
        'message' => 'Xuất kho thành công',
        'transaction' => new InventoryTransactionResource($transaction->load(['branch', 'items.product']))
      ], 201);
    } catch (Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Chuyển kho giữa các chi nhánh
   */
  public function transfer(Request $request)
  {
    $fromBranchId = $request->input('from_branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);
    $request->merge(['from_branch_id' => $fromBranchId]);

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

    try {
      $transaction = $this->inventoryService->transferStock(
        $fromBranchId,
        $request->input('to_branch_id'),
        $request->input('items'),
        $request->input('note')
      );

      return response()->json([
        'message' => 'Chuyển kho thành công',
        'transaction' => new InventoryTransactionResource($transaction->load(['branch', 'items.product']))
      ], 201);
    } catch (Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Lấy thẻ kho cho sản phẩm cụ thể
   */
  public function getProductStockCard(Request $request, $productId)
  {
    $branchId = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);

    if (!$branchId) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh'], 400);
    }

    // Validate query parameters
    $validator = Validator::make($request->all(), [
      'from_date' => 'nullable|date',
      'to_date' => 'nullable|date|after_or_equal:from_date',
      'type' => 'nullable|in:import,export,transfer_in,transfer_out,stocktaking,adjustment,sale,return',
      'per_page' => 'nullable|integer|min:1|max:100'
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
      // Validate product exists
      $this->inventoryService->validateProductExists($productId);

      // Get filters
      $filters = [
        'from_date' => $request->input('from_date'),
        'to_date' => $request->input('to_date'),
        'type' => $request->input('type'),
        'per_page' => $request->input('per_page', 20)
      ];

      $transactions = $this->inventoryService->getProductStockCard($productId, $branchId, $filters);

      return ProductStockCardResource::collection($transactions);
    } catch (Exception $e) {
      return response()->json(['error' => $e->getMessage()], 404);
    }
  }

  /**
   * Lấy tóm tắt thẻ kho sản phẩm
   */
  public function getProductStockSummary(Request $request, $productId)
  {
    $branchId = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);

    if (!$branchId) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh'], 400);
    }

    // Validate query parameters
    $validator = Validator::make($request->all(), [
      'from_date' => 'nullable|date',
      'to_date' => 'nullable|date|after_or_equal:from_date',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
      $product = $this->inventoryService->getProductStockSummary(
        $productId,
        $branchId,
        $request->input('from_date'),
        $request->input('to_date')
      );

      return new ProductStockSummaryResource($product);
    } catch (Exception $e) {
      return response()->json(['error' => $e->getMessage()], 404);
    }
  }
}
