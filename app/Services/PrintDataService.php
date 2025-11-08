<?php

namespace App\Services;

use App\Http\Print\Resources\ProvisionalResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\KitchenTicket;
use App\Models\PrintLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PrintDataService
{

  /**
   * Lấy data in tạm tính cho order
   * @param int $id ID của đơn hàng
   * @return array<mixed> Dữ liệu in tạm tính
   */
  public function getProvisionalData(int $id): array
  {
    $entry = Order::with([
      'customer',
      'items',
      'items.toppings'
    ])->findOrFail($id);
    // Trả về data tạm tính trực tiếp
    return [
      'type' => 'provisional',
      'metadata' =>  [
        'Ma_Dat_Hang'  =>  $entry->entry_code,
        'Nhan_Vien_Ban_Hang'  =>  $entry->staff->fullname,
        'Ngay_Thang_Nam'  =>  $entry->created_at->format('d/m/Y H:i:s'),
        'Ten_Phong_Ban'  =>  $entry->table_name,
        'Ten_Khach_Hang'  =>  $entry->customer_name,
        'Ma_Khach_Hang'  =>  $entry->loyalty_card_number,
        'Diem_Tich_Luy'  =>  $entry->customer ? $entry->customer->reward_points : 0,
        'Diem_Thuong'  =>  $entry->customer ? $entry->customer->loyalty_points : 0,
        'Kenh_Ban_Hang'  =>  $entry->sales_channel,
        'Phuong_Thuc_Thanh_Toan'  =>  $entry->payment_method,
        'Tong_Tien_Hang'  =>  $entry->total_price,
        'Ma_Giam_Gia'  =>  $entry->voucher_code,
        'Tien_Giam_Gia'  =>  $entry->voucher_discount,
        'Diem_Thuong_Su_Dung'  =>  $entry->reward_points_used,
        'Tien_Diem_Thuong_Su_Dung'  =>  $entry->reward_discount,
        'Tong_Thanh_Toan'  =>  $entry->total_price,
        'Ghi_Chu' =>  $entry->note,
        'items' => $entry->items->map(function ($item) {
          return [
            'Ten_San_Pham'  =>  $item->product_name,
            'Topping' =>  $item->toppings_text,
            'Ghi_Chu' =>  $item->note,
            'So_Luong'  =>  $item->quantity,
            'Don_Gia' =>  $item->unit_price,
            'Thanh_Tien'  =>  $item->total_price
          ];
        })->toArray()
      ]
    ];
  }

  /**
   * Lấy data in cho invoice
   * @param int $id ID của hóa đơn
   * @return array<mixed> Dữ liệu in hóa đơn
   */
  public function getInvoiceData(int $id): array
  {
    $entry = Invoice::with([
      'staff',
      'customer',
      'items',
      'items.toppings'
    ])->findOrFail($id);
    return [
      'type' => 'invoice',
      'metadata' =>  [
        'Ma_Don_Hang'  =>  $entry->code,
        'Ma_Dat_Hang'  =>  $entry->order_code,
        'Nhan_Vien_Ban_Hang'  =>  $entry->staff->fullname,
        'Ngay_Thang_Nam'  =>  $entry->created_at->format('d/m/Y H:i:s'),
        'Ten_Phong_Ban'  =>  $entry->table_name,
        'Ten_Khach_Hang'  =>  $entry->customer_name,
        'Ma_Khach_Hang'  =>  $entry->loyalty_card_number,
        'Diem_Tich_Luy'  =>  $entry->customer ? $entry->customer->reward_points : 0,
        'Diem_Thuong'  =>  $entry->customer ? $entry->customer->loyalty_points : 0,
        'Kenh_Ban_Hang'  =>  $entry->sales_channel,
        'Phuong_Thuc_Thanh_Toan'  =>  $entry->payment_method,
        'Tong_Tien_Hang'  =>  $entry->total_price,
        'Ma_Giam_Gia'  =>  $entry->voucher_code,
        'Tien_Giam_Gia'  =>  $entry->voucher_discount,
        'Diem_Thuong_Su_Dung'  =>  $entry->reward_points_used,
        'Tien_Diem_Thuong_Su_Dung'  =>  $entry->reward_discount,
        'Tong_Thanh_Toan'  =>  $entry->total_price,
        'Ghi_Chu' =>  $entry->note,
        'items' => $entry->items->map(function ($item) {
          return [
            'Ten_San_Pham'  =>  $item->product_name,
            'Topping' =>  $item->toppings_text,
            'Ghi_Chu' =>  $item->note,
            'So_Luong'  =>  $item->quantity,
            'Don_Gia' =>  $item->unit_price,
            'Thanh_Tien'  =>  $item->total_price
          ];
        })->toArray()
      ]
    ];
  }
  /**
   * Lấy data in cho phiếu bếp từ order
   * @param int $id ID của đơn hàng
   * @return array<mixed> Dữ liệu in phiếu bếp
   * Cập nhật print count cho các mục in bếp
   */
  public function getKitchentDataFromOrder(int $id): array
  {
    $entry = Order::findOrFail($id);
    if ($entry->invoice_id) {
      return $this->getKitchentDataFromInvoice($entry->invoice_id);
    }

    return $this->getKitchenData($entry);
  }

  /**
   * Lấy data in cho phiếu bếp từ hoá đơn
   * @param int $id ID của đơn hàng
   * @return array<mixed> Dữ liệu in phiếu bếp
   * Cập nhật print count cho các mục in bếp
   */
  public function getKitchentDataFromInvoice(int $id): array
  {
    $entry = Invoice::findOrFail($id);
    return $this->getKitchenData($entry);
  }

  /**
   * Lấy data in cho phiếu bếp từ hoá đơn
   * @param int $id ID của đơn hàng
   * @return array<mixed> Dữ liệu in phiếu bếp
   * Cập nhật print count cho các mục in bếp
   */
  public function getLabelsDataFromInvoice(int $id): array
  {
    return DB::transaction(function () use ($id) {
      $entry = Invoice::findOrFail($id);
      $entry->load([
        'labelItems',
        'labelItems.toppings'
      ]);
      $entry->labelItems()->update(['printed_label' => DB::raw('printed_label + 1'), 'printed_label_at' => now()]);

      return [
        'type' => 'label',
        'metadata' =>  [
          'labels' => $entry->labelItems->map(function ($item) {
            return  $this->getProductData($item);
          })->toArray()
        ]
      ];
    });
  }

  private function getKitchenData(Invoice | Order $entry): array
  {
    return DB::transaction(function () use ($entry) {
      $entry->load([
        'staff',
        'kitchenItems',
        'kitchenItems.toppings'
      ]);
      if ($entry->kitchenItems->isEmpty()) {
        return [];
      }
      $entry->kitchenItems()->update(['printed_kitchen' => DB::raw('printed_kitchen + 1'), 'printed_kitchen_at' => now()]);
      return [
        'type' => 'kitchen',
        'metadata' =>  [
          'id' => $entry->id,
          'Ma_Dat_Hang'  =>  $entry->order_code,
          'Nhan_Vien_Ban_Hang'  =>  $entry->staff->fullname,
          'Ngay_Thang_Nam'  =>  $entry->created_at->format('d/m/Y H:i:s'),
          'Ten_Phong_Ban'  =>  $entry->table_name,
          'Ten_Khach_Hang'  =>  $entry->customer_name,
          'Ma_Khach_Hang'  =>  $entry->loyalty_card_number,
          'Ghi_Chu' =>  $entry->note,
          'items' => $entry->kitchenItems->map(function ($item) {
            return  $this->getProductData($item);
          })->toArray()
        ]
      ];
    });
  }

  private function getProductData(Model $item): array
  {
    return [
      'Ten_San_Pham'  =>  $item->product_name,
      'Topping' =>  $item->toppings_text,
      'Ghi_Chu' =>  $item->note,
      'So_Luong'  =>  $item->quantity,
      'Don_Gia' =>  $item->unit_price,
      'Thanh_Tien'  =>  $item->total_price
    ];
  }

  /**
   * Lấy data in tất cả cho invoice (hóa đơn + tem + phiếu bếp)
   */
  public function getInvoiceAllData(int $invoiceId): array
  {
    $invoice = Invoice::with([
      'staff',
      'customer',
      'items.product',
      'items.toppings'
    ])->findOrFail($invoiceId);

    // Đánh dấu đã được yêu cầu in (nếu chưa)
    if (!$invoice->isPrintRequested()) {
      $invoice->markAsPrintRequested();
    }

    // Cập nhật print count cho invoice
    $invoice->markAsPrinted();

    // Tạo kitchen ticket nếu chưa có
    $kitchenTicket = $this->createOrGetKitchenTicket($invoice);

    // Tạo print labels cho từng item
    $printLabels = $this->createOrGetPrintLabels($invoice);

    return [
      'invoice' => [
        'id' => $invoice->id,
        'code' => $invoice->code,
        'type' => 'invoice',
        'metadata' => $invoice->toArray()
      ],
      'kitchen' => [
        'id' => $kitchenTicket->id,
        'type' => 'kitchen',
        'metadata' => $kitchenTicket->metadata
      ],
      'labels' => collect($printLabels)->map(fn($label) => [
        'id' => $label->id,
        'type' => 'label',
        'metadata' => $label->toArray()
      ])->toArray()
    ];
  }

  /**
   * Lấy data cho tem phiếu specific
   */
  public function getLabelData(int $labelId): array
  {
    $printLabel = PrintLabel::with('invoiceItem.product')->findOrFail($labelId);

    // Cập nhật print count
    $printLabel->markAsPrinted();

    return [
      'type' => 'label',
      'metadata' => [
        'id' => $printLabel->id,
        'product_code' => $printLabel->product_code,
        'product_name' => $printLabel->invoiceItem->product_name,
        'toppings_text' => $printLabel->toppings_text,
        'quantity' => $printLabel->invoiceItem->quantity,
        'print_count' => $printLabel->print_count,
        'last_printed_at' => $printLabel->last_printed_at
      ]
    ];
  }

  /**
   * Lấy data cho phiếu bếp specific  
   */
  /*  public function getKitchenData(int $kitchenId): array
  {
    // $order->items()->update(['printed_kitchen' => DB::raw('print_kitchen + 1'), 'printed_kitchen_at' => now()]);

    $kitchenTicket = KitchenTicket::with('invoice')->findOrFail($kitchenId);

    // Cập nhật print count
    $kitchenTicket->markAsPrinted();

    return [
      'type' => 'kitchen',
      'metadata' => [
        'id' => $kitchenTicket->id,
        'invoice_code' => $kitchenTicket->metadata['invoice_code'] ?? '',
        'table_name' => $kitchenTicket->metadata['table_name'] ?? '',
        'items' => $kitchenTicket->metadata['items'] ?? [],
        'print_count' => $kitchenTicket->print_count,
        'last_printed_at' => $kitchenTicket->last_printed_at
      ]
    ];
  } */

  /**
   * Tạo hoặc lấy kitchen ticket cho invoice
   */
  protected function createOrGetKitchenTicket(Invoice $invoice): KitchenTicket
  {
    return KitchenTicket::firstOrCreate(
      ['invoice_id' => $invoice->id],
      [
        'branch_id' => $invoice->branch_id,
        'metadata' => [
          'invoice_code' => $invoice->code,
          'table_name' => $invoice->table?->name,
          'items' => $invoice->items->map(function ($item) {
            return [
              'product_name' => $item->product_name,
              'quantity' => $item->quantity,
              'toppings_text' => $item->toppings->map(fn($t) => "{$t->topping_name} x{$t->quantity}")->join(', ')
            ];
          })->toArray()
        ]
      ]
    );
  }

  /**
   * Tạo hoặc lấy print labels cho invoice
   */
  protected function createOrGetPrintLabels(Invoice $invoice): array
  {
    $printLabels = [];

    foreach ($invoice->items as $item) {
      $printLabel = PrintLabel::firstOrCreate(
        ['invoice_item_id' => $item->id],
        [
          'branch_id' => $invoice->branch_id,
          'product_code' => $item->product->code ?? $item->product_name,
          'toppings_text' => $item->toppings->map(fn($t) => "{$t->topping_name} x{$t->quantity}")->join(', ')
        ]
      );
      $printLabels[] = $printLabel;
    }

    return $printLabels;
  }

  /**
   * Lấy thông tin in cho invoice (dùng trong InvoiceController)
   */
  public function getInvoicePrintData(int $invoiceId): array
  {
    return $this->getInvoiceAllData($invoiceId);
  }

  /**
   * Lấy danh sách hóa đơn đã được yêu cầu in cho chi nhánh
   */
  public function getPrintRequestedInvoices(int $branchId, int $limit = 50): array
  {
    $invoices = Invoice::with(['customer', 'staff'])
      ->where('branch_id', $branchId)
      ->printRequested()
      ->orderBy('print_requested_at', 'desc')
      ->limit($limit)
      ->get();

    return $invoices->map(function ($invoice) {
      return [
        'id' => $invoice->id,
        'code' => $invoice->code,
        'customer_name' => $invoice->customer_name ?? 'Khách lẻ',
        'staff_name' => $invoice->staff?->name ?? 'N/A',
        'total_price' => $invoice->total_price,
        'print_requested_at' => $invoice->print_requested_at?->format('d/m/Y H:i:s'),
        'print_count' => $invoice->print_count,
        'last_printed_at' => $invoice->last_printed_at?->format('d/m/Y H:i:s'),
        'table_name' => $invoice->table_name,
        'payment_method' => $invoice->payment_method,
        'invoice_status' => $invoice->invoice_status->value ?? $invoice->invoice_status,
      ];
    })->toArray();
  }
}
