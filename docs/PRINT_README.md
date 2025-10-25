# 🖨️ Print System - Quick Start

## API Endpoints

| Method | Endpoint                            | Description       |
| ------ | ----------------------------------- | ----------------- |
| `POST` | `/api/print/branchs/{code}/connect` | Kết nối chi nhánh |
| `GET`  | `/api/print/templates`              | Danh sách mẫu in  |
| `GET`  | `/api/print/data/{type}/{id}`       | **Lấy data in**   |

## Print Types

| Type          | ID               | Description                   |
| ------------- | ---------------- | ----------------------------- |
| `provisional` | Order ID         | In tạm tính                   |
| `invoice-all` | Invoice ID       | In hóa đơn + kitchen + labels |
| `invoice`     | Invoice ID       | Chỉ in hóa đơn                |
| `kitchen`     | KitchenTicket ID | Chỉ in phiếu bếp              |
| `label`       | PrintLabel ID    | Chỉ in tem phiếu              |

## WebSocket Flow

```
1. Listen: print-branch-{branch_id}
2. Event: print.requested
3. Payload: {type, id, branch_id}
4. API Call: GET /api/print/data/{type}/{id}
5. Print: Use returned data
```

## Trigger Print

```php
// Gửi lệnh in
event(new PrintRequested('invoice', $invoiceId, $branchId));
```

## Auto Print Tracking

- ✅ `print_count` auto increment
- ✅ `last_printed_at` auto update
- ✅ No confirm API needed

---

📖 **Chi tiết:** Xem [PRINT_SYSTEM_DOCS.md](./PRINT_SYSTEM_DOCS.md)
