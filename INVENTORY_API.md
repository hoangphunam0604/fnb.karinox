# API Kiểm Kho - Hướng Dẫn Sử Dụng

## 🎯 Tổng Quan

API quản lý kiểm kho cho hệ thống Karinox F&B, hỗ trợ:

- Kiểm kho (stocktaking)
- Nhập kho (import)
- Xuất kho (export)
- Chuyển kho giữa chi nhánh (transfer)
- Báo cáo tồn kho

## 🔐 Authentication

Tất cả API yêu cầu:

- Bearer Token (JWT)
- Header: `X-Karinox-App: karinox-fnb`
- Header: `X-Branch-Id: {branch_id}` (hoặc gửi `branch_id` trong query/body)
- Role: `admin` hoặc `manager`

**💡 Lưu ý về Branch ID:**

- Nếu gửi `branch_id` trong query parameter hoặc request body → Sử dụng giá trị đó
- Nếu KHÔNG gửi → Tự động lấy từ header `X-Branch-Id` (karinox-branch-id)
- Điều này giúp không cần gửi `branch_id` nhiều lần khi đã set header

---

## 📋 API Endpoints

### 1. Lấy Danh Sách Giao Dịch Kho

```http
GET /api/admin/inventory/transactions
```

**Query Parameters:**

- `branch_id` (optional): Lọc theo chi nhánh. Nếu không có, lấy từ header `X-Branch-Id`
- `transaction_type` (optional): import, export, sale, return, transfer_out, transfer_in, stocktaking
- `per_page` (optional, default: 20): Số bản ghi mỗi trang

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "branch_id": 1,
      "branch_name": "Chi nhánh trung tâm",
      "transaction_type": "stocktaking",
      "transaction_type_label": "Điều chỉnh tồn kho dựa trên kết quả kiểm kho",
      "note": "Kiểm kho định kỳ tháng 10",
      "reference_id": null,
      "destination_branch_id": null,
      "destination_branch_name": null,
      "created_at": "2025-10-16 23:50:00",
      "updated_at": "2025-10-16 23:50:00",
      "items": [
        {
          "id": 1,
          "transaction_id": 1,
          "product_id": 5,
          "product_name": "Cà phê rang xay",
          "product_code": "CF001",
          "product_unit": "gram",
          "quantity": 10000,
          "created_at": "2025-10-16 23:50:00"
        }
      ]
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### 2. Xem Chi Tiết Giao Dịch

```http
GET /api/admin/inventory/transactions/{id}
```

**Response:**

```json
{
  "data": {
    "id": 1,
    "branch_id": 1,
    "branch_name": "Chi nhánh trung tâm",
    "transaction_type": "stocktaking",
    "transaction_type_label": "Điều chỉnh tồn kho dựa trên kết quả kiểm kho",
    "note": "Kiểm kho định kỳ",
    "items": [...]
  }
}
```

---

### 3. Báo Cáo Tồn Kho

```http
GET /api/admin/inventory/stock-report?branch_id={branch_id}
```

**Query Parameters:**

