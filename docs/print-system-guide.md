# 🖨️ Hệ thống In cho F&B - Hướng dẫn Triển khai

## Tổng quan

Hệ thống in được thiết kế để xử lý 4 loại in chính trong nhà hàng/quán cà phê:

1. **In tạm tính** - Phiếu xem tổng tiền trước khi thanh toán
2. **In hóa đơn** - Hóa đơn chính thức sau thanh toán
3. **In tem phiếu** - Tem dán lên sản phẩm takeaway/delivery
4. **In phiếu bếp** - Phiếu cho nhà bếp chế biến món

## Kiến trúc Hệ thống

### 1. Components Chính

- **PrintService**: Xử lý logic tạo nội dung in
- **PrintQueue Model**: Quản lý hàng đợi in
- **PrintController**: API endpoints cho các chức năng in
- **ProcessPrintQueue Command**: Command xử lý hàng đợi in

### 2. Database Schema

#### Bảng `print_queue`

```sql
- id: Primary key
- branch_id: Chi nhánh
- type: Loại in (invoice, provisional, label, kitchen)
- content: Nội dung HTML cần in
- metadata: Thông tin bổ sung (order_id, device_id, etc.)
- status: Trạng thái (pending, processing, processed, failed)
- device_id: ID thiết bị in
- priority: Độ ưu tiên (low, normal, high)
- processed_at: Thời gian xử lý
- error_message: Lỗi nếu có
- retry_count: Số lần retry
```

#### Bảng `print_templates` (đã có)

- Chứa template HTML cho từng loại in
- Hỗ trợ template riêng cho từng chi nhánh

## API Endpoints

### 1. In Tạm tính

```http
POST /api/pos/print/provisional
{
  "order_id": 123,
  "device_id": "printer_001" // optional
}
```

### 2. In Hóa đơn

```http
POST /api/pos/print/invoice
{
  "order_id": 123,
  "device_id": "printer_001" // optional
}
```

**Lưu ý**: Chỉ in được khi order đã thanh toán (`payment_status = 'paid'`)

### 3. In Tem phiếu

```http
POST /api/pos/print/labels
{
  "order_id": 123,
  "item_ids": [1, 2, 3], // optional, nếu không có sẽ in tất cả items có print_label = true
  "device_id": "label_printer_001" // optional
}
```

### 4. In Phiếu bếp

```http
POST /api/pos/print/kitchen
{
  "order_id": 123,
  "item_ids": [1, 2, 3], // optional, nếu không có sẽ in tất cả items có print_kitchen = true
  "device_id": "kitchen_printer_001" // optional
}
```

### 5. In Tự động

```http
POST /api/pos/print/auto
{
  "order_id": 123,
  "device_id": "printer_001" // optional
}
```

Tự động in tem phiếu và phiếu bếp dựa trên cài đặt sản phẩm.

### 6. Lấy Hàng đợi In

```http
GET /api/pos/print/queue?device_id=printer_001&limit=10
```

### 7. Đánh dấu Job Hoàn thành

```http
POST /api/pos/print/queue/{job_id}/processed
```

### 8. Đánh dấu Job Thất bại

```http
POST /api/pos/print/queue/{job_id}/failed
{
  "error_message": "Printer offline"
}
```

### 9. Retry Job

```http
POST /api/pos/print/queue/{job_id}/retry
```

### 10. Trạng thái In của Order

```http
GET /api/pos/print/order/{order_id}/status
```

### 11. Preview Nội dung In

```http
GET /api/pos/print/preview?order_id=123&type=invoice
```

## Cách Sử dụng

### 1. Tích hợp với POS

```javascript
// Khi tạo order mới, tự động in phiếu bếp và tem
const response = await fetch('/api/pos/print/auto', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        order_id: orderId,
        device_id: getCurrentDeviceId(),
    }),
});
```

### 2. In tạm tính khi khách yêu cầu

```javascript
const response = await fetch('/api/pos/print/provisional', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        order_id: orderId,
        device_id: 'receipt_printer',
    }),
});
```

### 3. In hóa đơn sau thanh toán

```javascript
// Sau khi thanh toán thành công
const response = await fetch('/api/pos/print/invoice', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        order_id: orderId,
        device_id: 'receipt_printer',
    }),
});
```

### 4. Polling hàng đợi in (cho app client)

