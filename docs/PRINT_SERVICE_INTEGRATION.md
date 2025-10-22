# 🖨️ PRINT SERVICE INTEGRATION GUIDE

## 📖 Tổng Quan Hệ Thống

### 🏗️ Kiến Trúc Print System

Hệ thống in được thiết kế với 2 API riêng biệt:

1. **POS Print API** - Dành cho nhân viên bán hàng
2. **Print Service API** - Dành cho ứng dụng Print Service (external)

---

## 🔄 Luồng Hoạt Động Bán Hàng

### 1. Flow Bán Hàng Chuẩn

```
Order → Payment → Invoice (auto) → Print Jobs (auto) → Stock Update
```

### 2. Event-Driven Architecture

- **PaymentCompleted** → Tạo Invoice tự động
- **InvoiceCreated** → Gửi print jobs qua WebSocket
- **PrintRequested** → External Print Service nhận jobs

---

## 🖨️ Print Service API Endpoints

### Base URL

```
http://karinox-fnb.nam/api/print/
```

### Authentication

```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
karinox-app-id: karinox-app-print
```

### 📋 Available Endpoints

#### 1. In Phiếu Tạm Tính

```http
POST /api/print/provisional
Content-Type: application/json

{
  "order_id": 123
}
```

#### 2. In Hóa Đơn Chính Thức

```http
POST /api/print/invoice
Content-Type: application/json

{
  "order_id": 123
}
```

#### 3. In Phiếu Bếp

```http
POST /api/print/kitchen
Content-Type: application/json

{
  "order_id": 123
}
```

#### 4. In Nhãn Sản Phẩm

```http
POST /api/print/labels
Content-Type: application/json

{
  "order_id": 123
}
```

#### 5. Test In

```http
POST /api/print/test
Content-Type: application/json

{
  "printer_type": "invoice|kitchen|label",
  "test_content": "Test message"
}
```

#### 6. Xác Nhận In Thành Công

```http
POST /api/print/confirm
Content-Type: application/json

{
  "print_id": "print_1234567890_abc123"
}
```

#### 7. Báo Lỗi In

```http
POST /api/print/error
Content-Type: application/json

{
  "print_id": "print_1234567890_abc123",
  "error_message": "Printer offline"
}
```

---

## 🔌 WebSocket Integration

### WebSocket Server

```
ws://karinox-fnb.nam:6001/app/ed5zsi5ebpdmawcqbwva
```

### Channel Subscribe

```javascript
// Channel cho từng branch
channel: `print-branch-{branch_id}`;

// Event listeners
pusher.subscribe('print-branch-1').bind('PrintRequested', function (data) {
    handlePrintJob(data);
});
```

### Event Structure

```javascript
{
  "printData": {
    "type": "invoice|kitchen|other",
    "content": "Formatted print content",
    "metadata": {
      "order_id": 123,
      "order_code": "ORDER-001",
      "table_name": "Bàn 5",
      "total_amount": 150000,
      "payment_method": "cash",
      "items": [...],
      "print_type": "provisional|invoice|kitchen"
    }
  },
  "printId": "print_1234567890_abc123",
  "branchId": 1,
  "deviceId": "pos-station|kitchen-printer",
  "priority": "normal|high"
}
```

---

## 📊 Print Job Types & Content Format

### 1. Invoice Print (Hóa Đơn)

```javascript
{
  "type": "invoice",
  "content": "Hóa đơn #ORDER-001\n====================\nKhách hàng: Nguyễn Văn A\nBàn: 5\n...",
  "metadata": {
    "order_id": 123,
    "order_code": "ORDER-001",
    "customer_name": "Nguyễn Văn A",
    "table_name": "Bàn 5",
    "total_amount": 150000,
    "payment_method": "cash",
    "items": [...]
  }
}
```

### 2. Kitchen Ticket (Phiếu Bếp)

```javascript
{
  "type": "kitchen",
  "content": "Phiếu bếp #ORDER-001\n===================\nBàn: 5\n...",
  "metadata": {
    "order_id": 123,
    "order_code": "ORDER-001",
    "table_name": "Bàn 5",
    "items": [
      {
        "name": "Cà phê đen",
        "quantity": 2,
        "note": "Ít đường"
      }
    ],
    "special_instructions": "Giao nhanh"
  },
  "priority": "high"
}
```

