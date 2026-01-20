<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\POS\Resources\PrintTemplateResource;
use App\Services\PrintTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class TemplateController extends Controller
{
  protected PrintTemplateService $service;

  public function __construct(PrintTemplateService $service)
  {
    $this->service = $service;
  }

  /**
   * Get available print templates for branch
   * GET /api/print/templates?brand_id=BRANCH001&type=invoice
   */
  public function index(Request $request): JsonResponse
  {
    $branch_id = app()->bound('karinox_branch_id') ? app('karinox_branch_id') :  null;
    $templates = $this->service->getUsedTemplateInBranch(
      $branch_id
    );

    return response()->json(
      [
        'success' => true,
        'data' => PrintTemplateResource::collection($templates)
      ]
    );
  }
}