```javascript
// Client app polling mỗi 5 giây
setInterval(async () => {
    const response = await fetch(`/api/pos/print/queue?device_id=${deviceId}&limit=5`);
    const data = await response.json();

    if (data.jobs.length > 0) {
        for (const job of data.jobs) {
            try {
                await printJob(job);
                await markJobProcessed(job.id);
            } catch (error) {
                await markJobFailed(job.id, error.message);
            }
        }
    }
}, 5000);
```

## Logic Điều kiện In

### 1. Tem phiếu (Labels)

- Chỉ in cho sản phẩm có `print_label = true` trong `order_items`
- Thường dùng cho takeaway, delivery
- Mỗi sản phẩm một tem riêng

### 2. Phiếu bếp (Kitchen Tickets)

- Chỉ in cho sản phẩm có `print_kitchen = true` trong `order_items`
- Nhóm theo `kitchen_station` nếu có
- Chứa thông tin chế biến, ghi chú đặc biệt

### 3. Tự động thiết lập

Khi tạo order item, tự động set:

```php
// Trong OrderService hoặc khi tạo order item
$item->print_label = in_array($product->type, ['goods', 'processed', 'combo']);
$item->print_kitchen = in_array($product->type, ['processed', 'combo']);
```

## Triển khai Multi-device

### 1. Cấu hình Device ID

Mỗi thiết bị/máy tính cần có device_id duy nhất:

```javascript
// Trong app POS
const DEVICE_ID = localStorage.getItem('device_id') || generateDeviceId();
```

### 2. Background Service

Chạy background service để xử lý hàng đợi:

```bash
# Xử lý liên tục
php artisan print:process-queue --device=printer_001

# Hoặc dùng cron job
* * * * * php artisan print:process-queue --limit=50
```

### 3. Failover Strategy

- Nếu device_id cụ thể không hoạt động, job sẽ fallback về device_id = null
- Device khác có thể pickup job không có device_id cụ thể

## Template Variables

### Variables chung

- `{Chi_Nhanh_Ban_Hang}` - Tên chi nhánh
- `{Ngay_Thang_Nam}` - Ngày giờ hiện tại
- `{Ma_Don_Hang}` - Mã đơn hàng
- `{Ten_Phong_Ban}` - Tên bàn
- `{Khach_Hang}` - Tên khách hàng
- `{Nhan_Vien_Ban_Hang}` - Tên nhân viên
- `{Ghi_Chu}` - Ghi chú đơn hàng

### Variables hóa đơn/tạm tính

- `{Ten_Hang_Hoa}` - Danh sách sản phẩm (HTML table rows)
- `{Tong_Tien_Hang}` - Tổng tiền hàng
- `{Chiet_Khau_Hoa_Don}` - Chiết khấu
- `{Tong_Cong}` - Tổng cộng

### Variables tem phiếu/phiếu bếp

- `{Ten_Hang_Hoa}` - Tên sản phẩm
- `{So_Luong}` - Số lượng
- `{Don_Gia}` - Đơn giá
- `{Thanh_Tien}` - Thành tiền
- `{Ghi_Chu_Hang_Hoa}` - Ghi chú sản phẩm

## Monitoring & Logging

### 1. Logs

Hệ thống ghi log chi tiết:

- Print job creation
- Processing success/failure
- Device status
- Error tracking

### 2. Metrics cần theo dõi

- Print queue length
- Success/failure rates
- Average processing time
- Device availability

### 3. Health check

```bash
# Kiểm tra hàng đợi
php artisan tinker
>>> PrintQueue::pending()->count()

# Kiểm tra jobs thất bại
>>> PrintQueue::where('status', 'failed')->count()
```

## Troubleshooting

### 1. Jobs bị kẹt

```bash
# Retry tất cả failed jobs
php artisan tinker
>>> PrintQueue::where('status', 'failed')->where('retry_count', '<', 3)->update(['status' => 'pending'])
```

### 2. Clear old jobs

```bash
# Xóa jobs cũ hơn 7 ngày
php artisan tinker
>>> PrintQueue::where('created_at', '<', now()->subDays(7))->delete()
```

### 3. Reset device

```bash
# Reset jobs cho device cụ thể
php artisan tinker
>>> PrintQueue::where('device_id', 'printer_001')->where('status', 'processing')->update(['status' => 'pending'])
```

## Tính năng Nâng cao

### 1. Template động

- Có thể tạo template riêng cho từng chi nhánh
- Support multiple languages
- Custom variables based on order type

### 2. Print scheduling

- Delay printing based on order time
- Batch printing for efficiency
- Priority-based queue processing

### 3. Integration options

- ESC/POS printers
- Network printers
- PDF generation
- Email delivery for remote locations
