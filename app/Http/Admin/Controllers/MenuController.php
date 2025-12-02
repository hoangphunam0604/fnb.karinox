<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\CategoryRequest;
use App\Http\Admin\Resources\MenuResource;
use App\Services\MenuService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
  public function __construct(protected MenuService $service) {}

  public function index(Request $request)
  {
    $data = $this->service->getList($request->all());
    return MenuResource::collection($data);
  }

  public function all()
  {
    $data = $this->service->getAll();
    return MenuResource::collection($data);
  }

  public function store(CategoryRequest $request)
  {
    $category = $this->service->create($request->validated());
    return new MenuResource($category);
  }

  public function show($id)
  {
    return new MenuResource($this->service->find($id));
  }

  public function update(CategoryRequest $request, $id)
  {
    $category = $this->service->update($id, $request->validated());
    return new MenuResource($category);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->noContent();
  }
}
