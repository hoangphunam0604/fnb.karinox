<?php

namespace App\Http\POS\Controllers;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Http\POS\Requests\BookingRequest;
use App\Http\POS\Resources\BookingResource;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BookingController extends Controller
{
  /**
   * Lấy danh sách bookings theo khoảng thời gian
   * 
   * @param Request $request
   * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function index(Request $request)
  {
    $validated = $request->validate([
      'start_date' => 'required|date_format:Y-m-d',
      'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
    ]);

    $startDate = Carbon::parse($validated['start_date'])->startOfDay();
    $endDate = Carbon::parse($validated['end_date'])->endOfDay();

    $bookings = Booking::with(['order', 'table', 'customer'])
      ->whereBetween('start_time', [$startDate, $endDate])
      ->orWhereBetween('end_time', [$startDate, $endDate])
      ->orWhere(function ($query) use ($startDate, $endDate) {
        // Bao gồm cả booking bắt đầu trước và kết thúc sau khoảng thời gian
        $query->where('start_time', '<=', $startDate)
          ->where('end_time', '>=', $endDate);
      })
      ->orderBy('start_time', 'asc')
      ->get();

    return BookingResource::collection($bookings);
  }

  /**
   * Tạo booking mới
   * 
   * @param BookingRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(BookingRequest $request)
  {
    $validated = $request->validated();

    $booking = Booking::create([
      'table_id' => $validated['table_id'],
      'name' => $validated['name'] ?? 'Social Booking',
      'type' => BookingType::SOCIAL,
      'status' => BookingStatus::CONFIRMED,
      'start_time' => Carbon::parse($validated['start_time']),
      'end_time' => Carbon::parse($validated['end_time']),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Tạo booking thành công.',
      'data' => new BookingResource($booking)
    ], 201);
  }

  /**
   * Cập nhật booking
   * 
   * @param BookingRequest $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function update(BookingRequest $request, $id)
  {
    $booking = Booking::findOrFail($id);
    $validated = $request->validated();

    $booking->update([
      'table_id' => $validated['table_id'],
      'start_time' => Carbon::parse($validated['start_time']),
      'end_time' => Carbon::parse($validated['end_time']),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Cập nhật booking thành công.',
      'data' => new BookingResource($booking->fresh())
    ]);
  }

  /**
   * Xoá booking
   * 
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function destroy($id)
  {
    $booking = Booking::findOrFail($id);

    $booking->delete();

    return response()->json([
      'success' => true,
      'message' => 'Xoá booking thành công.'
    ]);
  }
}
