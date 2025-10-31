# 🖨️ Tài liệu Tính năng In Thử (Test Print)

## 🎯 Mục tiêu

Tính năng **Test Print** cho phép:

- **Kiểm tra máy in** mà không cần đơn hàng thật
- **Test template** in với dữ liệu mẫu
- **Xem trước kết quả** in trước khi triển khai
- **Debug vấn đề** in ấn với dữ liệu có sẵn

## 🏗️ Cấu trúc Tính năng

### 📋 Components đã tạo:

1. **TestPrintRequest** - Validation cho request
2. **MockDataService** - Generate dữ liệu giả
3. **PrintController::testPrint()** - Xử lý logic test print
4. **Routes** - `/api/print/test` và `/api/print/client/test`

## 🚀 API Endpoints

### 1. Staff Test Print (JWT Auth)

```http
POST /api/print/test
Authorization: Bearer {jwt_token}
X-Branch-ID: 1
Content-Type: application/json

{
  "print_type": "provisional",
  "template_id": 1,
  "device_id": "test_printer",
  "mock_data_type": "simple"
}
```

### 2. Client Test Print (API Key Auth)

```http
POST /api/print/client/test
X-API-Key: {device_api_key}
Content-Type: application/json

{
  "print_type": "kitchen",
  "device_id": "kitchen_printer_001",
  "mock_data_type": "complex"
}
```

## 📝 Request Parameters

| Field            | Type    | Required | Values                                              | Description                           |
| ---------------- | ------- | -------- | --------------------------------------------------- | ------------------------------------- |
| `print_type`     | string  | ✅       | `provisional`, `invoice`, `labels`, `kitchen`       | Loại in muốn test                     |
| `template_id`    | integer | ❌       | ID template                                         | Template cụ thể (null = dùng default) |
| `device_id`      | string  | ❌       | Device ID                                           | Thiết bị in (default: "test_printer") |
| `mock_data_type` | string  | ❌       | `simple`, `complex`, `with_toppings`, `large_order` | Loại dữ liệu mẫu                      |

## 🎭 Các loại Mock Data

### 1. **Simple** (Mặc định)

```json
{
    "items_count": 3,
    "total_amount": 85000,
    "items": [
        {
            "product_name": "Cà phê đen",
            "quantity": 2,
            "price": 25000
        },
        {
            "product_name": "Trà sữa truyền thống",
            "quantity": 1,
            "price": 35000
        }
    ]
}
```

### 2. **Complex** (Có khách hàng, giảm giá)

```json
{
    "items_count": 4,
    "total_amount": 173000,
    "voucher_discount": 10000,
    "voucher_code": "TESTDISCOUNT",
    "customer": {
        "name": "Nguyễn Văn Test",
        "membership_level": "Gold"
    }
}
```

### 3. **With Toppings** (Có topping)

```json
{
    "items": [
        {
            "product_name": "Trà sữa socola",
            "toppings": [
                { "name": "Trân châu đen", "price": 8000 },
                { "name": "Thạch rau câu", "price": 7000 }
            ]
        }
    ]
}
```

### 4. **Large Order** (Đơn hàng lớn)

```json
{
    "items_count": 15,
    "total_amount": 448000,
    "customer": {
        "name": "Công ty ABC",
        "membership_level": "Platinum"
    }
}
```

## 📤 Response Format

### Thành công

```json
{
    "success": true,
    "message": "Tạo job in thử provisional thành công",
    "data": {
        "id": 100001,
        "type": "provisional",
        "device_id": "test_printer",
        "status": "pending",
        "created_at": "2025-10-19 15:30:00"
    },
    "mock_data_preview": {
        "order_code": "TEST-20251019153000",
        "table_name": "Bàn Test-01",
        "items_count": 3,
        "total_amount": 85000
    }
}
```

### Lỗi

```json
{
    "success": false,
    "message": "Template không tồn tại hoặc không phù hợp với loại in"
}
```

## 🖨️ Print Templates

### Default Templates

#### 1. **Receipt Template** (provisional/invoice)

```
========================================
         Karinox Coffee Test
========================================
PHIẾU TẠM TÍNH
----------------------------------------
Mã đơn: TEST-20251019153000
Bàn: Bàn Test-01
Thời gian: 19/10/2025 15:30
NV: Nhân viên Test
----------------------------------------
Cà phê đen x2
   50,000đ

Trà sữa truyền thống x1
   35,000đ

----------------------------------------
Tạm tính: 85,000đ
Giảm giá: 0đ
TỔNG CỘNG: 85,000đ
========================================
        ** ĐÂY LÀ IN THỬ **
========================================
```

#### 2. **Kitchen Template**

