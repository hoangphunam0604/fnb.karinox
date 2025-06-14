<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\Admin\ProductResource;
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
    $areas = $this->service->getList($request->all());
    return ProductResource::collection($areas);
  }

  public function store(ProductRequest $request)
  {
    $area = $this->service->create($request->validated());
    return new ProductResource($area);
  }

  public function show($id)
  {
    $area = $this->service->find($id);
    return new ProductResource($area);
  }

  public function update(ProductRequest $request, $id)
  {
    $area = $this->service->update($id, $request->validated());
    return new ProductResource($area);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
