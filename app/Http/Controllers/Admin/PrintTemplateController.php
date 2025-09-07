<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StorePrintTemplateRequest;
use App\Http\Requests\Api\Admin\UpdatePrintTemplateRequest;
use Illuminate\Http\Request;
use App\Services\PrintTemplateService;
use App\Http\Resources\Admin\PrintTemplateResource;
use App\Models\PrintTemplate;

class PrintTemplateController extends Controller
{
  protected PrintTemplateService $service;

  public function __construct(PrintTemplateService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    $templates = $this->service->getAll($request->all(), $request->get('per_page', 20));
    return PrintTemplateResource::collection($templates);
  }

  public function show($id)
  {
    $template = $this->service->getById($id);
    if (!$template) {
      return response()->json(['message' => 'Không tìm thấy template'], 404);
    }

    return new PrintTemplateResource($template);
  }

  public function store(StorePrintTemplateRequest $request)
  {
    $template = $this->service->create($request->validated());
    return new PrintTemplateResource($template);
  }

  public function update(UpdatePrintTemplateRequest $request, $id)
  {
    $template = PrintTemplate::findOrFail($id);
    $updated = $this->service->update($template, $request->validated());
    return new PrintTemplateResource($updated);
  }

  public function destroy($id)
  {
    $template = PrintTemplate::findOrFail($id);
    $this->service->delete($template);
    return response()->noContent();
  }
}
