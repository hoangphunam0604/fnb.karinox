# 🖨️ Karinox Print System - Event & Channel Specifications

## 📡 WebSocket Event Structure

### Event Name: `PrintRequested`

Broadcast name: `print.requested`

#### Channel Patterns

```javascript
// 1. Branch Channel (tất cả devices trong chi nhánh)
'print-branch-{branchId}';
// Ví dụ: print-branch-1, print-branch-2

// 2. Device Channel (device cụ thể)
'print-device-{deviceId}';
// Ví dụ: print-device-pos-station, print-device-kitchen-printer
```

## 🔥 Event Data Structure

### PrintRequested Event Payload

```javascript
{
  "print_id": "print_1729547292_67166abc123def", // Unique ID
  "type": "invoice",                             // invoice|kitchen|label|receipt|other
  "content": "<HTML content to print>",          // Formatted content
  "metadata": {                                  // Additional data
    "order_id": 123,
    "order_code": "ORD-20241021-001",
    "customer_name": "Nguyễn Văn A",
    "table_name": "Bàn 5",
    "total_amount": 150000
  },
  "priority": "normal",                          // low|normal|high
  "timestamp": "2025-10-21T23:04:52.000Z"       // ISO timestamp
}
```

## 🎯 Trigger Scenarios

### 1. Thanh toán Order (OrderCompleted Event)

**Trigger:** Khi order được thanh toán thành công

**Events được broadcast:**

- 📄 **Invoice Print** → `print-branch-{branchId}`
- 🍳 **Kitchen Ticket** → `print-branch-{branchId}` (nếu có món cần vào bếp)
- 🏷️ **Product Labels** → `print-branch-{branchId}` (nếu có items)

**Listener:** `CreatePostPaymentPrintJobs`

### 2. Manual Print từ POS

**Routes:**

```php
POST /api/pos/orders/{id}/provisional  // In tạm tính
POST /api/pos/orders/{id}/invoice      // In hóa đơn
POST /api/pos/orders/{id}/kitchen      // In phiếu bếp
POST /api/pos/orders/{id}/labels       // In nhãn
POST /api/pos/orders/{id}/auto-print   // In tự động
```

**Target Channel:** `print-device-{deviceId}` hoặc `print-branch-{branchId}`

### 3. External Print Service

**Routes:**

```php
POST /api/print/provisional    // External service request
POST /api/print/invoice
POST /api/print/kitchen
POST /api/print/labels
POST /api/print/auto
```

**Target Channel:** Theo `device_id` được gửi trong request

## 🔧 Integration Code Examples

### JavaScript WebSocket Client

```javascript
// Connect to Reverb
const pusher = new Pusher('karinox-app-key', {
    wsHost: 'karinox-fnb.nam',
    wsPort: 6001,
    wssPort: 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    cluster: 'mt1',
});

// Subscribe to branch channel (all devices in branch)
const branchChannel = pusher.subscribe('print-branch-1');
branchChannel.bind('print.requested', function (data) {
    console.log('Print job received for branch:', data);
    processPrintJob(data);
});

// Subscribe to specific device channel
const deviceChannel = pusher.subscribe('print-device-pos-station');
deviceChannel.bind('print.requested', function (data) {
    console.log('Print job received for device:', data);
    processPrintJob(data);
});

function processPrintJob(printData) {
    // 1. Validate print job
    if (!printData.print_id || !printData.content) {
        console.error('Invalid print job:', printData);
        return;
    }

    // 2. Process based on type
    switch (printData.type) {
        case 'invoice':
            printInvoice(printData);
            break;
        case 'kitchen':
            printKitchenTicket(printData);
            break;
        case 'label':
            printProductLabel(printData);
            break;
        default:
            printGeneric(printData);
    }

    // 3. Confirm print completion
    confirmPrintCompletion(printData.print_id);
}

async function confirmPrintCompletion(printId) {
    try {
        await fetch('/api/print/confirm', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                print_id: printId,
                status: 'printed',
                device_id: 'pos-station',
            }),
        });
    } catch (error) {
        console.error('Failed to confirm print:', error);
    }
}
```

### C# Desktop Application

