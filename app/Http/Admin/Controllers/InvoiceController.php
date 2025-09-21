<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{

  public function __construct(protected InvoiceService $service) {}

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
