<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\POS\Requests\CustomerRequest;
use App\Http\POS\Resources\CustomerResource;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
  protected $customerService;

  public function __construct(CustomerService $customerService)
  {
    $this->customerService = $customerService;
  }

  public function index(Request $request)
  {
    $customers = $this->customerService->getList($request->all());
    return response()->json([
      'success' => true,
      'data' => CustomerResource::collection($customers)
    ]);
  }

  public function store(CustomerRequest $request)
  {
    $customer = $this->customerService->create($request->validated());
    return response()->json([
      'success' => true,
      'data' => new CustomerResource($customer)
    ]);
  }

  public function update(CustomerRequest $request, int $customerId)
  {
    $customer = $this->customerService->update($customerId, $request->validated());

    return response()->json([
      'success' => true,
      'data' => new CustomerResource($customer)
    ]);
  }

  public function findCustomer(Request $request)
  {
    $customer = $this->customerService->findCustomer($request->keyword);
    return response()->json([
      'success' => true,
      'data' => new CustomerResource($customer)
    ]);
  }

  public function receiveNewMemberGift(int $customerId)
  {
    $customer = $this->customerService->receiveNewMemberGift($customerId);
    return response()->json([
      'success' => true,
      'data' => new CustomerResource($customer)
    ]);
  }

  public function receiveBirthdayGift(int $customerId)
  {
    $customer = $this->customerService->receiveBirthdayGift($customerId);
    return response()->json([
      'success' => true,
      'data' => new CustomerResource($customer)
    ]);
  }
}
