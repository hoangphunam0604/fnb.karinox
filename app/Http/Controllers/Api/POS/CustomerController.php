<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\CustomerRequest;
use App\Http\Resources\Api\POS\CategoryProductResponse;
use App\Http\Resources\Api\POS\CustomerResource;
use App\Services\CustomerService;
use App\Services\ProductService;
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
    $customers = $this->customerService->getCustomers(10, $request->keyword);
    return response()->json([
      'success' => true,
      'data' => CustomerResource::collection($customers)
    ]);
  }

  public function store(CustomerRequest $request)
  {
    $customer = $this->customerService->createCustomer($request->validated());
    return response()->json([
      'success' => true,
      'data' => new CustomerResource($customer)
    ]);
  }

  public function update(CustomerRequest $request, int $customerId)
  {
    $customer = $this->customerService->updateCustomer($customerId, $request->validated());

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
  public function receiveBirthdayGift(int $customerId)
  {
    $customer = $this->customerService->receiveBirthdayGift($customerId);
    return response()->json([
      'success' => true,
      'data' => new CustomerResource($customer)
    ]);
  }
}
