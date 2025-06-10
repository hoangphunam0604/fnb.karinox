<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\Admin\CategoryResource;
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
