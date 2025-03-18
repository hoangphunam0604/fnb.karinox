<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
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
    $customers = $this->customerService->getCustomers();
    return response()->json([
      'success' => true,
      'data' => CustomerResource::collection($customers)
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
}
