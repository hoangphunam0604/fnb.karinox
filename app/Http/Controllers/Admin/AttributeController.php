<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeRequest;
use App\Http\Resources\Admin\AttributeResource;
use App\Services\AttributeService;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
  protected AttributeService $attributeService;

  public function __construct(AttributeService $attributeService)
  {
    $this->attributeService = $attributeService;
  }

  public function index(Request $request)
  {
    $attributes = $this->attributeService->getList($request->all());
    return AttributeResource::collection($attributes);
  }

  public function store(AttributeRequest $request)
  {
    $attribute = $this->attributeService->create($request->validated());
    return new AttributeResource($attribute);
  }

  public function show($id)
  {
    $attribute = $this->attributeService->find($id);
    return new AttributeResource($attribute);
  }

  public function update(AttributeRequest $request, $id)
  {
    $attribute = $this->attributeService->update($id, $request->validated());
    return new AttributeResource($attribute);
  }

  public function destroy($id)
  {
    $this->attributeService->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
