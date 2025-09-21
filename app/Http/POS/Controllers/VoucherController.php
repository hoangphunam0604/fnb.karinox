<?php

namespace App\Http\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Http\POS\Resources\CustomerResource;
use App\Http\POS\Resources\VoucherResource;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
  protected $voucherService;

  public function __construct(VoucherService $voucherService)
  {
    $this->voucherService = $voucherService;
  }

  public function index(Request $request)
  {
    $customerId = $request->customerId;
    $totalPrice = $request->totalPrice;
    $vouchers = $this->voucherService->getValidVouchers($customerId, $totalPrice);
    return VoucherResource::collection($vouchers);
  }
}
