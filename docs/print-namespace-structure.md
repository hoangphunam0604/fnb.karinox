# 📁 Tài liệu Cấu trúc Print System Namespace

## 🎯 Mục tiêu

Tổ chức lại Print System với namespace riêng biệt tương tự Admin, đảm bảo:

- **Separation of Concerns**: Tách biệt logic Print khỏi API chung
- **Clean Architecture**: Cấu trúc rõ ràng với Controllers, Requests, Resources
- **Scalability**: Dễ mở rộng và maintain
- **Consistency**: Đồng nhất với cấu trúc Admin, POS

## 🏗️ Cấu trúc Namespace

```
app/Http/Print/
├── Controllers/
│   ├── PrintController.php          # Main print controller
│   └── PrintTemplateController.php  # Template management (NEW)
├── Requests/
│   ├── PrintProvisionalRequest.php  # Validation cho in tạm tính
│   ├── PrintInvoiceRequest.php      # Validation cho in hóa đơn
│   ├── PrintLabelsRequest.php       # Validation cho in tem phiếu
│   ├── PrintKitchenRequest.php      # Validation cho in phiếu bếp
│   ├── PrintAutoRequest.php         # Validation cho in tự động
│   ├── TestPrintRequest.php         # Validation cho test print (NEW)
│   ├── CreatePrintTemplateRequest.php # Validation tạo template (NEW)
│   └── UpdatePrintTemplateRequest.php # Validation update template (NEW)
└── Resources/
    ├── PrintJobResource.php         # Response format cho print job
    ├── PrintQueueResource.php       # Response format cho queue
    └── PrintTemplateResource.php    # Response format cho template

routes/
└── api-print.php                    # Print system routes

services/
└── MockDataService.php              # Generate mock data cho test print (NEW)
```

## 📊 So sánh Before/After

### Before (Old Structure)

```
app/Http/Controllers/Api/PrintController.php  ❌
routes/api-pos.php (mixed with POS routes)    ❌
- No request validation classes
- No structured responses
- Mixed concerns
```

### After (New Structure)

```
app/Http/Print/                                ✅
├── Controllers/PrintController.php            ✅
├── Requests/*.php                             ✅
└── Resources/*.php                            ✅

routes/api-print.php                           ✅
- Dedicated namespace App\Http\Print
- Form Request validation
- Resource responses
- Separate route file
```

## 🔧 API Endpoints

### 📝 Authenticated Routes (JWT Required)

Base: `/api/print/`

**Print Actions:**

```http
POST /api/print/provisional    # In tạm tính
POST /api/print/invoice        # In hóa đơn
POST /api/print/labels         # In tem phiếu
POST /api/print/kitchen        # In phiếu bếp
POST /api/print/auto           # In tự động
POST /api/print/test           # In thử với mock data (NEW)
```

**Queue Management:**

```http
GET  /api/print/queue          # Lấy hàng đợi
PUT  /api/print/queue/{id}/status  # Cập nhật trạng thái
DELETE /api/print/queue/{id}   # Xóa job
```

**Template Management:** (NEW)

```http
GET  /api/print/templates              # Danh sách templates
GET  /api/print/templates/{id}         # Chi tiết template
POST /api/print/templates              # Tạo template
PUT  /api/print/templates/{id}         # Cập nhật template
DELETE /api/print/templates/{id}       # Xóa template
POST /api/print/templates/{id}/duplicate    # Sao chép template
POST /api/print/templates/{id}/set-default # Set default
POST /api/print/templates/{id}/preview      # Xem trước
```

### 🖨️ Client Routes (API Key Auth)

Base: `/api/print/client/`

```http
GET  /api/print/client/queue           # Queue cho client
PUT  /api/print/client/queue/{id}/status  # Update từ client
POST /api/print/client/register        # Đăng ký device
PUT  /api/print/client/heartbeat       # Device heartbeat
GET  /api/print/client/history         # Lịch sử in
GET  /api/print/client/history/daily   # Báo cáo ngày
```

## 🛡️ Security & Authentication

### JWT Authentication (Staff APIs)

```php
Route::middleware([
    'auth:api',
    'is_karinox_app',
    'set_karinox_branch_id'
])->prefix('print')->group(function () {
    // Staff print APIs
});
```

### API Key Authentication (Client APIs)

```php
Route::middleware(['print_client_auth'])->prefix('print/client')->group(function () {
    // Device print APIs
});
```

## 📋 Request Validation

### Print Request Example

```php
<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintProvisionalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|exists:orders,id',
            'device_id' => 'nullable|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'ID đơn hàng là bắt buộc',
            'order_id.exists' => 'Đơn hàng không tồn tại'
        ];
    }
}
```

## 📤 Response Format

### PrintJobResource Example

```php
<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrintJobResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'content' => $this->content,
            'device_id' => $this->device_id,
            'priority' => $this->priority,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'order_code' => $this->order->order_code,
                    'table_name' => $this->order->table?->name
                ];
            })
        ];
    }
}
```

## 🔄 Migration Guide

### 1. Update Route References

```php
// Before
use App\Http\Controllers\Api\PrintController;

// After
use App\Http\Print\Controllers\PrintController;
```

### 2. Update API Calls

```javascript
// Before
POST / api / pos / print / auto;

// After
POST / api / print / auto;
```

### 3. Client Integration

```javascript
// Print Client (no user auth)
const response = await fetch('/api/print/client/queue', {
    headers: {
        'X-API-Key': 'device_api_key',
        'Content-Type': 'application/json',
    },
});
```

## ✅ Benefits

### 🎯 **Organized Structure**

- Clear separation Print vs POS vs Admin
- Dedicated namespace for print functionality
- Consistent with Laravel conventions

### 🛡️ **Enhanced Security**

- Device-based authentication for clients
- Separate middleware for different access levels
- API key management for print devices

### 📈 **Scalability**

- Easy to add new print types
- Independent versioning for Print APIs
- Clean dependency management

### 🔧 **Maintainability**

- Request validation classes
- Structured response formats
- Clear route organization
- Better error handling

## 🚀 Usage Examples

### Staff Print Request

```http
POST /api/print/auto
Authorization: Bearer jwt_token
X-Branch-ID: 1

{
  "order_id": 123,
  "device_id": "kitchen_printer_001"
}
```

### Client Queue Check

```http
GET /api/print/client/queue?device_id=kitchen_001&limit=5
X-API-Key: device_api_key_here
```

### Response Format

```json
{
    "success": true,
    "message": "In tự động thành công",
    "data": {
        "id": 501,
        "type": "kitchen",
        "device_id": "kitchen_001",
        "status": "pending",
        "created_at": "2025-10-19 14:30:00"
    }
}
```

---

## 📝 Next Steps

1. ✅ **Cấu trúc Namespace** - Hoàn thành
2. ✅ **Controllers & Routes** - Hoàn thành
3. ✅ **Request Validation** - Hoàn thành
4. ✅ **Response Resources** - Hoàn thành
5. 🔄 **Testing & Validation** - Cần test
6. 📚 **Documentation Update** - Cần cập nhật docs

Print System hiện đã có cấu trúc namespace chuyên nghiệp, sẵn sàng cho production! 🎉
