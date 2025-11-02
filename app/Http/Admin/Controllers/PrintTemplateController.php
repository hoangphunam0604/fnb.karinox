<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\PrintTemplateRequest;
use Illuminate\Http\Request;
use App\Services\PrintTemplateService;
use App\Http\Admin\Resources\PrintTemplateResource;
use App\Models\PrintTemplate;

class PrintTemplateController extends Controller
{

  public function __construct(protected PrintTemplateService $service) {}

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

  public function store(PrintTemplateRequest $request)
  {
    $template = $this->service->create($request->validated());
    return new PrintTemplateResource($template);
  }

  public function update(PrintTemplateRequest $request, $id)
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

  public function setDefault($id)
  {
    $updated = $this->service->setDefault($id);
    return new PrintTemplateResource($updated);
  }
}
