<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Requests\HolidayRequest;
use App\Http\Admin\Resources\HolidayResource;
use App\Services\HolidayService;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
  public function __construct(protected HolidayService $service) {}

  public function index(Request $request)
  {
    $holidays = $this->service->getList($request->all());
    return HolidayResource::collection($holidays);
  }

  public function store(HolidayRequest $request)
  {
    $holiday = $this->service->create($request->validatedData());
    return new HolidayResource($holiday);
  }

  public function show($id)
  {
    $holiday = $this->service->find($id);
    return new HolidayResource($holiday);
  }

  public function update(HolidayRequest $request, $id)
  {
    $holiday = $this->service->update($id, $request->validatedData());
    return new HolidayResource($holiday);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
