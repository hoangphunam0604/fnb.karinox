<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CustomerImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerImportController extends Controller
{
  protected $importService;

  public function __construct(CustomerImportService $importService)
  {
    $this->importService = $importService;
  }

  public function viewImport(): Response
  {
    return Inertia::render('Customer/Import');
  }

  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|mimes:xlsx,xls'
    ]);

    $result = $this->importService->import($request->file('file'));

    return redirect()->back()->with('message', $result['message']);
  }
}
