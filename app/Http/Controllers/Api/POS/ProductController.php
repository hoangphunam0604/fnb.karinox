<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\POS\CategoryProductResponse;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
  protected $productService;

  public function __construct(ProductService $productService)
  {
    $this->productService = $productService;
  }

  /**
   * API lấy danh sách sản phẩm nhóm theo danh mục của chi nhánh được chọn.
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function index(Request $request)
  { 
    $user = auth('api')->user();

    if (!$user || !$user->current_branch) {
      return response()->json([
        'success' => false,
        
        'message' => 'Người dùng hoặc chi nhánh không hợp lệ',
      ], 400);
    }
    $branchId = $user->current_branch;
    if (!$branchId) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh'], 400);
    }

    $category_products = $this->productService->getProductsByBranch($branchId);


    return response()->json([
      'success' => true,
      'data' => CategoryProductResponse::collection($category_products)
    ]);
  }
}
