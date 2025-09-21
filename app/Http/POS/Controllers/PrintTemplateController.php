<?php

namespace App\Http\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PrintTemplateService;
use App\Http\POS\Resources\PrintTemplateResource;

class PrintTemplateController extends Controller
{
  protected PrintTemplateService $service;

  public function __construct(PrintTemplateService $service)
  {
    $this->service = $service;
  }

  public function index()
  {
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null;
    if (!$branchId)
      return response()->json(['error' => 'Vui lòng chọn chi nhánh', 'karinox_branch_id' => $branchId], 400);

    $templates = $this->service->getUsedTemplateInBranch($branchId);
    return PrintTemplateResource::collection($templates);
  }
}
