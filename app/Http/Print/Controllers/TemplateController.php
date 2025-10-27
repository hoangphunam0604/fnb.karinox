<?php

namespace App\Http\Print\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Print\Resources\PrintTemplateResource;
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
    $templates = $this->service->getUsedTemplateInBranch(
      $request->get('branch_id'),
      $request->get('type')
    );

    return response()->json(
      [
        'success' => true,
        'data' => PrintTemplateResource::collection($templates)
      ]
    );
  }
}
