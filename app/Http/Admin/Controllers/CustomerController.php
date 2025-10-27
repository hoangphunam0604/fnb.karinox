<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\CustomerRequest;
use App\Http\Admin\Resources\CustomerResource;
use App\Services\CustomerImportService;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

  public function __construct(protected CustomerService $service, protected CustomerImportService $importService) {}

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return CustomerResource::collection($items);
  }

  public function store(CustomerRequest $request)
  {
    $item = $this->service->create($request->validated());
    return new CustomerResource($item);
  }

  public function show($id)
  {
    $item = $this->service->find($id);
    $item->load([
      'membershipLevel'
    ]);
    return new CustomerResource($item);
  }

  public function update(CustomerRequest $request, $id)
  {
    $item = $this->service->update($id, $request->validated());
    return $item;
    return new CustomerResource($item);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
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
    $filePath = $file->move(public_path('imports/customer'), time() . '-' . $file->getClientOriginalName());

    if (!$filePath) {
      return response()->json(['error' => 'Lưu file thất bại.'], 500);
    }


    $fullPath = public_path('imports/customer/' . basename($filePath));

    $result = $this->importService->import($fullPath);

    return response()->json($result);
  }
}
