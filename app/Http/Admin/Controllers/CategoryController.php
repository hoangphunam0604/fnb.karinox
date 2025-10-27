<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\CategoryRequest;
use App\Http\Admin\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
  public function __construct(protected CategoryService $service) {}

  public function index(Request $request)
  {
    $data = $this->service->getList($request->all());
    return CategoryResource::collection($data);
  }

  public function all()
  {
    $data = $this->service->getAll();
    return CategoryResource::collection($data);
  }

  public function store(CategoryRequest $request)
  {
    $category = $this->service->create($request->validated());
    return new CategoryResource($category);
  }

  public function show($id)
  {
    return new CategoryResource($this->service->find($id));
  }

  public function update(CategoryRequest $request, $id)
  {
    $category = $this->service->update($id, $request->validated());
    return new CategoryResource($category);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->noContent();
  }
}
