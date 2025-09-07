<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Http\Resources\Admin\CustomerResource;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
  protected CustomerService $service;

  public function __construct(CustomerService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return CustomerResource::collection($items);
  }

  public function store(CustomerRequest $request)
  {
    $item = $this->service->create($request->validated());
    return new CustomerResource($item);
  }

  public function show($id)
  {
    $item = $this->service->find($id);
    $item->load([
      'membershipLevel'
    ]);
    return new CustomerResource($item);
  }

  public function update(CustomerRequest $request, $id)
  {
    $item = $this->service->update($id, $request->validated());
    return $item;
    return new CustomerResource($item);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
