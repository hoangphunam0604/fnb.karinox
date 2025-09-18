<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HolidayRequest;
use App\Http\Resources\Admin\HolidayResource;
use App\Services\HolidayService;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
  protected HolidayService $holidayService;

  public function __construct(HolidayService $holidayService)
  {
    $this->holidayService = $holidayService;
  }

  public function index(Request $request)
  {
    $holidays = $this->holidayService->getList($request->all());
    return HolidayResource::collection($holidays);
  }

  public function store(HolidayRequest $request)
  {
    $holiday = $this->holidayService->create($request->validatedData());
    return new HolidayResource($holiday);
  }

  public function show($id)
  {
    $holiday = $this->holidayService->find($id);
    return new HolidayResource($holiday);
  }

  public function update(HolidayRequest $request, $id)
  {
    $holiday = $this->holidayService->update($id, $request->validatedData());
    return new HolidayResource($holiday);
  }

  public function destroy($id)
  {
    $this->holidayService->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
