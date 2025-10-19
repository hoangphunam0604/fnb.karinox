# 📄 Tài liệu Quản lý Print Templates

## 🎯 Mục tiêu

**Print Template Management** đã được di chuyển hoàn toàn vào **Print namespace** để ứng dụng quản lý in có thể:

- ✅ **Tự quản lý templates** độc lập với POS
- ✅ **CRUD operations** đầy đủ cho templates
- ✅ **Preview templates** với mock data
- ✅ **Set default templates** cho từng loại
- ✅ **Duplicate & customize** templates

## 🏗️ API Endpoints mới

### 🔐 Staff APIs (JWT Authentication)

Base URL: `/api/print/templates`

| Method | Endpoint                                | Description             |
| ------ | --------------------------------------- | ----------------------- |
| GET    | `/api/print/templates`                  | Lấy danh sách templates |
| GET    | `/api/print/templates/{id}`             | Chi tiết template       |
| POST   | `/api/print/templates`                  | Tạo template mới        |
| PUT    | `/api/print/templates/{id}`             | Cập nhật template       |
| DELETE | `/api/print/templates/{id}`             | Xóa template            |
| POST   | `/api/print/templates/{id}/duplicate`   | Sao chép template       |
| POST   | `/api/print/templates/{id}/set-default` | Đặt template mặc định   |
| POST   | `/api/print/templates/{id}/preview`     | Xem trước với mock data |

### 🔑 Client APIs (API Key Authentication)

Base URL: `/api/print/client/templates`

| Method | Endpoint                           | Description                   |
| ------ | ---------------------------------- | ----------------------------- |
| GET    | `/api/print/client/templates`      | Lấy templates (read-only)     |
| GET    | `/api/print/client/templates/{id}` | Chi tiết template (read-only) |

---

## 📋 API Documentation

### 1. **Lấy danh sách Templates**

```http
GET /api/print/templates
Authorization: Bearer {jwt_token}
X-Branch-ID: 1

Query Parameters:
- type: provisional|invoice|labels|kitchen
- is_active: true|false
- branch_id: integer
```

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Template Hóa đơn Karinox",
            "type": "invoice",
            "is_active": true,
            "is_default": true,
            "branch_id": 1,
            "variables": ["order_code", "table_name", "total_amount"],
            "created_at": "2025-10-19 10:00:00"
        }
    ],
    "branch_id": 1
}
```

### 2. **Tạo Template mới**

```http
POST /api/print/templates
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "name": "Template Tạm tính Custom",
  "type": "provisional",
  "content": "{{branch_name}}\n---\nĐơn: {{order_code}}\nBàn: {{table_name}}\nTổng: {{total_amount}}đ",
  "variables": ["branch_name", "order_code", "table_name", "total_amount"],
  "is_active": true,
  "is_default": false,
  "description": "Template tùy chỉnh cho phiếu tạm tính"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Tạo template thành công",
    "data": {
        "id": 10,
        "name": "Template Tạm tính Custom",
        "type": "provisional",
        "content": "{{branch_name}}...",
        "is_active": true,
        "created_at": "2025-10-19 15:30:00"
    }
}
```

### 3. **Cập nhật Template**

```http
PUT /api/print/templates/10
Authorization: Bearer {jwt_token}

{
  "name": "Template Tạm tính Updated",
  "content": "PHIẾU TẠM TÍNH\n{{branch_name}}\n---\nĐơn: {{order_code}}\nBàn: {{table_name}}\nTổng: {{total_amount}}đ\n---\nCảm ơn quý khách!",
  "is_active": true
}
```

### 4. **Xem trước Template**

```http
POST /api/print/templates/10/preview
Authorization: Bearer {jwt_token}

