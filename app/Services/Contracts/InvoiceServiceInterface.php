<?php

namespace App\Services\Contracts;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceServiceInterface
{
  /**
   * Tạo hóa đơn từ đơn hàng.
   *
   * @param int $orderId ID của đơn hàng.
   * @param float $paidAmount Số tiền đã thanh toán ban đầu (mặc định là 0).
   * @return Invoice Hóa đơn được tạo từ đơn hàng.
   * @throws \Exception Nếu đơn hàng chưa hoàn tất.
   */
  public function createInvoiceFromOrder(int $orderId, float $paidAmount = 0): Invoice;

  /**
   * Cập nhật phương thức thanh toán của hóa đơn.
   *
   * @param int $invoiceId ID của hóa đơn cần cập nhật.
   * @param string $method Phương thức thanh toán mới.
   * @return Invoice Hóa đơn sau khi cập nhật.
   * @throws \Exception Nếu hóa đơn không tồn tại.
   */
  public function updatePaymentMethod(int $invoiceId, string $method): Invoice;

  /**
   * Cập nhật trạng thái thanh toán của hóa đơn.
   *
   * @param int $invoiceId ID của hóa đơn.
   * @param string $status Trạng thái thanh toán mới (unpaid, partial, paid, refunded).
   * @return Invoice Hóa đơn sau khi cập nhật trạng thái thanh toán.
   * @throws \Exception Nếu trạng thái thanh toán không hợp lệ.
   */
  public function updatePaymentStatus(int $invoiceId, string $status): Invoice;

  /**
   * Tìm kiếm hóa đơn theo mã.
   *
   * @param string $code Mã hóa đơn cần tìm kiếm.
   * @return Invoice|null Trả về hóa đơn nếu tìm thấy, nếu không trả về null.
   */
  public function findInvoiceByCode(string $code): ?Invoice;

  /**
   * Lấy danh sách hóa đơn (có phân trang).
   *
   * @param int $perPage Số lượng hóa đơn hiển thị trên mỗi trang (mặc định là 10).
   * @return LengthAwarePaginator Danh sách hóa đơn có phân trang.
   */
  public function getInvoices(int $perPage = 10): LengthAwarePaginator;

  /**
   * Kiểm tra xem hóa đơn có thể được hoàn tiền hay không.
   *
   * @param Invoice $invoice Hóa đơn cần kiểm tra.
   * @return bool Trả về true nếu có thể hoàn tiền, ngược lại là false.
   * @throws \Exception Nếu hóa đơn không hợp lệ.
   */
  public function canBeRefunded(Invoice $invoice): bool;
}