```
========================================
           PHIẾU BẾP TEST
========================================
Đơn: TEST-20251019153000
Bàn: Bàn Test-01
Thời gian: 15:30 19/10
========================================
- Bánh mì thịt nướng x1
  (Không có ghi chú)

- Combo Set A x1
  (Không có ghi chú)

----------------------------------------
Ghi chú: Đơn hàng test - Ưu tiên thực hiện
Ưu tiên: high
========================================
        ** ĐÂY LÀ IN THỬ **
========================================
```

#### 3. **Label Template**

```
====================
    TEM SẢN PHẨM
====================
Trà sữa socola
Bàn: Bàn Test-01
Đơn: TEST-20251019153000
--------------------
+ Trân châu đen
+ Thạch rau câu
Ít ngọt
--------------------
1/1
====================
   ** IN THỬ **
====================
```

## 🔧 Custom Templates

### Sử dụng Template có sẵn

```http
POST /api/print/test
{
  "print_type": "invoice",
  "template_id": 5,
  "mock_data_type": "complex"
}
```

### Template Variables

Templates có thể sử dụng các biến:

- `{{order_code}}` - Mã đơn hàng
- `{{table_name}}` - Tên bàn
- `{{branch_name}}` - Tên chi nhánh
- `{{total_amount}}` - Tổng tiền
- `{{created_at}}` - Thời gian tạo
- `{{staff_name}}` - Tên nhân viên

## 🎮 Cách sử dụng

### 1. **Test In Cơ bản**

```bash
curl -X POST "http://karinox-fnb.local/api/print/test" \
  -H "Authorization: Bearer {token}" \
  -H "X-Branch-ID: 1" \
  -H "Content-Type: application/json" \
  -d '{
    "print_type": "provisional",
    "device_id": "cashier_printer"
  }'
```

### 2. **Test với Template cụ thể**

```bash
curl -X POST "http://karinox-fnb.local/api/print/test" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "print_type": "invoice",
    "template_id": 3,
    "mock_data_type": "complex"
  }'
```

### 3. **Test từ Print Client**

```bash
curl -X POST "http://karinox-fnb.local/api/print/client/test" \
  -H "X-API-Key: kitchen_device_key" \
  -d '{
    "print_type": "kitchen",
    "device_id": "kitchen_001",
    "mock_data_type": "large_order"
  }'
```

## ✅ Tính năng Test Job

### Metadata cho Test Jobs

```json
{
    "metadata": {
        "is_test": true,
        "test_data_type": "simple",
        "template_id": 1
    }
}
```

### Đặc điểm Test Jobs:

- ❌ **Không có order_id thật** (null)
- 🔻 **Priority thấp** ("low")
- 🏷️ **Được đánh dấu** "is_test": true
- 🗂️ **Dễ phân biệt** trong queue
- 🧹 **Tự động cleanup** sau 24h

## 🔍 Monitoring & Debugging

### Kiểm tra Test Jobs

```http
GET /api/print/queue?status=pending
```

### Lọc chỉ Test Jobs

```sql
SELECT * FROM print_queues
WHERE JSON_EXTRACT(metadata, '$.is_test') = true
```

### Logs Test Print

```
Log::info('Test print created', [
  'print_type' => 'provisional',
  'mock_data_type' => 'simple',
  'template_id' => 1,
  'job_id' => 100001
]);
```

## 🚨 Lưu ý quan trọng

### ⚠️ **Production Environment**

- Test jobs có priority thấp, không ảnh hưởng production
- Tự động cleanup để tránh spam queue
- Có đánh dấu rõ ràng "** ĐÂY LÀ IN THỬ **"

### 🔒 **Security**

- Staff route cần JWT authentication
- Client route cần API key authentication
- Validate template ownership theo branch

### 📊 **Performance**

- Mock data generation rất nhanh (< 10ms)
- Không truy vấn database nặng
- Template rendering optimized

---

## 🎉 Sử dụng thực tế

### 1. **Setup máy in mới**

```bash
# Test basic connectivity
POST /api/print/client/test
{
  "print_type": "provisional",
  "device_id": "new_printer_001"
}
```

### 2. **Kiểm tra template mới**

```bash
# Test custom template
POST /api/print/test
{
  "print_type": "invoice",
  "template_id": 10,
  "mock_data_type": "complex"
}
```

### 3. **Debug print issues**

```bash
# Test với data phức tạp
POST /api/print/test
{
  "print_type": "labels",
  "mock_data_type": "with_toppings"
}
```

**Tính năng Test Print giúp đảm bảo hệ thống in hoạt động ổn định trước khi phục vụ khách hàng thật!** 🎯
