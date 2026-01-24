<?php

namespace App\Http\POS\Controllers;

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
}