### 3. Provisional (Tạm Tính)

```javascript
{
  "type": "other",
  "content": "Tạm tính #ORDER-001\n==================\n...",
  "metadata": {
    "print_type": "provisional",
    "order_id": 123,
    "order_code": "ORDER-001"
  }
}
```

---

## 🔧 Print Service Application Updates

### 1. Connection Setup

```javascript
// WebSocket connection
const pusher = new Pusher('ed5zsi5ebpdmawcqbwva', {
    wsHost: 'karinox-fnb.nam',
    wsPort: 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// Subscribe to branch channel
const channel = pusher.subscribe('print-branch-1');
```

### 2. Event Handling

```javascript
channel.bind('PrintRequested', function (data) {
    const { printData, printId, branchId, deviceId, priority } = data;

    // Route to appropriate printer
    switch (printData.type) {
        case 'invoice':
            printInvoice(printData, printId);
            break;
        case 'kitchen':
            printKitchen(printData, printId);
            break;
        case 'other':
            if (printData.metadata.print_type === 'provisional') {
                printProvisional(printData, printId);
            }
            break;
    }
});
```

### 3. Print Confirmation

```javascript
async function confirmPrint(printId) {
    try {
        await fetch('/api/print/confirm', {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
                'karinox-app-id': 'karinox-app-print',
            },
            body: JSON.stringify({ print_id: printId }),
        });
    } catch (error) {
        reportPrintError(printId, error.message);
    }
}

async function reportPrintError(printId, errorMessage) {
    await fetch('/api/print/error', {
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
            'karinox-app-id': 'karinox-app-print',
        },
        body: JSON.stringify({
            print_id: printId,
            error_message: errorMessage,
        }),
    });
}
```

---

## 🔐 Authentication

### JWT Token

```javascript
// Login to get token
const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        username: 'print_service_user',
        password: 'your_password',
    }),
});

const { access_token } = await response.json();
```

### Headers Required

```javascript
{
  'Authorization': `Bearer ${access_token}`,
  'Content-Type': 'application/json',
  'karinox-app-id': 'karinox-app-print'
}
```

---

## 📈 Monitoring & Debugging

### Print History Tracking

Tất cả print jobs được lưu vào bảng `print_histories`:

- `print_id` - Unique identifier
- `type` - invoice|kitchen|other
- `status` - requested|printed|failed
- `branch_id` - Chi nhánh
- `device_id` - Thiết bị in
- `error_message` - Lỗi nếu có

### API Response Format

```javascript
// Success
{
  "success": true,
  "message": "Print job created successfully",
  "data": {
    "print_id": "print_1234567890_abc123",
    "status": "requested"
  }
}

// Error
{
  "success": false,
  "message": "Order not found",
  "error": "Order with ID 999 does not exist"
}
```

---

## 🚀 Migration Checklist

### For Print Service Application:

1. **Update WebSocket Connection**

    - [ ] Change to new WebSocket server: `karinox-fnb.nam:6001`
    - [ ] Update app key: `ed5zsi5ebpdmawcqbwva`
    - [ ] Subscribe to `print-branch-{id}` channels

2. **Update API Endpoints**

    - [ ] Change base URL to `/api/print/`
    - [ ] Add required headers (`karinox-app-id`)
    - [ ] Update authentication flow

3. **Handle New Event Structure**

    - [ ] Parse new `PrintRequested` event format
    - [ ] Handle `printData.metadata` properly
    - [ ] Support priority levels

4. **Implement Feedback API**

    - [ ] Call `/api/print/confirm` after successful print
    - [ ] Call `/api/print/error` when print fails
    - [ ] Include proper `print_id` in requests

5. **Support New Print Types**
    - [ ] Distinguish between invoice/kitchen/provisional
    - [ ] Handle kitchen ticket priority (high)
    - [ ] Format content based on type

---

## 📞 Support

### Test Environment

- URL: `http://karinox-fnb.nam`
- WebSocket: `karinox-fnb.nam:6001`
- Test credentials: Contact development team

### Development Contact

- For technical issues with Print Service integration
- For WebSocket connection problems
- For API authentication issues

---

**📝 Last Updated:** October 21, 2025  
**🔄 Version:** 2.0 (Event-driven WebSocket Architecture)
