<?php

namespace App\Http\Admin\Controllers;

use App\Client\KiotViet;
use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\ProductRequest;
use App\Http\Admin\Resources\ProductResource;
use App\Http\Admin\Resources\ProductDetailResource;
use App\Services\ProductService;
use App\Services\ProductImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{

  public function __construct(
    protected ProductService $service,
    protected ProductImportService $importService,
    protected KiotViet $kiotVietClient
  ) {}

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

  /* 
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
  } */

  public function manufacturingAutocomplete(Request $request)
  {
    $items = $this->service->manufacturingAutocomplete($request->all());
    return ProductResource::collection($items);
  }
  public function kiotViet(Request $request)
  {
    $pageSize  = $request->get('pageSize', 100);
    $currentItem =  $request->get('currentItem', 0);
    $result = $this->kiotVietClient->getProducts($pageSize, $currentItem);
    $data = [];
    /* foreach ($result['data'] as $item) {
      $data[] = ['productType'  =>  $item['productType'], 'name' =>  $item['name']];
    }
    return response()->json($data); */
    return response()->json($result);
  }

  public function syncFromKiotViet()
  {
    return new StreamedResponse(function () {
      $result = $this->importService->importFromKiotViet();

      return response()->json($result);
    }, 200, [
      'Content-Type' => 'text/event-stream',
      'Cache-Control' => 'no-cache, no-transform',
      'X-Accel-Buffering' => 'no'
    ]);
  }
}
