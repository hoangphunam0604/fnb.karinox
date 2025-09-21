<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Requests\AttributeRequest;
use App\Http\Admin\Resources\AttributeResource;
use App\Services\AttributeService;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
  public function __construct(protected AttributeService $service) {}

  public function index(Request $request)
  {
    $attributes = $this->service->getList($request->all());
    return AttributeResource::collection($attributes);
  }

  public function store(AttributeRequest $request)
  {
    $attribute = $this->service->create($request->validated());
    return new AttributeResource($attribute);
  }

  public function show($id)
  {
    $attribute = $this->service->find($id);
    return new AttributeResource($attribute);
  }

  public function update(AttributeRequest $request, $id)
  {
    $attribute = $this->service->update($id, $request->validated());
    return new AttributeResource($attribute);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
