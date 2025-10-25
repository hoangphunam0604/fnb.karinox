# 📋 Mẫu Metadata cho Print System

Sau khi hoá đơn được tạo thành công (InvoiceCreated event), hệ thống sẽ tự động tạo các print histories và broadcast qua WebSocket.

## 🧾 1. METADATA HÓA ĐƠN (Invoice)

```json
{
    "staff": {
        "name": "Nguyễn Văn A"
    },
    "customer": {
        "name": "Trần Thị B",
        "membership_level": "Bạc",
        "loyalty_points": 1250,
        "reward_points": 50
    },
    "invoice": {
        "id": 123,
        "code": "CN01N251025HD0001",
        "order_code": "CN01N251025ORD0001",
        "table_name": "Bàn 5",
        "subtotal_price": 150000,
        "discount_amount": 15000,
        "reward_discount": 10000,
        "total_price": 125000,
        "paid_amount": 130000,
        "change_amount": 5000,
        "tax_rate": 10,
        "tax_amount": 12500,
        "payment_method": "cash",
        "reward_points_used": 100,
        "earned_loyalty_points": 125,
        "earned_reward_points": 12,
        "note": "Không hành",
        "created_at": "25/10/2025 14:30:45",
        "items": [
            {
                "product_id": 1,
                "product_name": "Cà phê đen",
                "quantity": 2,
                "unit_price": 25000,
                "total_price": 50000,
                "toppings_text": "Đường (5,000đ) x 2, Đá (0đ) x 2"
            },
            {
                "product_id": 2,
                "product_name": "Trà sữa trân châu",
                "quantity": 1,
                "unit_price": 45000,
                "total_price": 45000,
                "toppings_text": "Trân châu đen (10,000đ) x 1, Thạch (5,000đ) x 1"
            },
            {
                "product_id": 3,
                "product_name": "Bánh mì",
                "quantity": 3,
                "unit_price": 15000,
                "total_price": 45000,
                "toppings_text": ""
            }
        ]
    }
}
```

## 🍳 2. METADATA PHIẾU BẾP (Kitchen)

Chỉ tạo nếu có ít nhất 1 sản phẩm có `print_kitchen = true`

```json
{
    "staff": {
        "name": "Nguyễn Văn A"
    },
    "customer": {
        "name": "Trần Thị B",
        "membership_level": "Bạc",
        "loyalty_points": 1250,
        "reward_points": 50
    },
    "invoice": {
        "id": 123,
        "code": "CN01N251025HD0001",
        "order_code": "CN01N251025ORD0001",
        "table_name": "Bàn 5",
        "note": "Không hành",
        "created_at": "25/10/2025 14:30:45",
        "items": [
            {
                "product_id": 2,
                "product_name": "Trà sữa trân châu",
                "quantity": 1,
                "unit_price": 45000,
                "total_price": 45000,
                "toppings_text": "Trân châu đen (10,000đ) x 1, Thạch (5,000đ) x 1"
            },
            {
                "product_id": 3,
                "product_name": "Bánh mì",
                "quantity": 3,
                "unit_price": 15000,
                "total_price": 45000,
                "toppings_text": ""
            }
        ],
        "priority": "high"
    }
}
```

## 🏷️ 3. METADATA TEM PHIẾU (Label)

Tạo 1 print history cho MỖI sản phẩm có `print_label = true`

### Label cho sản phẩm 1:

```json
{
    "invoice_code": "CN01N251025HD0001",
    "order_code": "CN01N251025ORD0001",
    "table_name": "Bàn 5",
    "product": {
        "id": 1,
        "name": "Cà phê đen",
        "quantity": 2,
        "unit_price": 25000,
        "total_price": 50000,
        "toppings_text": "Đường (5,000đ) x 2, Đá (0đ) x 2"
    },
    "created_at": "25/10/2025 14:30:45"
}
```

### Label cho sản phẩm 2:

```json
{
    "invoice_code": "CN01N251025HD0001",
    "order_code": "CN01N251025ORD0001",
    "table_name": "Bàn 5",
    "product": {
        "id": 2,
        "name": "Trà sữa trân châu",
        "quantity": 1,
        "unit_price": 45000,
        "total_price": 45000,
        "toppings_text": "Trân châu đen (10,000đ) x 1, Thạch (5,000đ) x 1"
    },
    "created_at": "25/10/2025 14:30:45"
}
```

### Label cho sản phẩm 3:

```json
{
    "invoice_code": "CN01N251025HD0001",
    "order_code": "CN01N251025ORD0001",
    "table_name": "Bàn 5",
    "product": {
        "id": 3,
        "name": "Bánh mì",
        "quantity": 3,
        "unit_price": 15000,
        "total_price": 45000,
        "toppings_text": ""
    },
    "created_at": "25/10/2025 14:30:45"
}
```

## 📡 WebSocket Event Format

Mỗi print history sẽ được broadcast qua WebSocket channel: `print-branch-{branch_id}`

```json
{
    "print_id": "print_1730012345_66f1a2b4c5d6e",
    "type": "invoice", // hoặc "kitchen", "label"
    "metadata": {
        // Metadata tương ứng theo type như trên
    },
    "timestamp": "2025-10-25T14:30:45.123Z"
}
```

## 🔄 Flow Hoàn Chỉnh

1. **Tạo Invoice** → Trigger `InvoiceCreated` event
2. **CreateInvoicePrintJobs Listener** được gọi:
    - Load relationships: staff, customer, order.table, items.product, items.toppings
    - Tạo Print History cho **Hóa đơn** (luôn luôn)
    - Tạo Print History cho **Phiếu bếp** (nếu có món `print_kitchen = true`)
    - Tạo Print History cho **Tem phiếu** (cho mỗi món `print_label = true`)
3. **Broadcast WebSocket** cho mỗi print history
4. **Frontend nhận** → Render theo template → In

## 📊 Database Structure

### Print Histories Table

```
- print_id (unique)
- branch_id
- type (invoice|kitchen|label|receipt|report|other)
- metadata (JSON)
- status (requested|printed|confirmed|failed)
- requested_at
- printed_at
- confirmed_at
```

## 🎯 Lưu Ý Quan Trọng

### Toppings Text Format

```php
// Từ:
[
  {"topping_name": "Đường", "price": 5000, "quantity": 2},
  {"topping_name": "Đá", "price": 0, "quantity": 2}
]

// Thành:
"Đường (5,000đ) x 2, Đá (0đ) x 2"
```

### Điều Kiện Tạo Print History

- **Invoice**: Luôn tạo
- **Kitchen**: Chỉ tạo nếu `kitchenItems->isNotEmpty()`
- **Label**: Tạo cho mỗi item có `product->print_label === true`

### Customer Info

Nếu không có customer (khách lẻ):

```json
{
    "customer": {
        "name": "Khách lẻ",
        "membership_level": "N/A",
        "loyalty_points": 0,
        "reward_points": 0
    }
}
```

## ✅ Testing

Để test flow này:

1. Tạo Order với items có `print_kitchen` và `print_label`
2. Complete Order
3. Tạo Invoice từ Order
4. Check PrintHistory table
5. Monitor WebSocket channel
6. Verify metadata structure

```bash
# Check print histories
SELECT * FROM print_histories ORDER BY created_at DESC LIMIT 10;

# Check by type
SELECT type, COUNT(*) FROM print_histories GROUP BY type;
```
