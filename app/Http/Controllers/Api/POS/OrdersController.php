<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AreaResource;
use App\Services\AreaService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrdersController extends Controller
{
  protected $areaService;

  public function __construct(AreaService $areaService)
  {
    $this->areaService = $areaService;
  }

  public function preOrder() {}
  public function order() {}
  public function useVoucher() {}
  public function usePoint() {}
  public function confirm() {}
  public function payment() {}
}