```csharp
using PusherClient;

public class PrintServiceClient
{
    private Pusher pusher;
    private Channel branchChannel;
    private Channel deviceChannel;

    public async Task InitializeAsync(string branchId, string deviceId)
    {
        var options = new PusherOptions
        {
            Host = "karinox-fnb.nam",
            Port = 6001,
            Encrypted = false
        };

        pusher = new Pusher("karinox-app-key", options);

        // Subscribe to branch channel
        branchChannel = await pusher.SubscribeAsync($"print-branch-{branchId}");
        branchChannel.Bind("print.requested", (PusherEvent evt) =>
        {
            var printData = JsonConvert.DeserializeObject<PrintRequestData>(evt.Data);
            ProcessPrintJob(printData);
        });

        // Subscribe to device channel
        deviceChannel = await pusher.SubscribeAsync($"print-device-{deviceId}");
        deviceChannel.Bind("print.requested", (PusherEvent evt) =>
        {
            var printData = JsonConvert.DeserializeObject<PrintRequestData>(evt.Data);
            ProcessPrintJob(printData);
        });

        await pusher.ConnectAsync();
    }

    private void ProcessPrintJob(PrintRequestData printData)
    {
        switch (printData.Type)
        {
            case "invoice":
                PrintInvoice(printData);
                break;
            case "kitchen":
                PrintKitchenTicket(printData);
                break;
            case "label":
                PrintProductLabel(printData);
                break;
        }

        // Confirm completion
        ConfirmPrintCompletion(printData.PrintId);
    }
}

public class PrintRequestData
{
    public string PrintId { get; set; }
    public string Type { get; set; }
    public string Content { get; set; }
    public Dictionary<string, object> Metadata { get; set; }
    public string Priority { get; set; }
    public DateTime Timestamp { get; set; }
}
```

## 📋 Print Types & Content Format

### 1. Invoice Print

```html
<div class="invoice">
    <h2>HÓA ĐƠN BÁN HÀNG</h2>
    <p>Mã đơn: ORD-20241021-001</p>
    <p>Bàn: Bàn 5</p>
    <p>Khách hàng: Nguyễn Văn A</p>
    <p>Thời gian: 21/10/2024 23:04</p>
    <hr />
    <table>
        <tr>
            <th>Món</th>
            <th>SL</th>
            <th>Giá</th>
            <th>Thành tiền</th>
        </tr>
        <tr>
            <td>Cà phê đen</td>
            <td>2</td>
            <td>25,000</td>
            <td>50,000</td>
        </tr>
        <tr>
            <td>Bánh mì</td>
            <td>1</td>
            <td>30,000</td>
            <td>30,000</td>
        </tr>
    </table>
    <hr />
    <p><strong>Tổng cộng: 80,000 VNĐ</strong></p>
    <p>Cảm ơn quý khách!</p>
</div>
```

### 2. Kitchen Ticket

```html
<div class="kitchen-ticket">
    <h2>PHIẾU BẾP</h2>
    <p>Đơn: ORD-20241021-001 | Bàn: Bàn 5</p>
    <p>Thời gian: 23:04:52</p>
    <hr />
    <ul>
        <li><strong>2x</strong> Cà phê đen</li>
        <li><strong>1x</strong> Bánh mì nướng</li>
    </ul>
    <hr />
    <p><strong>Ghi chú:</strong> Ít đường</p>
</div>
```

### 3. Product Label

```html
<div class="product-label">
    <h3>Cà phê đen</h3>
    <p>Bàn: 5 | Đơn: ORD-001</p>
    <p>Thời gian: 23:04</p>
    <p><strong>SL: 2</strong></p>
</div>
```

## 🔄 Print Flow Diagram

```
Order Payment
     ↓
OrderCompleted Event
     ↓
CreatePostPaymentPrintJobs Listener
     ↓
PrintService::printInvoiceViaSocket()
     ↓
PrintRequested Event Created
     ↓
Broadcast to Channels:
  - print-branch-{branchId}
  - print-device-{deviceId}
     ↓
Print Service Receives Event
     ↓
Process & Print Content
     ↓
Confirm via API: POST /api/print/confirm
```

## 🎛️ Configuration

### Reverb Server

- **Host:** karinox-fnb.nam
- **Port:** 6001
- **App Key:** karinox-app-key
- **Debug:** Enabled

### Database

- **Table:** print_histories
- **Status Values:** requested, printed, confirmed, failed
- **Types:** invoice, kitchen, label, receipt, other

## 🚀 Quick Test Commands

```bash
# Test PrintRequested Event
curl http://karinox-fnb.nam/test-print-event

# Test OrderCompleted Flow
curl http://karinox-fnb.nam/test-order-completed

# Check WebSocket Client
http://karinox-fnb.nam/reverb-test.html
```
