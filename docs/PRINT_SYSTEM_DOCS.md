# 🖨️ Tài liệu Hệ thống In - Karinox F&B

## Tổng quan

Hệ thống in sử dụng **Pull Model** với WebSocket notifications và REST API để quản lý việc in tài liệu tại các chi nhánh.

### Kiến trúc

```
[POS/Admin] → [WebSocket Event] → [Print Client] → [API Call] → [Print Data] → [Printer]
```

## 🔄 Workflow

### 1. Kết nối ban đầu

```http
POST /api/print/branchs/{connection_code}/connect
```

**Response:**

```json
{
    "success": true,
    "message": "Kết nối thành công",
    "data": {
        "branch": {
            "id": 1,
            "name": "Chi nhánh trung tâm",
            "connection_code": "BR001"
        },
        "websocket_config": {
            "channel": "print-branch-1",
            "event": "print.requested"
        }
    }
}
```

### 2. Lắng nghe WebSocket Events

**Channel:** `print-branch-{branch_id}`  
**Event:** `print.requested`

**Payload nhận được:**

```json
{
    "type": "invoice", // invoice | provisional | kitchen | label
    "id": 123 // ID của entity cần in
}
```

### 3. Lấy dữ liệu in

```http
GET /api/print/data/{type}/{id}
```

**Các loại type hỗ trợ:**

| Type          | Mô tả                         | ID tham chiếu    |
| ------------- | ----------------------------- | ---------------- |
| `provisional` | In tạm tính                   | Order ID         |
| `invoice-all` | In hóa đơn + kitchen + labels | Invoice ID       |
| `invoice`     | Chỉ in hóa đơn                | Invoice ID       |
| `kitchen`     | Chỉ in phiếu bếp              | KitchenTicket ID |
| `label`       | Chỉ in tem phiếu              | PrintLabel ID    |

## 📋 Chi tiết API

### 🍽️ In tạm tính (Provisional)

```http
GET /api/print/data/provisional/{order_id}
```

**Response:**

```json
{
    "success": true,
    "message": "Lấy dữ liệu in tạm tính thành công",
    "data": {
        "type": "provisional",
        "metadata": {
            "order_id": 1,
            "order_code": "ORD-001",
            "table_name": "Bàn 01",
            "customer_name": "Nguyễn Văn A",
            "subtotal_price": 100000,
            "discount_amount": 10000,
            "total_price": 90000,
            "note": "Không đá",
            "created_at": "26/10/2025 14:30:00",
            "items": [
                {
                    "product_name": "Cà phê đen",
                    "quantity": 2,
                    "unit_price": 25000,
                    "total_price": 50000,
                    "toppings_text": "Đường, Sữa x2"
                }
            ]
        }
    }
}
```

### 🧾 In hóa đơn đầy đủ (Invoice All)

```http
GET /api/print/data/invoice-all/{invoice_id}
```

**Response:**

```json
{
    "success": true,
    "message": "Lấy dữ liệu in hóa đơn thành công",
    "data": {
        "invoice": {
            "id": 1,
            "code": "INV-001",
            "type": "invoice",
            "metadata": {
                "invoice_code": "INV-001",
                "customer_name": "Nguyễn Văn A",
                "staff_name": "Thu ngân A",
                "total_price": 90000,
                "payment_method": "cash",
                "print_count": 1,
                "last_printed_at": "2025-10-26T14:30:00.000000Z"
            }
        },
        "kitchen_ticket": {
            "id": 1,
            "type": "kitchen",
            "metadata": {
                "invoice_code": "INV-001",
                "table_name": "Bàn 01",
                "items": [
                    {
                        "product_name": "Cà phê đen",
                        "quantity": 2,
                        "toppings_text": "Đường, Sữa x2"
                    }
                ]
            }
        },
        "print_labels": [
            {
                "id": 1,
                "type": "label",
                "metadata": {
                    "product_code": "CF001",
                    "product_name": "Cà phê đen",
                    "toppings_text": "Đường, Sữa x2",
                    "quantity": 2
                }
            }
        ]
    }
}
```

### 🍳 In phiếu bếp riêng

```http
GET /api/print/data/kitchen/{kitchen_ticket_id}
```

**Response:**

```json
{
    "success": true,
    "message": "Lấy dữ liệu in phiếu bếp thành công",
    "data": {
        "type": "kitchen",
        "metadata": {
            "id": 1,
            "invoice_code": "INV-001",
            "table_name": "Bàn 01",
            "items": [
                {
                    "product_name": "Cà phê đen",
                    "quantity": 2,
                    "toppings_text": "Đường, Sữa x2"
                }
            ],
            "print_count": 2,
            "last_printed_at": "2025-10-26T14:35:00.000000Z"
        }
    }
}
```

