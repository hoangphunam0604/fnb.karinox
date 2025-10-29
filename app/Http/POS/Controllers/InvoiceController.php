<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Services\InvoiceService;
use App\Events\PrintRequested;

class InvoiceController extends Controller
{
  public function __construct(protected InvoiceService $invoiceService)
  {
    $this->invoiceService = $invoiceService;
  }

  public function show($id)
  {
    $invoice = $this->invoiceService->findById($id);
    return response()->json($invoice);
  }
  public function requestPrint($invoiceId, $brandId, $type)
  {
    broadcast(new PrintRequested($type, ['id' => $invoiceId], $brandId));
    return response()->json(['message' => 'Đã gửi yêu cầu in.']);
  }
}
