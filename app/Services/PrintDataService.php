<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Http\Print\Resources\ProvisionalResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\KitchenTicket;
use App\Models\OrderItem;
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
      'staff',
      'customer',
      'items',
      'items.toppings'
    ])->findOrFail($id);
    // Trả về data tạm tính trực tiếp
    return [
      'type' => 'provisional',
      'metadata' => $this->getEntryData($entry)
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

    return [
      'invoice' => [
        'id' => $invoice->id,
        'code' => $invoice->code,
        'type' => 'invoice',
        'metadata' => $this->getEntryData($invoice)
      ],
      'kitchen' => $this->getKitchenData($invoice),
      'labels' =>  $this->getLabelsDataFromInvoice($invoice->id),
    ];
  }


  /**
   * Lấy data in cho invoice
   * @param int $id ID của hóa đơn
   * @return array<mixed> Dữ liệu in hóa đơn
   */
  public function getInvoiceData(int $id): array
  {
    $invoice = Invoice::with([
      'staff',
      'customer',
      'items',
      'items.toppings'
    ])->findOrFail($id);

    // Đánh dấu đã được yêu cầu in (nếu chưa)
    if (!$invoice->isPrintRequested()) {
      $invoice->markAsPrintRequested();
    }
    // Cập nhật print count cho invoice
    $invoice->markAsPrinted();

    return [
      'type' => 'invoice',
      'metadata' =>  $this->getEntryData($invoice)
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
      $invoice = Invoice::where('invoice_status', InvoiceStatus::COMPLETED)->whereId($id)->firstOrFail();
      $invoice->load([
        'labelItems',
        'labelItems.toppings'
      ]);
      $invoice->labelItems()->update(['printed_label' => DB::raw('printed_label + 1'), 'printed_label_at' => now()]);
      return $invoice->labelItems->map(function ($item) {
        return [
          'type' => 'label',
          'metadata' =>
          $this->getProductData($item)
        ];
      })->toArray();
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

  private function getEntryData(Order | Invoice $entry): array
  {
    $data = [
      'Ma_Dat_Hang'  =>  $entry->order_code,
      'Nhan_Vien_Ban_Hang'  =>  $entry->staff->fullname,
      'Ngay_Thang_Nam'  =>  $entry->created_at->format('d/m/Y H:i:s'),
      'Ten_Phong_Ban'  =>  $entry->table_name,
      'Ten_Khach_Hang'  =>  $entry->customer_name,
      'Ma_Khach_Hang'  =>  $entry->loyalty_card_number,
      'Diem_Tich_Luy'  =>  $entry->customer ? number_format($entry->customer->loyalty_points, 0, ',', '.') : 0,
      'Diem_Thuong'  =>  $entry->customer ? number_format($entry->customer->reward_points, 0, ',', '.') : 0,
      'Kenh_Ban_Hang'  =>  $entry->sales_channel,
      'Phuong_Thuc_Thanh_Toan'  =>  $entry->payment_method,
      'Tong_Tien_Hang'  =>  number_format($entry->subtotal_price, 0, ',', '.') . "đ",
      'Ma_Giam_Gia'  =>  $entry->voucher_code || "",
      'Tien_Giam_Gia'  =>  number_format($entry->voucher_discount, 0, ',', '.') . "đ",
      'Diem_Doi_Thuong'  =>  number_format($entry->reward_points_used, 0, ',', '.') . "đ",
      'Tien_Diem_Doi_Thuong'  =>  number_format($entry->reward_discount, 0, ',', '.') . "đ",
      'Tong_Thanh_Toan'  =>  number_format($entry->total_price, 0, ',', '.') . "đ",
      'Ghi_Chu' =>  $entry->note,
      'items' => $entry->items->map(function ($item) {
        return $this->getProductData($item);
      })->toArray()
    ];

    if ($entry instanceof Invoice) {
      $data['Ma_Don_Hang'] = $entry->code;
    }

    return $data;
  }

  private function getProductData(InvoiceItem | OrderItem $item): array
  {
    return [
      'Ten_San_Pham'  =>  $item->product_name,
      'Topping' =>  $item->toppings_text,
      'Ghi_Chu' =>  $item->note,
      'So_Luong'  =>  $item->quantity,
      'Don_Gia' =>  number_format($item->unit_price, 0, ',', '.') . 'đ',
      'Thanh_Tien'  =>  number_format($item->total_price, 0, ',', '.') . 'đ'
    ];
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
