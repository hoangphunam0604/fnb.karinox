<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{

  protected BaseService $service;
  protected string $resource;


  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return $this->resource::collection($items);
  }

  public function store(Request $request)
  {
    $item = $this->service->create($request->validated());
    return new $this->resource($item);
  }

  public function show($id)
  {
    $item = $this->service->find($id);
    return new $this->resource($item);
  }

  public function update(Request $request, $id)
  {
    $item = $this->service->update($id, $request->validated());
    return new $this->resource($item);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Xoá thành công!']);
  }
}
