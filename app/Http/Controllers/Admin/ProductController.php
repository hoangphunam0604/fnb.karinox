<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Http\Resources\Admin\ProductDetailResource;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
  protected ProductService $service;

  public function __construct(ProductService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return ProductResource::collection($items);
  }

  public function manufacturingAutocomplete(Request $request)
  {
    $items = $this->service->manufacturingAutocomplete($request->all());
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
      'toppings',
      'formulas',
    ]);
    return new ProductDetailResource($item);
  }

  public function update(ProductRequest $request, $id)
  {
    $item = $this->service->update($id, $request->validated());
    return $item;
    return new ProductResource($item);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
