<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
  protected InvoiceService $service;

  public function __construct(InvoiceService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return InvoiceResource::collection($items);
  }

  public function show($id)
  {
    $item = $this->service->find($id);
    $item->load([
      'items.toppings'
    ]);
    return new InvoiceResource($item);
  }
}
