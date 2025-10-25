# Print System Architecture

## Cấu trúc Database

### Table: `print_histories`

```sql
id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
branch_id           BIGINT UNSIGNED (FK to branches)
type                ENUM('invoice', 'provisional', 'kitchen', 'label', 'receipt', 'report', 'other')
status              ENUM('requested', 'printed', 'failed') DEFAULT 'requested'
requested_at        DATETIME (when print was requested)
printed_at          DATETIME NULL (when frontend finished printing)
metadata            JSON (print data, invoice info, items, etc.)
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**Indexes:**

- `(branch_id, status)` - Query by branch and status
- `(requested_at)` - Query by time

# Print System Architecture - Pull Model

## Tổng quan

Hệ thống in sử dụng **Pull Model** - Frontend nhận notification nhẹ qua WebSocket, sau đó gọi API để lấy print data khi cần.

### Ưu điểm Pull Model:
✅ WebSocket payload nhẹ (chỉ vài KB thay vì hàng trăm KB)  
✅ Không tạo print history vô ích - chỉ tạo khi frontend thực sự cần  
✅ Dễ retry - frontend có thể poll API nếu miss notification  
✅ Frontend control thứ tự in và timing  
✅ Giảm DB writes - không tạo print history nếu không có printer  
✅ Cache-friendly - có thể cache print data trong API  

```
Event: InvoiceCreated
    ↓
Listener: Broadcast notification NHẸ qua WebSocket
    {
      "event": "invoice.created",
      "invoice_id": 123,
      "invoice_code": "INV-001",
      "branch_id": 1
    }
    ↓
Frontend nhận notification
    ↓
Frontend call API: GET /api/print/invoices/{id}/print-data
    ↓
Service: PrintHistoryService (tạo print histories LÚC NÀY)
    ↓
Resources: Format metadata & return targets array
    ↓
Frontend nhận targets và in theo priority
    ↓
Frontend confirm: POST /api/print/histories/{id}/confirm
```

## Các thành phần

### 1. Listener: `CreateInvoicePrintJobs`

**Đường dẫn**: `app/Listeners/CreateInvoicePrintJobs.php`

**Trách nhiệm**: Broadcast notification nhẹ qua WebSocket

**KHÔNG tạo print history** - để API endpoint xử lý khi frontend request

```php
public function handle(InvoiceCreated $event): void
{
    broadcast(new PrintRequested([
        'event' => 'invoice.created',
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code,
        'branch_id' => $invoice->branch_id
    ], $invoice->branch_id));
}
```

### 2. API Endpoint: Invoice Print Data

**Route**: `GET /api/print/invoices/{invoice}/print-data`  
**Controller**: `App\Http\Print\Controllers\InvoiceController`  
**Method**: `getPrintData(int $invoiceId)`

**Trách nhiệm**: 
- Load invoice với relationships
- Tạo print histories (lazy creation)
- Return targets array với metadata

**Response**:
```json
{
  "success": true,
  "data": {
    "invoice_id": 123,
    "invoice_code": "INV-001",
    "branch_id": 1,
    "targets": [
      {
        "id": 456,
        "type": "invoice",
        "priority": 1,
        "printer_type": "receipt",
        "metadata": { ... }
      }
    ]
  }
}
```

### 3. Service: `PrintHistoryService`

**Đường dẫn**: `app/Services/PrintHistoryService.php`

**Methods chính**:

- `createPrintJobsForInvoice(Invoice $invoice): array` - Orchestrate tất cả print jobs, return mảng các print job info
- `createInvoicePrint(Invoice $invoice): PrintHistory` - Tạo và return PrintHistory model cho hóa đơn
- `createKitchenPrint(Invoice $invoice): ?PrintHistory` - Tạo và return PrintHistory model cho phiếu bếp (hoặc null)
- `createLabelPrints(Invoice $invoice): array` - Tạo và return array các PrintHistory models cho tem nhãn

**Logic**:

1. Load relationships cần thiết
2. Tạo 3 loại print history (invoice, kitchen, label)
3. Sử dụng Resources để format metadata
4. Broadcast qua WebSocket
5. **Return PrintHistory models** (không phải print_id)

### 3. Resources cho Metadata

**Namespace**: `App\Http\Print\Resources`  
**Đường dẫn**: `app/Http/Print/Resources/`

#### `InvoicePrintResource`

**File**: `InvoicePrintResource.php`

Format metadata đầy đủ cho hóa đơn:

- Thông tin staff
- Thông tin customer (membership, points)
- Thông tin invoice đầy đủ (prices, tax, payment, etc.)
- Danh sách items với toppings

#### `KitchenPrintResource`

**File**: `KitchenPrintResource.php`

Format metadata cho phiếu bếp:

- Thông tin staff
- Thông tin customer
- **Chỉ items có `print_kitchen = true`**
- Priority = 'high'

#### `LabelPrintResource`

**File**: `LabelPrintResource.php`

Format metadata cho tem nhãn (mỗi item):

- Invoice code, order code, table name
- Thông tin product cụ thể
- Toppings text (formatted)

#### `InvoiceItemPrintResource`

**File**: `InvoiceItemPrintResource.php`

Format item trong danh sách:

- Product info (id, name, quantity, prices)
- `toppings_text` - Sử dụng accessor từ model

## Flow hoạt động

```
1. Invoice được tạo → InvoiceCreated event fired

2. CreateInvoicePrintJobs listener nhận event
   → Gọi PrintHistoryService::createPrintJobsForInvoice()

3. PrintHistoryService load relationships và tạo print jobs:

   a) Invoice Print (luôn tạo)
      → InvoicePrintResource format metadata
      → Tạo PrintHistory record
      → Broadcast qua WebSocket
      → Return PrintHistory model

   b) Kitchen Print (conditional - nếu có món print_kitchen=true)
      → KitchenPrintResource format metadata
      → Tạo PrintHistory record
      → Broadcast qua WebSocket
      → Return PrintHistory model hoặc null

   c) Label Prints (mỗi item có print_label=true)
      → LabelPrintResource format metadata cho từng item
      → Tạo PrintHistory record cho từng item
      → Broadcast qua WebSocket
      → Return array of PrintHistory models

4. WebSocket broadcast đến channel: print-branch-{branch_id}
   → Payload chứa type và metadata

5. Client nhận và in theo type (invoice/kitchen/label)
   → Sau khi in xong, gọi API để confirm print
   → Update print_history.status = 'printed', printed_at = now()
```

## Lợi ích của kiến trúc mới

✅ **Separation of Concerns**:

- Listener chỉ handle event
- Service xử lý business logic
- Resources format data

✅ **Maintainability**:

- Code ngắn gọn, dễ đọc
- Dễ test từng phần riêng biệt
- Dễ thay đổi format metadata

✅ **Reusability**:

- Resources có thể tái sử dụng cho API khác
- Service methods có thể gọi độc lập

✅ **Consistency**:

- Metadata format nhất quán
- Sử dụng Laravel Resources pattern chuẩn

## Testing

```php
// Test service trực tiếp
$service = app(PrintHistoryService::class);
$printJobs = $service->createPrintJobsForInvoice($invoice);

// Kiểm tra print histories được tạo
$this->assertDatabaseHas('print_histories', [
    'branch_id' => $invoice->branch_id,
    'type' => 'invoice',
    'status' => 'requested'
]);
```

## Metadata Format Examples

Xem file `PRINT_METADATA_SAMPLES.md` để biết chi tiết format của từng loại metadata.
