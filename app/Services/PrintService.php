<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintTemplate;
use App\Models\PrintQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrintService
{
  /**
   * In phiếu tạm tính (Provisional Bill)
   * Dùng khi khách hàng yêu cầu xem tổng tiền trước khi thanh toán
   */
  public function printProvisional(Order $order, ?string $deviceId = null)
  {
    try {
      DB::beginTransaction();

      // Lấy template tạm tính
      $template = $this->getTemplate($order->branch_id, 'provisional');

      // Render nội dung
      $content = $this->renderTemplate($template->content, $order);

      // Tạo print job
      $printJob = $this->createPrintJob($order->branch_id, 'provisional', $content, [
        'order_id' => $order->id,
        'device_id' => $deviceId,
        'priority' => 'normal'
      ]);

      DB::commit();

      Log::info("Provisional bill queued for order {$order->order_code}", [
        'print_job_id' => $printJob->id,
        'device_id' => $deviceId
      ]);

      return [
        'success' => true,
        'print_job_id' => $printJob->id,
        'content' => $content,
        'message' => 'Phiếu tạm tính đã được đưa vào hàng đợi in'
      ];
    } catch (\Exception $e) {
      DB::rollback();
      Log::error("Failed to queue provisional bill: " . $e->getMessage());

      return [
        'success' => false,
        'message' => 'Lỗi khi tạo phiếu tạm tính: ' . $e->getMessage()
      ];
    }
  }

  /**
   * In hóa đơn chính thức (Official Invoice)
   * Chỉ in khi đơn hàng đã thanh toán
   */
  public function printInvoice(Order $order, ?string $deviceId = null)
  {
    try {
      // Kiểm tra điều kiện in hóa đơn
      if ($order->payment_status !== 'paid') {
        return [
          'success' => false,
          'message' => 'Chỉ có thể in hóa đơn khi đơn hàng đã thanh toán'
        ];
      }

      DB::beginTransaction();

      // Lấy template hóa đơn
      $template = $this->getTemplate($order->branch_id, 'invoice');

      // Render nội dung
      $content = $this->renderTemplate($template->content, $order);

      // Tạo print job với priority cao
      $printJob = $this->createPrintJob($order->branch_id, 'invoice', $content, [
        'order_id' => $order->id,
        'device_id' => $deviceId,
        'priority' => 'high'
      ]);

      // Cập nhật trạng thái đã in hóa đơn
      $order->update([
        'printed_bill' => true,
        'printed_bill_at' => now()
      ]);

      DB::commit();

      Log::info("Invoice queued for order {$order->order_code}", [
        'print_job_id' => $printJob->id,
        'device_id' => $deviceId
      ]);

      return [
        'success' => true,
        'print_job_id' => $printJob->id,
        'content' => $content,
        'message' => 'Hóa đơn đã được đưa vào hàng đợi in'
      ];
    } catch (\Exception $e) {
      DB::rollback();
      Log::error("Failed to queue invoice: " . $e->getMessage());

      return [
        'success' => false,
        'message' => 'Lỗi khi tạo hóa đơn: ' . $e->getMessage()
      ];
    }
  }

  /**
   * In tem phiếu (Labels)
   * In cho các sản phẩm cần dán tem (takeaway, delivery)
   */
  public function printLabels(Order $order, ?array $itemIds = null, ?string $deviceId = null)
  {
    try {
      DB::beginTransaction();

      // Lấy các item cần in tem
      $items = $this->getItemsForLabels($order, $itemIds);

      if ($items->isEmpty()) {
        return [
          'success' => false,
          'message' => 'Không có sản phẩm nào cần in tem'
        ];
      }

      $printJobs = [];
      $template = $this->getTemplate($order->branch_id, 'label');

      foreach ($items as $item) {
        // Render nội dung tem cho từng item
        $content = $this->renderLabelTemplate($template->content, $item);

        // Tạo print job
        $printJob = $this->createPrintJob($order->branch_id, 'label', $content, [
          'order_id' => $order->id,
          'order_item_id' => $item->id,
          'device_id' => $deviceId,
          'priority' => 'normal'
        ]);

        // Cập nhật trạng thái đã in tem
        $item->update([
          'printed_label' => true,
          'printed_label_at' => now()
        ]);

        $printJobs[] = $printJob->id;
      }

      DB::commit();

      Log::info("Labels queued for order {$order->order_code}", [
        'print_job_ids' => $printJobs,
        'items_count' => $items->count(),
        'device_id' => $deviceId
      ]);

      return [
        'success' => true,
        'print_job_ids' => $printJobs,
        'items_count' => $items->count(),
        'message' => "Đã tạo {$items->count()} tem phiếu vào hàng đợi in"
      ];
    } catch (\Exception $e) {
      DB::rollback();
      Log::error("Failed to queue labels: " . $e->getMessage());

      return [
        'success' => false,
        'message' => 'Lỗi khi tạo tem phiếu: ' . $e->getMessage()
      ];
    }
  }

  /**
   * In phiếu bếp (Kitchen Tickets)
   * In cho các sản phẩm cần chế biến
   */
  public function printKitchenTickets(Order $order, ?array $itemIds = null, ?string $deviceId = null)
  {
    try {
      DB::beginTransaction();

      // Lấy các item cần in phiếu bếp
      $items = $this->getItemsForKitchen($order, $itemIds);

      if ($items->isEmpty()) {
        return [
          'success' => false,
          'message' => 'Không có sản phẩm nào cần in phiếu bếp'
        ];
      }

      $printJobs = [];
      $template = $this->getTemplate($order->branch_id, 'kitchen');

      // Nhóm items theo kitchen station nếu có
      $groupedItems = $items->groupBy(function ($item) {
        return $item->product->kitchen_station ?? 'general';
      });

      foreach ($groupedItems as $station => $stationItems) {
        // Render phiếu bếp cho từng station
        $content = $this->renderKitchenTemplate($template->content, $order, $stationItems);

        // Tạo print job
        $printJob = $this->createPrintJob($order->branch_id, 'kitchen', $content, [
          'order_id' => $order->id,
          'kitchen_station' => $station,
          'device_id' => $deviceId,
          'priority' => 'high'
        ]);

        $printJobs[] = $printJob->id;
      }

      // Cập nhật trạng thái đã in phiếu bếp cho tất cả items
      $items->each(function ($item) {
        $item->update([
          'printed_kitchen' => true,
          'printed_kitchen_at' => now()
        ]);
      });

      DB::commit();

      Log::info("Kitchen tickets queued for order {$order->order_code}", [
        'print_job_ids' => $printJobs,
        'items_count' => $items->count(),
        'stations' => array_keys($groupedItems->toArray()),
        'device_id' => $deviceId
      ]);

      return [
        'success' => true,
        'print_job_ids' => $printJobs,
        'items_count' => $items->count(),
        'stations' => array_keys($groupedItems->toArray()),
        'message' => "Đã tạo phiếu bếp cho {$items->count()} sản phẩm"
      ];
    } catch (\Exception $e) {
      DB::rollback();
      Log::error("Failed to queue kitchen tickets: " . $e->getMessage());

      return [
        'success' => false,
        'message' => 'Lỗi khi tạo phiếu bếp: ' . $e->getMessage()
      ];
    }
  }

  /**
   * In tự động dựa trên cài đặt sản phẩm
   */
  public function autoPrint(Order $order, ?string $deviceId = null)
  {
    $results = [];

    // Auto print kitchen tickets cho sản phẩm cần chế biến
    $kitchenItems = $order->items()->where('print_kitchen', true)->get();
    if ($kitchenItems->isNotEmpty()) {
      $results['kitchen'] = $this->printKitchenTickets($order, $kitchenItems->pluck('id')->toArray(), $deviceId);
    }

    // Auto print labels cho sản phẩm cần tem
    $labelItems = $order->items()->where('print_label', true)->get();
    if ($labelItems->isNotEmpty()) {
      $results['labels'] = $this->printLabels($order, $labelItems->pluck('id')->toArray(), $deviceId);
    }

    return $results;
  }

  /**
   * Lấy template theo chi nhánh và loại
   */
  private function getTemplate(int $branchId, string $type)
  {
    $template = PrintTemplate::where('branch_id', $branchId)
      ->where('type', $type)
      ->where('is_active', true)
      ->first();

    if (!$template) {
      // Fallback to default template
      $template = PrintTemplate::where('branch_id', null)
        ->where('type', $type)
        ->where('is_default', true)
        ->first();
    }

    if (!$template) {
      throw new \Exception("Không tìm thấy template {$type} cho chi nhánh {$branchId}");
    }

    return $template;
  }

  /**
   * Render template với dữ liệu order
   */
  private function renderTemplate(string $template, Order $order)
  {
    $data = [
      '{Chi_Nhanh_Ban_Hang}' => $order->branch->name ?? 'Chi nhánh',
      '{Ngay_Thang_Nam}' => now()->format('d/m/Y H:i:s'),
      '{Ma_Don_Hang}' => $order->order_code,
      '{Ten_Phong_Ban}' => $order->table_name ?? '',
      '{Khach_Hang}' => $order->customer_name ?? 'Khách lẻ',
      '{Nhan_Vien_Ban_Hang}' => $order->staff->name ?? '',
      '{Ten_Hang_Hoa}' => $this->renderOrderItems($order),
      '{Tong_Tien_Hang}' => number_format($order->subtotal),
      '{Chiet_Khau_Hoa_Don}' => number_format($order->discount_amount),
      '{Tong_Cong}' => number_format($order->total_amount),
      '{Ghi_Chu}' => $order->notes ?? ''
    ];

    return str_replace(array_keys($data), array_values($data), $template);
  }

  /**
   * Render template cho tem phiếu
   */
  private function renderLabelTemplate(string $template, OrderItem $item)
  {
    $data = [
      '{Ten_Hang_Hoa}' => $item->product_name,
      '{So_Luong}' => $item->quantity,
      '{Don_Gia}' => number_format($item->price),
      '{Thanh_Tien}' => number_format($item->total_amount),
      '{Ghi_Chu_Hang_Hoa}' => $item->notes ?? '',
      '{Ma_Don_Hang}' => $item->order->order_code,
      '{Ngay_Thang_Nam}' => now()->format('d/m/Y H:i:s')
    ];

    return str_replace(array_keys($data), array_values($data), $template);
  }

  /**
   * Render template cho phiếu bếp
   */
  private function renderKitchenTemplate(string $template, Order $order, $items)
  {
    $itemsText = '';
    foreach ($items as $item) {
      $itemsText .= "{$item->product_name} x{$item->quantity}\n";
      if ($item->notes) {
        $itemsText .= "Ghi chú: {$item->notes}\n";
      }
    }

    $data = [
      '{Ma_Don_Hang}' => $order->order_code,
      '{Ten_Phong_Ban}' => $order->table_name ?? '',
      '{Ngay_Thang_Nam}' => now()->format('d/m/Y H:i:s'),
      '{Ten_Hang_Hoa}' => $itemsText,
      '{Ghi_Chu}' => $order->notes ?? ''
    ];

    return str_replace(array_keys($data), array_values($data), $template);
  }

  /**
   * Render danh sách sản phẩm trong order
   */
  private function renderOrderItems(Order $order)
  {
    $itemsHtml = '';
    foreach ($order->items as $item) {
      $itemsHtml .= "<tr>";
      $itemsHtml .= "<td>{$item->product_name}</td>";
      $itemsHtml .= "<td class='text-center'>{$item->quantity}</td>";
      $itemsHtml .= "<td class='text-right'>" . number_format($item->price) . "</td>";
      $itemsHtml .= "<td class='text-right'>" . number_format($item->total_amount) . "</td>";
      $itemsHtml .= "</tr>";
    }
    return $itemsHtml;
  }

  /**
   * Lấy items cần in tem
   */
  private function getItemsForLabels(Order $order, ?array $itemIds = null)
  {
    $query = $order->items()->where('print_label', true);

    if ($itemIds) {
      $query->whereIn('id', $itemIds);
    }

    return $query->where('printed_label', false)->with('product')->get();
  }

  /**
   * Lấy items cần in phiếu bếp
   */
  private function getItemsForKitchen(Order $order, ?array $itemIds = null)
  {
    $query = $order->items()->where('print_kitchen', true);

    if ($itemIds) {
      $query->whereIn('id', $itemIds);
    }

    return $query->where('printed_kitchen', false)->with('product')->get();
  }

  /**
   * Tạo print job
   */
  private function createPrintJob(int $branchId, string $type, string $content, array $metadata = [])
  {
    return PrintQueue::create([
      'branch_id' => $branchId,
      'type' => $type,
      'content' => $content,
      'metadata' => $metadata,
      'status' => 'pending',
      'device_id' => $metadata['device_id'] ?? null,
      'priority' => $metadata['priority'] ?? 'normal',
      'created_at' => now()
    ]);
  }
}
