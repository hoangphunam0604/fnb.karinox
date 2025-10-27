<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\POS\Resources\CategoryProductResource;
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
   * @return \Illuminate\Http\JsonResponse
   */
  public function index()
  {
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null;
    if (!$branchId) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh', 'karinox_branch_id' => $branchId], 400);
    }
    $category_products = $this->productService->getProductsByBranch($branchId);
    return response()->json([
      'success' => true,
      'data' => CategoryProductResource::collection($category_products)
    ]);
  }
}
