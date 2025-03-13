<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductImportService;
use Inertia\Inertia;

class ProductImportController extends Controller
{
  protected $productImportService;

  public function __construct(ProductImportService $productImportService)
  {
    $this->productImportService = $productImportService;
  }
  public function index()
  {
    return Inertia::render('Products/Import');
  }
  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx']);
    if (!$request->hasFile('file')) {
      return redirect()->back()->with('error', 'Không có file nào được tải lên.');
    }

    $file = $request->file('file');

    if (!$file->isValid()) {
      return redirect()->back()->with('error', 'File tải lên bị lỗi.');
    }
    dd($file->store('temp'));
    /* 
    $filePath = $file->store('temp');
    $filePath = $request->file('file')->store('temp');

    $result = $this->productImportService->importFromExcel(storage_path('app/' . $filePath)); */

    return response()->json($result);
  }
}
