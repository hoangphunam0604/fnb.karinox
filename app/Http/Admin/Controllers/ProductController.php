<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\ProductRequest;
use App\Http\Admin\Resources\ProductResource;
use App\Http\Admin\Resources\ProductDetailResource;
use App\Services\ProductService;
use App\Services\ProductImportService;
use Illuminate\Http\Request;

class ProductController extends Controller
{

  public function __construct(protected ProductService $service, protected ProductImportService $importService) {}

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return ProductResource::collection($items);
  }

  public function store(ProductRequest $request)
  {
    $item = $this->service->create($request->validated());
    return new ProductResource($item);
  }

  public function show($id)
  {
    $item = $this->service->find($id);
    $item->load([
      'category',
      'branches',
      'attributes',
      'toppings.topping',
      'formulas.ingredient',
    ]);
    return new ProductDetailResource($item);
  }

  public function update(ProductRequest $request, $id)
  {
    $item = $this->service->update($id, $request->validated());
    return new ProductResource($item);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }


  public function import(Request $request)
  {
    $request->validate(['branch_id' => 'required', 'file' => 'required|mimes:xlsx']);

    if (!$request->hasFile('file')) {
      return response()->json(['error' => 'Không có file nào được tải lên.'], 400);
    }

    $file = $request->file('file');

    if (!$file->isValid()) {
      return response()->json(['error' => 'File tải lên bị lỗi.'], 400);
    }
    // Kiểm tra tên file và kích thước

    // Lưu file vào storage/temp
    //$filePath = $file->store('temp');
    $filePath = $file->move(public_path('imports/product'), time() . '-' . $file->getClientOriginalName());

    if (!$filePath) {
      return response()->json(['error' => 'Lưu file thất bại.'], 500);
    }


    $fullPath = public_path('imports/product/' . basename($filePath));

    $branch_id = $request->branch_id;
    $result = $this->importService->importFromExcel($branch_id, $fullPath);

    return response()->json($result);
  }

  public function manufacturingAutocomplete(Request $request)
  {
    $items = $this->service->manufacturingAutocomplete($request->all());
    return ProductResource::collection($items);
  }
}
