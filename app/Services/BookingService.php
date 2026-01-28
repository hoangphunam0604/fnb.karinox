<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\ProductArenaType;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Log;

class BookingService
{

  public function __construct(
    protected TableAndRoomService $tableAndRoomService
  ) {}

  /**
   * Tạo booking socail nếu chưa có
   */
  public function createSocial(OrderItem $item, $product = null): void
  {
    $bookingData = json_decode($item->note, true);

    $startTime = $bookingData['startTime'] ?? '00:00';
    $endTime = $bookingData['endTime'] ?? '00:00';
    $dateString = $bookingData['date'];

    // Tính duration_hours
    $duration = $this->calculateDuration($startTime, $endTime);
    // Parse date format dd/mm/yyyy
    $date = Carbon::createFromFormat('d/m/Y', $dateString);

    // Parse time và gắn vào date
    [$startHour, $startMinute] = explode(':', $startTime);
    [$endHour, $endMinute] = explode(':', $endTime);

    $startDateTime = $date->copy()->setTime((int)$startHour, (int)$startMinute);
    $endDateTime = $date->copy()->setTime((int)$endHour, (int)$endMinute);

    // Nếu end time < start time thì end time là ngày hôm sau
    if ($endDateTime->lessThan($startDateTime)) {
      $endDateTime->addDay();
    }
    if (Booking::where('start_time', $startDateTime)->where('end_time', $endDateTime)->count() == 0)
      Booking::create([
        'table_id' => $item->order->table_id,
        'type' => BookingType::SOCIAL,
        'status' => BookingStatus::CONFIRMED,
        'start_time' => $startDateTime,
        'end_time' => $endDateTime,
        'duration_hours' => $duration,
      ]);
    $this->tableAndRoomService->addSlot($item->order->table_id, $item->quantity);
  }

  /**
   * Đồng bộ bookings cho một order item cụ thể
   * Xóa bookings cũ và tạo mới theo note hiện tại
   */
  public function syncBookingsForItem(OrderItem $item, $product = null): void
  {
    Log::info('syncBookingsForItem called', [
      'order_item_id' => $item->id,
      'note' => $item->note,
      'product_id' => $item->product_id
    ]);

    // Xóa tất cả bookings cũ của item này
    Booking::where('order_item_id', $item->id)->delete();

    // Tạo bookings mới
    $this->createBookingsFromItem($item);
  }

  /**
   * Tạo bookings từ order items đặt sân cố định trong order
   */
  public function createBookingsFromOrder(Order $order): void
  {
    foreach ($order->items as $item) {
      if ($item->isBookingFullSlot()) {
        $this->syncBookingsForItem($item);
      }
    }
  }

  /**
   * Parse JSON từ note và tạo bookings
   */
  private function createBookingsFromItem(OrderItem $item): void
  {
    try {
      // Parse JSON từ note
      $bookingData = json_decode($item->note, true);

      if (!$bookingData || !isset($bookingData['bookingList'])) {
        Log::warning("Invalid booking data in order item note", [
          'order_item_id' => $item->id,
          'note' => $item->note
        ]);
        return;
      }

      $startTime = $bookingData['startTime'] ?? '00:00';
      $endTime = $bookingData['endTime'] ?? '00:00';
      $bookingList = $bookingData['bookingList'] ?? [];

      // Tính duration_hours
      $duration = $this->calculateDuration($startTime, $endTime);

      // Tạo booking cho mỗi ngày trong danh sách
      foreach ($bookingList as $dateString) {
        $this->createSingleBooking($item, $dateString, $startTime, $endTime, $duration);
      }
    } catch (Exception $e) {
      Log::error("Error creating bookings from order item", [
        'order_item_id' => $item->id,
        'error' => $e->getMessage()
      ]);
    }
  }

  /**
   * Tạo một booking cho một ngày cụ thể
   */
  private function createSingleBooking(
    OrderItem $item,
    string $dateString,
    string $startTime,
    string $endTime,
    int $duration
  ): void {
    try {
      // Parse date format dd/mm/yyyy
      $date = Carbon::createFromFormat('d/m/Y', $dateString);

      // Parse time và gắn vào date
      [$startHour, $startMinute] = explode(':', $startTime);
      [$endHour, $endMinute] = explode(':', $endTime);

      $startDateTime = $date->copy()->setTime((int)$startHour, (int)$startMinute);
      $endDateTime = $date->copy()->setTime((int)$endHour, (int)$endMinute);

      // Nếu end time < start time thì end time là ngày hôm sau
      if ($endDateTime->lessThan($startDateTime)) {
        $endDateTime->addDay();
      }

      Booking::create([
        'order_id' => $item->order_id,
        'table_id' => $item->order->table_id,
        'user_id' => $item->order->user_id,
        'receiver_id' => $item->order->receiver_id,
        'customer_id' => $item->order->customer_id,
        'type' => BookingType::FULL,
        'status' => BookingStatus::PENDING,
        'start_time' => $startDateTime,
        'end_time' => $endDateTime,
        'duration_hours' => $duration,
        'order_item_id' => $item->id,
      ]);
    } catch (Exception $e) {
      Log::error("Error creating single booking", [
        'order_item_id' => $item->id,
        'date' => $dateString,
        'error' => $e->getMessage()
      ]);
    }
  }

  /**
   * Tính số giờ giữa start time và end time
   */
  private function calculateDuration(string $startTime, string $endTime): int
  {
    try {
      [$startHour, $startMinute] = explode(':', $startTime);
      [$endHour, $endMinute] = explode(':', $endTime);

      $start = Carbon::today()->setTime((int)$startHour, (int)$startMinute);
      $end = Carbon::today()->setTime((int)$endHour, (int)$endMinute);

      // Nếu end < start thì end là ngày hôm sau
      if ($end->lessThan($start)) {
        $end->addDay();
      }

      return (int)$start->diffInHours($end);
    } catch (Exception $e) {
      Log::error("Error calculating duration", [
        'start_time' => $startTime,
        'end_time' => $endTime,
        'error' => $e->getMessage()
      ]);
      return 1; // Default 1 hour
    }
  }

  /**
   * Xác nhận bookings khi order được thanh toán
   */
  public function confirmBookings(Order $order): void
  {
    Booking::where('order_id', $order->id)
      ->where('status', BookingStatus::PENDING)
      ->update(['status' => BookingStatus::CONFIRMED]);
  }

  /**
   * Hủy bookings khi order bị hủy
   */
  public function cancelBookings(Order $order): void
  {
    Booking::where('order_id', $order->id)
      ->where('status', BookingStatus::PENDING)
      ->delete();
  }

  public function createSocail(Order $order): void
  {
    // Logic tạo booking social theo sân nếu sân đó đang trống
  }
}
