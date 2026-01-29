<?php

namespace App\Http\Common\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Common\Resources\CustomerResource;
use App\Services\CustomerService;

class CustomerController extends Controller
{

  public function __construct(protected CustomerService $service) {}

  public function findByCode($code)
  {
    $customer = $this->service->findCustomer($code);
    return new CustomerResource($customer);
  }
}
