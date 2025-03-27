<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PrintTemplateService;
use App\Http\Resources\Api\POS\PrintTemplateResource;

class PrintTemplateController extends Controller
{
  protected PrintTemplateService $service;

  public function __construct(PrintTemplateService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    $templates = $this->service->getAll([
      'type' => $request->get('type'),
      'is_active' => true,
    ], perPage: 0); // Lấy tất cả

    return PrintTemplateResource::collection($templates);
  }

  public function show($id)
  {
    $template = $this->service->getById($id);

    if (!$template || !$template->is_active) {
      return response()->json(['message' => 'Không tìm thấy template'], 404);
    }

    return new PrintTemplateResource($template);
  }
}
