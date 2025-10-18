# 📊 PRODUCT STOCK CARD API

API thẻ kho cho từng sản phẩm - theo dõi lịch sử xuất nhập tồn chi tiết.

## 🎯 **Overview**

API này cung cấp 2 endpoints chính:

1. **Product Stock Card** - Lịch sử giao dịch chi tiết
2. **Product Stock Summary** - Tóm tắt thống kê tồn kho

---

## 📋 **1. GET Product Stock Card**

Lấy lịch sử giao dịch kho chi tiết của một sản phẩm.

### **Endpoint**

```
GET /api/admin/inventory/product-card/{product_id}
```

### **Headers**

```
Authorization: Bearer {jwt_token}
karinox-app-id: karinox-app-admin
X-Branch-Id: {branch_id}
```

### **Query Parameters**

| Parameter   | Type    | Required | Description                          |
| ----------- | ------- | -------- | ------------------------------------ |
| `branch_id` | integer | No\*     | ID chi nhánh (có thể lấy từ header)  |
| `from_date` | date    | No       | Từ ngày (Y-m-d)                      |
| `to_date`   | date    | No       | Đến ngày (Y-m-d)                     |
| `type`      | string  | No       | Loại giao dịch                       |
| `per_page`  | integer | No       | Số record/trang (1-100, default: 20) |

**Valid transaction types:**

- `import` - Nhập kho
- `export` - Xuất kho
- `transfer_in` - Chuyển đến
- `transfer_out` - Chuyển đi
- `stocktaking` - Kiểm kho
- `adjustment` - Điều chỉnh
- `sale` - Bán hàng
- `return` - Trả hàng

### **Example Request**

```bash
curl -X GET "http://karinox-fnb.nam/api/admin/inventory/product-card/1?from_date=2024-01-01&to_date=2024-12-31&type=import&per_page=10" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..." \
  -H "karinox-app-id: karinox-app-admin" \
  -H "X-Branch-Id: 1"
```

### **Response**

```json
{
    "data": [
        {
            "transaction_id": 15,
            "date": "2024-10-18 14:30:25",
            "type": "import",
            "type_label": "Nhập kho",
            "reference_number": "IMP-2024101800015",
            "quantity_before": 100,
            "quantity_change": 50,
            "quantity_after": 150,
            "unit_cost": 25000,
            "total_cost": 1250000,
            "note": "Nhập hàng từ nhà cung cấp A",
            "branch": {
                "id": 1,
                "name": "Chi nhánh Quận 1",
                "code": "Q1"
            },
            "user": {
                "id": 2,
                "fullname": "Nguyễn Văn Admin"
            }
        }
    ],
    "links": {
        "first": "http://karinox-fnb.nam/api/admin/inventory/product-card/1?page=1",
        "last": "http://karinox-fnb.nam/api/admin/inventory/product-card/1?page=3",
        "prev": null,
        "next": "http://karinox-fnb.nam/api/admin/inventory/product-card/1?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 10,
        "to": 10,
        "total": 28
    }
}
```

---

## 📊 **2. GET Product Stock Summary**

Lấy tóm tắt thống kê tồn kho của một sản phẩm.

### **Endpoint**

```
GET /api/admin/inventory/product-summary/{product_id}
```

### **Headers**

```
Authorization: Bearer {jwt_token}
karinox-app-id: karinox-app-admin
X-Branch-Id: {branch_id}
```

### **Query Parameters**

| Parameter   | Type    | Required | Description                         |
| ----------- | ------- | -------- | ----------------------------------- |
| `branch_id` | integer | No\*     | ID chi nhánh (có thể lấy từ header) |
| `from_date` | date    | No       | Từ ngày để tính thống kê            |
| `to_date`   | date    | No       | Đến ngày để tính thống kê           |

### **Example Request**

```bash
curl -X GET "http://karinox-fnb.nam/api/admin/inventory/product-summary/1?from_date=2024-10-01&to_date=2024-10-31" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..." \
  -H "karinox-app-id: karinox-app-admin" \
  -H "X-Branch-Id: 1"
```

### **Response**

```json
{
    "data": {
        "product": {
            "id": 1,
            "code": "CF0001",
            "name": "Cà phê đen đá",
            "unit": "ly",
            "cost_price": 15000,
            "regular_price": 25000,
            "category": {
                "id": 1,
                "name": "Cà phê",
                "code_prefix": "CF"
            }
        },
        "current_stock": {
            "quantity": 145,
            "value": 2175000,
            "last_updated": "2024-10-18 14:30:25"
        },
        "statistics": {
            "total_imported": 200,
            "total_exported": 55,
            "total_sold": 45,
            "total_adjusted": 0,
            "transactions_count": 12
        },
        "period_summary": {
            "period": "2024-10-01 đến 2024-10-31",
            "opening_stock": 100,
            "closing_stock": 145,
            "net_change": 45
        }
    }
}
```

---

## 🔍 **Use Cases**

### **1. Theo dõi lịch sử sản phẩm**

```bash
# Xem tất cả giao dịch của sản phẩm CF0001
GET /api/admin/inventory/product-card/1
```

### **2. Kiểm tra giao dịch trong tháng**

```bash
# Xem giao dịch tháng 10/2024
GET /api/admin/inventory/product-card/1?from_date=2024-10-01&to_date=2024-10-31
```

### **3. Kiểm tra chỉ giao dịch bán hàng**

```bash
# Xem lịch sử bán hàng
GET /api/admin/inventory/product-card/1?type=sale
```

### **4. Báo cáo tổng quan sản phẩm**

```bash
# Tóm tắt tình trạng tồn kho
GET /api/admin/inventory/product-summary/1
```

### **5. Phân tích theo kỳ**

```bash
# Thống kê Q3/2024
GET /api/admin/inventory/product-summary/1?from_date=2024-07-01&to_date=2024-09-30
```

---

## ⚠️ **Error Responses**

### **404 - Product Not Found**

```json
{
    "error": "Sản phẩm không tồn tại"
}
```

### **400 - Missing Branch**

```json
{
    "error": "Vui lòng chọn chi nhánh"
}
```

### **422 - Validation Error**

```json
{
    "errors": {
        "to_date": ["The to date field must be a date after or equal to from date."],
        "type": ["The selected type is invalid."]
    }
}
```

---

## 🎯 **Features**

✅ **Lịch sử đầy đủ** - Tất cả giao dịch xuất/nhập/tồn  
✅ **Filter linh hoạt** - Theo ngày, loại giao dịch  
✅ **Phân trang** - Hiệu suất cao với dữ liệu lớn  
✅ **Thống kê chi tiết** - Tổng hợp số liệu theo kỳ  
✅ **Multi-branch** - Hỗ trợ nhiều chi nhánh  
✅ **Real-time** - Dữ liệu cập nhật thời gian thực

## 🚀 **Performance**

- **Pagination**: 20 records/request (max 100)
- **Response time**: ~150-200ms
- **Caching**: Header-based branch detection
- **Indexing**: Optimized for product_id + branch_id queries
