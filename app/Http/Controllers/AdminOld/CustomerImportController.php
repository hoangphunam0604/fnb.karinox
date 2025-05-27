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

    if (!$request->hasFile('file')) {
      return response()->json(['error' => 'Không có file nào được tải lên.'], 400);
    }

    $file = $request->file('file');

    if (!$file->isValid()) {
      return response()->json(['error' => 'File tải lên bị lỗi.'], 400);
    }
    // Kiểm tra tên file và kích thước

    // Lưu file vào storage/temp
    //$filePath = $file->store('temp');
    $filePath = $file->move(public_path('uploads'), time() . '-' . $file->getClientOriginalName());

    if (!$filePath) {
      return response()->json(['error' => 'Lưu file thất bại.'], 500);
    }


    $fullPath = public_path('uploads/' . basename($filePath));

    $result = $this->importService->import($fullPath);

    return response()->json($result);
  }
}