- `branch_id` (optional): ID chi nhánh. Nếu không có, lấy từ header `X-Branch-Id`

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "product_id": 5,
            "product_name": "Cà phê rang xay",
            "product_code": "CF001",
            "product_unit": "gram",
            "product_type": "ingredient",
            "branch_id": 1,
            "stock_quantity": 10000,
            "min_stock": 5000,
            "max_stock": 20000,
            "is_low_stock": false,
            "is_out_of_stock": false
        },
        {
            "id": 2,
            "product_id": 6,
            "product_name": "Sữa tươi",
            "product_code": "MILK001",
            "product_unit": "ml",
            "product_type": "ingredient",
            "branch_id": 1,
            "stock_quantity": 3000,
            "min_stock": 5000,
            "max_stock": 15000,
            "is_low_stock": true,
            "is_out_of_stock": false
        }
    ]
}
```

---

### 4. Kiểm Kho

```http
POST /api/admin/inventory/stocktaking
```

**Request Body:**

```json
{
    "branch_id": 1, // Optional: Nếu không gửi, lấy từ header X-Branch-Id
    "items": [
        {
            "product_id": 5,
            "actual_quantity": 9850
        },
        {
            "product_id": 6,
            "actual_quantity": 4800
        }
    ],
    "note": "Kiểm kho định kỳ tháng 10/2025"
}
```

**💡 Tip:** Nếu đã set header `X-Branch-Id: 1`, có thể bỏ qua `branch_id` trong body:

```json
{
    "items": [
        { "product_id": 5, "actual_quantity": 9850 },
        { "product_id": 6, "actual_quantity": 4800 }
    ],
    "note": "Kiểm kho định kỳ tháng 10/2025"
}
```

**Response (Có chênh lệch):**

```json
{
  "message": "Kiểm kho thành công",
  "transaction": {
    "id": 1,
    "branch_id": 1,
    "transaction_type": "stocktaking",
    "items": [...]
  },
  "differences": [
    {
      "product_id": 5,
      "product_name": "Cà phê rang xay",
      "system_quantity": 10000,
      "actual_quantity": 9850,
      "difference": -150
    },
    {
      "product_id": 6,
      "product_name": "Sữa tươi",
      "system_quantity": 5000,
      "actual_quantity": 4800,
      "difference": -200
    }
  ]
}
```

**Response (Không có chênh lệch):**

```json
{
    "message": "Không có chênh lệch nào, không cần điều chỉnh tồn kho",
    "differences": []
}
```

---

### 5. Nhập Kho

```http
POST /api/admin/inventory/import
```

**Request Body:**

```json
{
    "branch_id": 1, // Optional: Nếu không gửi, lấy từ header X-Branch-Id
    "items": [
        {
            "product_id": 5,
            "quantity": 5000
        },
        {
            "product_id": 6,
            "quantity": 10000
        }
    ],
    "note": "Nhập hàng từ nhà cung cấp ABC"
}
```

**Response:**

```json
{
  "message": "Nhập kho thành công",
  "transaction": {
    "id": 2,
    "branch_id": 1,
    "transaction_type": "import",
    "note": "Nhập hàng từ nhà cung cấp ABC",
    "items": [...]
  }
}
```

---

### 6. Xuất Kho

```http
POST /api/admin/inventory/export
```

**Request Body:**

```json
{
    "branch_id": 1,
    "items": [
        {
            "product_id": 5,
            "quantity": 1000
        }
    ],
    "note": "Xuất hủy hàng hỏng"
}
```

**Response:**

```json
{
  "message": "Xuất kho thành công",
  "transaction": {
    "id": 3,
    "branch_id": 1,
    "transaction_type": "export",
    "items": [...]
  }
}
```

---

### 7. Chuyển Kho

```http
POST /api/admin/inventory/transfer
```

**Request Body:**

```json
{
    "from_branch_id": 1,
    "to_branch_id": 2,
    "items": [
        {
            "product_id": 5,
            "quantity": 2000
        },
        {
            "product_id": 6,
            "quantity": 3000
        }
    ],
    "note": "Chuyển hàng sang chi nhánh quận 7"
}
```

**Response:**

```json
{
  "message": "Chuyển kho thành công",
  "transaction": {
    "id": 4,
    "branch_id": 2,
    "transaction_type": "transfer_in",
    "destination_branch_id": 1,
    "note": "Chuyển hàng sang chi nhánh quận 7",
    "items": [...]
  }
}
```

---

## ⚠️ Error Responses

### 400 Bad Request

```json
{
    "error": "Vui lòng chọn chi nhánh"
}
```

### 422 Validation Error

```json
{
    "errors": {
        "branch_id": ["The branch id field is required."],
        "items": ["The items field must be an array."],
        "items.0.product_id": ["The items.0.product_id field is required."]
    }
}
```

### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
    "message": "This action is unauthorized."
}
```

---

## 📝 Validation Rules

### Kiểm Kho (Stocktaking)

- `branch_id`: required, exists:branches,id
- `items`: required, array, min:1
- `items.*.product_id`: required, exists:products,id
- `items.*.actual_quantity`: required, numeric, min:0
- `note`: nullable, string, max:500

### Nhập/Xuất Kho

- `branch_id`: required, exists:branches,id
- `items`: required, array, min:1
- `items.*.product_id`: required, exists:products,id
- `items.*.quantity`: required, numeric, min:0
- `note`: nullable, string, max:500

### Chuyển Kho

- `from_branch_id`: required, exists:branches,id
- `to_branch_id`: required, exists:branches,id, different:from_branch_id
- `items`: required, array, min:1
- `items.*.product_id`: required, exists:products,id
- `items.*.quantity`: required, numeric, min:0
- `note`: nullable, string, max:500

---

## 🔄 Workflow Kiểm Kho

1. **Lấy báo cáo tồn kho hiện tại:**

    ```
    GET /api/admin/inventory/stock-report?branch_id=1
    ```

2. **Kiểm đếm thực tế tại kho**

3. **Gửi dữ liệu kiểm kho:**

    ```
    POST /api/admin/inventory/stocktaking
    {
      "branch_id": 1,
      "items": [
        {"product_id": 5, "actual_quantity": 9850},
        {"product_id": 6, "actual_quantity": 4800}
      ],
      "note": "Kiểm kho tháng 10"
    }
    ```

4. **Hệ thống tự động:**
    - So sánh số lượng thực tế vs hệ thống
    - Tạo giao dịch kiểm kho nếu có chênh lệch
    - Cập nhật tồn kho = số lượng thực tế
    - Trả về danh sách chênh lệch

---

## 💡 Lưu Ý

- **Kiểm kho** sẽ GHI ĐÈ số lượng tồn kho = số lượng thực tế
- **Nhập/Xuất kho** sẽ CỘNG/TRỪ vào tồn kho hiện tại
- Chỉ tạo giao dịch kiểm kho khi CÓ chênh lệch
- Hệ thống tự động xử lý nguyên liệu cho sản phẩm có công thức
- Tất cả giao dịch đều được ghi log với user_id và timestamp