### 🏷️ In tem phiếu riêng

```http
GET /api/print/data/label/{print_label_id}
```

**Response:**

```json
{
    "success": true,
    "message": "Lấy dữ liệu in tem phiếu thành công",
    "data": {
        "type": "label",
        "metadata": {
            "id": 1,
            "product_code": "CF001",
            "product_name": "Cà phê đen",
            "toppings_text": "Đường, Sữa x2",
            "quantity": 2,
            "print_count": 1,
            "last_printed_at": "2025-10-26T14:30:00.000000Z"
        }
    }
}
```

### 📄 Danh sách mẫu in

```http
GET /api/print/templates
```

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Hóa đơn bán hàng",
            "type": "invoice",
            "template_data": "..."
        }
    ]
}
```

## 🎯 Print Tracking

Hệ thống tự động tracking việc in:

### Fields được track:

- `print_count`: Số lần đã in
- `last_printed_at`: Thời gian in cuối cùng

### Entities có print tracking:

- ✅ `invoices` table
- ✅ `kitchen_tickets` table
- ✅ `print_labels` table

### Auto-tracking:

Mỗi khi gọi API lấy data in → `markAsPrinted()` được gọi tự động:

```php
// Trong service
$invoice->markAsPrinted(); // print_count++, last_printed_at = now()
```

## 🔧 Cấu hình Frontend

### JavaScript WebSocket Client

```javascript
// Kết nối WebSocket
const echo = new Echo({
    broadcaster: 'reverb',
    key: 'your-app-key',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// Lắng nghe channel của chi nhánh
echo.channel(`print-branch-${branchId}`).listen('PrintRequested', (event) => {
    console.log('Print request received:', event);

    // Gọi API lấy data in
    fetchPrintData(event.type, event.id);
});

// Function lấy data và in
async function fetchPrintData(type, id) {
    try {
        const response = await fetch(`/api/print/data/${type}/${id}`);
        const result = await response.json();

        if (result.success) {
            // Xử lý data và gửi đến máy in
            printDocument(result.data);
        }
    } catch (error) {
        console.error('Print error:', error);
    }
}
```

## 🚀 Trigger Print từ Backend

### Gửi Event PrintRequested

```php
use App\Events\PrintRequested;

// Trong Controller hoặc Service
event(new PrintRequested('invoice', $invoiceId, $branchId));

// Hoặc qua broadcast
broadcast(new PrintRequested('provisional', $orderId, $branchId));
```

### Event Class

```php
class PrintRequested implements ShouldBroadcast
{
    public function __construct(
        public string $type,
        public int $id,
        public int $branchId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("print-branch-{$this->branchId}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'print.requested';
    }
}
```

## 📊 Database Schema

### Invoices Table

```sql
print_count INT DEFAULT 0,
last_printed_at DATETIME NULL
```

### Kitchen Tickets Table

```sql
id BIGINT PRIMARY KEY,
branch_id BIGINT,
invoice_id BIGINT,
metadata JSON,
print_count INT DEFAULT 0,
last_printed_at DATETIME NULL
```

### Print Labels Table

```sql
id BIGINT PRIMARY KEY,
invoice_item_id BIGINT,
branch_id BIGINT,
product_code VARCHAR(255),
toppings_text TEXT,
print_count INT DEFAULT 0,
last_printed_at DATETIME NULL
```

## ⚡ Ưu điểm của kiến trúc mới

1. **Pull Model**: Frontend chủ động lấy data khi cần
2. **Lightweight WebSocket**: Chỉ gửi thông báo nhẹ, không gửi full data
3. **Auto Tracking**: Tự động track số lần in và thời gian
4. **Entity Specific**: Mỗi loại document có entity riêng
5. **Unified API**: Một endpoint cho tất cả loại print data
6. **No Confirmation**: Không cần API confirm riêng

## 🔍 Troubleshooting

### Lỗi thường gặp:

**404 Not Found khi gọi API:**

- Kiểm tra type có hợp lệ không
- Kiểm tra ID entity có tồn tại không

**WebSocket không nhận được event:**

- Kiểm tra connection_code có đúng không
- Kiểm tra Reverb server có chạy không
- Kiểm tra channel name có đúng format không

**Data không đầy đủ:**

- Kiểm tra relationships trong Eloquent model
- Kiểm tra data seeding có đầy đủ không

## 📝 Migration Notes

Từ hệ thống cũ:

- ❌ Bỏ `PrintHistory` generic table
- ❌ Bỏ `KitchenTicketItem` normalized table
- ❌ Bỏ các API confirm riêng
- ✅ Sử dụng JSON metadata thay vì normalized
- ✅ Print tracking tích hợp trong từng entity
- ✅ Unified endpoint cho tất cả print data
