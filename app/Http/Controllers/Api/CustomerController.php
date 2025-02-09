<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
  protected $customerService;

  public function __construct(CustomerService $customerService)
  {
    $this->customerService = $customerService;
  }

  public function store(Request $request)
  {
    $customer = $this->customerService->createCustomer($request->all());
    return response()->json($customer);
  }

  public function update(Request $request, $id)
  {
    $customer = $this->customerService->updateCustomer($id, $request->all());
    return response()->json($customer);
  }

  public function destroy($id)
  {
    $this->customerService->deleteCustomer($id);
    return response()->json(['message' => 'Khách hàng đã được xóa']);
  }

  public function search(Request $request)
  {
    $customer = $this->customerService->findCustomer($request->input('keyword'));
    return response()->json($customer);
  }

  public function index()
  {
    $customers = $this->customerService->getCustomers();
    return response()->json($customers);
  }
}