{
  "mock_data_type": "complex"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "template": {
            "id": 10,
            "name": "Template Tạm tính Custom",
            "type": "provisional"
        },
        "rendered_content": "PHIẾU TẠM TÍNH\nKarinox Coffee Test\n---\nĐơn: TEST-20251019153000\nBàn: Bàn Test-01\nTổng: 173,000đ\n---\nCảm ơn quý khách!",
        "mock_data_used": "complex"
    }
}
```

### 5. **Sao chép Template**

```http
POST /api/print/templates/5/duplicate
Authorization: Bearer {jwt_token}
```

**Response:**

```json
{
    "success": true,
    "message": "Sao chép template thành công",
    "data": {
        "id": 11,
        "name": "Template Hóa đơn Karinox (Copy)",
        "type": "invoice",
        "is_default": false
    }
}
```

### 6. **Đặt Template mặc định**

```http
POST /api/print/templates/11/set-default
Authorization: Bearer {jwt_token}
```

**Response:**

```json
{
    "success": true,
    "message": "Đặt template mặc định thành công",
    "data": {
        "id": 11,
        "is_default": true
    }
}
```

---

## 🎨 Template Variables

### **Biến cơ bản hỗ trợ:**

| Variable              | Description       | Example                  |
| --------------------- | ----------------- | ------------------------ |
| `{{order_code}}`      | Mã đơn hàng       | "ORD-001"                |
| `{{table_name}}`      | Tên bàn           | "Bàn 05"                 |
| `{{branch_name}}`     | Tên chi nhánh     | "Karinox Coffee"         |
| `{{branch_address}}`  | Địa chỉ chi nhánh | "123 Nguyễn Văn Linh..." |
| `{{branch_phone}}`    | SĐT chi nhánh     | "0901234567"             |
| `{{staff_name}}`      | Tên nhân viên     | "Nguyễn Văn A"           |
| `{{total_amount}}`    | Tổng tiền         | "150,000"                |
| `{{subtotal}}`        | Tạm tính          | "140,000"                |
| `{{discount_amount}}` | Giảm giá          | "10,000"                 |
| `{{created_at}}`      | Thời gian         | "19/10/2025 15:30"       |

### **Loop cho Items:**

```html
{{#items}} - {{product_name}} x{{quantity}} {{#toppings}} + {{name}}: {{price}}đ {{/toppings}} Giá: {{total}}đ {{note}} {{/items}}
```

---

## 🖨️ Template Examples

### 1. **Provisional Template**

```
========================================
         {{branch_name}}
========================================
PHIẾU TẠM TÍNH
----------------------------------------
Đơn: {{order_code}}
Bàn: {{table_name}}
Thời gian: {{created_at}}
NV: {{staff_name}}
----------------------------------------
{{#items}}
{{product_name}} x{{quantity}}
{{#toppings}}
  + {{name}}: {{price}}đ
{{/toppings}}
  {{total}}đ

{{/items}}
----------------------------------------
Tạm tính: {{subtotal}}đ
Giảm giá: {{discount_amount}}đ
TỔNG CỘNG: {{total_amount}}đ
========================================
```

### 2. **Kitchen Template**

```
========================================
           PHIẾU BẾP
========================================
Đơn: {{order_code}}
Bàn: {{table_name}}
Thời gian: {{created_at}}
========================================
{{#items}}
{{product_name}} x{{quantity}}
{{#toppings}}
+ {{name}}
{{/toppings}}
Ghi chú: {{note}}

{{/items}}
========================================
```

### 3. **Label Template**

```
====================
  {{product_name}}
====================
Bàn: {{table_name}}
Đơn: {{order_code}}
--------------------
{{#toppings}}
+ {{name}}
{{/toppings}}
{{note}}
--------------------
{{item_number}}/{{total_quantity}}
====================
```

---

## 🔧 Client Integration

### **Lấy templates cho print client:**

```bash
curl -X GET "http://karinox-fnb.local/api/print/client/templates?type=kitchen" \
  -H "X-API-Key: kitchen_device_key"
```

### **Sử dụng template trong test print:**

```bash
curl -X POST "http://karinox-fnb.local/api/print/client/test" \
  -H "X-API-Key: device_key" \
  -d '{
    "print_type": "invoice",
    "template_id": 5,
    "mock_data_type": "complex"
  }'
```

---

## 🚀 Migration từ POS

### **Routes đã thay đổi:**

```diff
- GET /api/pos/print-templates
+ GET /api/print/templates

- Chỉ có list templates
+ Full CRUD + preview + duplicate + set default
```

### **Features mới:**

- ✅ **CRUD operations** đầy đủ
- ✅ **Template preview** với mock data
- ✅ **Duplicate templates**
- ✅ **Set default templates**
- ✅ **Advanced filtering**
- ✅ **Client access** với API keys

### **Compatibility:**

- ✅ **PrintTemplateService** updated với methods mới
- ✅ **Existing templates** work normally
- ✅ **Branch-specific** templates supported
- ✅ **Global templates** (branch_id = null) supported

---

## 💡 Best Practices

### **1. Template Organization**

```
📁 Templates per Branch:
├── Provisional Templates
│   ├── Default Provisional ⭐
│   └── Custom Provisional
├── Invoice Templates
│   ├── Default Invoice ⭐
│   └── VIP Invoice
├── Kitchen Templates
│   └── Default Kitchen ⭐
└── Label Templates
    └── Default Label ⭐
```

### **2. Variable Usage**

- Sử dụng `{{variable}}` cho single values
- Sử dụng `{{#items}}...{{/items}}` cho loops
- Test với mock data trước khi deploy

### **3. Template Management**

- Giữ 1 default template cho mỗi type
- Backup templates quan trọng bằng duplicate
- Test preview trước khi set default
- Sử dụng descriptive names

---

## 🎯 Usage Examples

### **Setup templates cho chi nhánh mới:**

```bash
# 1. Lấy templates hiện có
GET /api/print/templates

# 2. Duplicate template làm base
POST /api/print/templates/1/duplicate

# 3. Customize template mới
PUT /api/print/templates/10
{
  "name": "Invoice Chi nhánh 2",
  "content": "..."
}

# 4. Set làm default
POST /api/print/templates/10/set-default
```

**Print Template Management giờ đây hoàn toàn thuộc về Print namespace, sẵn sàng cho ứng dụng quản lý in độc lập!** 🎉
