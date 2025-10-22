w# Hướng Dẫn Phát Triển Ứng Dụng Quản Lý Máy In

## 🎯 **Mục Tiêu**

Phát triển ứng dụng desktop/mobile quản lý máy in tại chi nhánh, kết nối với hệ thống F&B qua API và WebSocket để nhận lệnh in real-time.

## 📋 **Yêu Cầu Chức Năng**

### 🔌 **1. Kết Nối Ban Đầu**

- Nhập mã kết nối do admin cung cấp
- Xác thực và lấy thông tin chi nhánh
- Lưu cấu hình kết nối cho lần sau

### 🖨️ **2. Quản Lý Máy In**

- Phát hiện và kết nối máy in có sẵn
- Cấu hình loại máy in (hóa đơn, bếp, tem)
- Test máy in
- Theo dõi trạng thái máy in

### 📡 **3. Nhận Lệnh In Real-time**

- Kết nối WebSocket để nhận print jobs
- Xử lý các loại in: invoice, kitchen, label
- Queue và prioritize print jobs
- Auto-retry khi lỗi

### 📊 **4. Giám Sát & Báo Cáo**

- Dashboard hiện trạng thái
- Lịch sử in
- Thống kê theo ngày/tuần/tháng
- Cảnh báo lỗi

---

## 🔗 **API ENDPOINTS**

### **Base URL:** (được thiết lập trong env)

### 🔌 **Connect API**

```http
POST /connect
Content-Type: application/json

{
    "connection_code": "KARINOX001"
}
```

**Response Success:**

```json
{
    "success": true,
    "message": "Kết nối thành công",
    "data": {
        "branch_id": 2,
        "branch_name": "Karinox Coffee",
        "branch_address": "Lô TM27-1 Hoàng Diệu",
        "branch_phone": "0987654321",
        "websocket_url": "ws://your-domain.com:6001/app/app-key",
        "channel_name": "print-branch-2",
        "event_name": "print.requested"
    }
}
```

### ✅ **Confirm Print API**

```http
POST /confirm
Content-Type: application/json

{
    "print_id": "print_1729588800_abc123",
    "device_id": "receipt-printer-01",
    "status": "printed"
}
```

### ❌ **Report Error API**

```http
POST /error
Content-Type: application/json

{
    "print_id": "print_1729588800_abc123",
    "device_id": "receipt-printer-01",
    "error_type": "printer_offline",
    "error_message": "Máy in không phản hồi",
    "error_details": {
        "error_code": "E001",
        "timestamp": "2024-10-22T10:30:00Z"
    }
}
```

### 📊 **History API**

```http
GET /history?branch_id=2&limit=50&status=printed&from_date=2024-10-01
```

### 📈 **Stats API**

```http
GET /stats?branch_id=2&period=today
```

### 🧾 **Template Management API**

Ứng dụng quản lý máy in cần lấy danh sách các mẫu in có sẵn để có thể chọn mẫu phù hợp cho từng trường hợp in.

#### 1. Lấy danh sách templates:

```http
GET /api/print/templates?connection_code=KARINOX00001&type=invoice
```

**Response:**

```json
{
    "success": true,
    "data": {
        "branch": {
            "id": 2,
            "name": "Karinox Coffee",
            "connection_code": "KARINOX00001"
        },
        "templates": [
            {
                "id": 1,
                "name": "Hóa đơn A4 tiêu chuẩn",
                "type": "invoice",
                "is_default": true,
                "description": "Template hóa đơn in A4",
                "created_at": "2024-10-22 10:30:00"
            }
        ]
    }
}
```

#### 2. Lấy chi tiết template:

```http
GET /api/print/templates/1?connection_code=KARINOX00001
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Hóa đơn A4 tiêu chuẩn",
        "type": "invoice",
        "description": "Template hóa đơn in A4",
        "content": "=== {{branch_name}} ===\nHóa đơn: {{order_code}}\n...",
        "is_default": true,
        "settings": {},
        "created_at": "2024-10-22 10:30:00",
        "updated_at": "2024-10-22 10:30:00"
    }
}
```

#### 3. Lấy template mặc định theo loại:

```http
GET /api/print/templates/default?connection_code=KARINOX00001&type=invoice
```

#### 4. Lấy các loại template có sẵn:

```http
GET /api/print/templates/types?connection_code=KARINOX00001
```

**Response:**

```json
{
    "success": true,
    "data": {
        "branch": {
            "id": 2,
            "name": "Karinox Coffee",
            "connection_code": "KARINOX00001"
        },
        "types": [
            {
                "type": "invoice",
                "label": "Hóa đơn thanh toán"
            },
            {
                "type": "kitchen",
                "label": "Phiếu bếp"
            }
        ]
    }
}
```

````

---

## 🔌 **WebSocket Integration**

### **Kết Nối WebSocket**

```javascript
const ws = new WebSocket('ws://your-domain.com:6001/app/app-key');

// Subscribe to branch channel
ws.onopen = function () {
    console.log('WebSocket connected');

    // Subscribe to print channel
    const subscribeMessage = {
        event: 'pusher:subscribe',
        data: {
            channel: 'print-branch-2',
        },
    };
    ws.send(JSON.stringify(subscribeMessage));
};
````

### **Nhận Print Events**

```javascript
ws.onmessage = function (event) {
    const data = JSON.parse(event.data);

    if (data.event === 'print.requested') {
        const printJob = data.data;
        handlePrintJob(printJob);
    }
};

function handlePrintJob(job) {
    console.log('New print job:', job);
    /*
    job = {
        print_id: "print_1729588800_abc123",
        type: "invoice",
        content: "=== HÓA ĐƠN ===\n...",
        metadata: {
            order_code: "ORD001",
            table: "Bàn 5"
        },
        priority: "normal",
        timestamp: "2024-10-22T10:30:00.000Z"
    }
    */

    // Add to print queue
    addToPrintQueue(job);
}
```

---

## 🏗️ **Kiến Trúc Ứng Dụng**

### **1. Technology Stack Đề Xuất**

**Desktop App:**

- **Electron + Vue.js/React** - Cross-platform
- **Tauri + Rust** - Lightweight, secure
- **Flutter Desktop** - Single codebase

**Mobile App:**

- **Flutter** - Cross-platform
- **React Native** - JavaScript
- **Xamarin** - C#

### **2. Core Modules**

```
PrintManagerApp/
├── src/
│   ├── connection/
│   │   ├── ConnectionManager.js
│   │   └── WebSocketClient.js
│   ├── printers/
│   │   ├── PrinterDiscovery.js
│   │   ├── PrinterManager.js
│   │   └── PrintJobProcessor.js
│   ├── api/
│   │   ├── ApiClient.js
│   │   └── ApiEndpoints.js
│   ├── ui/
│   │   ├── Dashboard.vue
│   │   ├── Settings.vue
│   │   └── History.vue
│   └── utils/
│       ├── Logger.js
│       └── Config.js
```

---

## 💻 **Sample Code Implementation**

### **1. Connection Manager**

```javascript
class ConnectionManager {
    constructor() {
        this.config = null;
        this.wsClient = null;
    }

    async connect(connectionCode) {
        try {
            const response = await fetch('/api/print/connect', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ connection_code: connectionCode }),
            });

            const result = await response.json();

            if (result.success) {
                this.config = result.data;
                await this.initWebSocket();
                this.saveConfig();
                return result;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Connection failed:', error);
            throw error;
        }
    }

    async initWebSocket() {
        this.wsClient = new WebSocketClient(this.config.websocket_url, this.config.channel_name, this.onPrintJobReceived.bind(this));
        await this.wsClient.connect();
    }

    onPrintJobReceived(printJob) {
        PrintJobProcessor.addJob(printJob);
    }
}
```

### **2. Print Job Processor**

```javascript
class PrintJobProcessor {
    constructor() {
        this.queue = [];
        this.processing = false;
    }

    static addJob(job) {
        // Add to queue with priority
        this.queue.push(job);
        this.queue.sort((a, b) => {
            const priorities = { high: 3, normal: 2, low: 1 };
            return priorities[b.priority] - priorities[a.priority];
        });

        this.processQueue();
    }

    static async processQueue() {
        if (this.processing || this.queue.length === 0) return;

        this.processing = true;

        while (this.queue.length > 0) {
            const job = this.queue.shift();
            await this.processJob(job);
        }

        this.processing = false;
    }

    static async processJob(job) {
        try {
            // Send to printer
            const success = await PrinterManager.print(job);

            if (success) {
                // Confirm success
                await this.confirmPrint(job.print_id, 'printed');
            } else {
                throw new Error('Print failed');
            }
        } catch (error) {
            // Report error
            await this.reportError(job.print_id, error.message);
        }
    }

    static async confirmPrint(printId, status) {
        await fetch('/api/print/confirm', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                print_id: printId,
                device_id: 'main-printer',
                status: status,
            }),
        });
    }

    static async reportError(printId, errorMessage) {
        await fetch('/api/print/error', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                print_id: printId,
                device_id: 'main-printer',
                error_type: 'print_failed',
                error_message: errorMessage,
            }),
        });
    }
}
```

### **3. Printer Manager**

```javascript
class PrinterManager {
    constructor() {
        this.printers = new Map();
        this.defaultPrinter = null;
    }

    async discoverPrinters() {
        // Platform-specific printer discovery
        // Windows: Use WMI
        // macOS: Use CUPS
        // Linux: Use CUPS

        const printers = await this.platformDiscoverPrinters();

        printers.forEach((printer) => {
            this.printers.set(printer.id, printer);
        });

        return Array.from(this.printers.values());
    }

    async print(job) {
        const printer = this.getPrinterForJobType(job.type);

        if (!printer) {
            throw new Error(`No printer configured for type: ${job.type}`);
        }

        return await this.sendToPrinter(printer, job.content);
    }

    getPrinterForJobType(type) {
        // Map job types to printers
        const typeMapping = {
            invoice: 'receipt-printer',
            kitchen: 'kitchen-printer',
            label: 'label-printer',
        };

        const printerType = typeMapping[type] || 'receipt-printer';
        return this.printers.get(printerType) || this.defaultPrinter;
    }

    async sendToPrinter(printer, content) {
        // Platform-specific printing
        // Use system print APIs or printer-specific protocols
        try {
            // Example for thermal printers
            await this.sendToThermalPrinter(printer, content);
            return true;
        } catch (error) {
            console.error('Print failed:', error);
            return false;
        }
    }
}
```

---

## 🎨 **UI/UX Guidelines**

### **Dashboard Layout**

```
┌─────────────────────────────────────┐
│ 📊 Karinox Coffee - Dashboard       │
├─────────────────────────────────────┤
│ 🟢 Connected | 📡 WebSocket: Active │
├─────────────────────────────────────┤
│ 🖨️ Printers Status:                │
│   ✅ Receipt Printer (Ready)        │
│   ✅ Kitchen Printer (Ready)        │
│   ❌ Label Printer (Offline)        │
├─────────────────────────────────────┤
│ 📈 Today Stats:                     │
│   📄 Printed: 45 | ❌ Failed: 2    │
│   ⏱️ Avg Time: 3.2s                │
├─────────────────────────────────────┤
│ 🔄 Recent Jobs:                     │
│   [10:30] Invoice #ORD001 ✅        │
│   [10:25] Kitchen #ORD002 ✅        │
│   [10:20] Label #ORD003 ❌          │
└─────────────────────────────────────┘
```

### **Settings Screen**

- Connection settings
- Printer configuration
- Auto-retry settings
- Log level settings
- Export settings

---

## 🔧 **Configuration**

### **Server Configuration (.env)**

```bash
# Domain configuration
APP_URL=https://your-domain.com
REVERB_HOST="your-domain.com"
REVERB_PORT=6001
REVERB_SCHEME=https  # Use https for production
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

### **WebSocket URL Examples:**

- **Development:** `ws://localhost:6001/app/app-key`
- **Production HTTP:** `ws://your-domain.com:6001/app/app-key`
- **Production HTTPS:** `wss://your-domain.com:6001/app/app-key`

### **App Config (config.json)**

```json
{
    "connection": {
        "connection_code": "KARINOX001",
        "server_url": "http://your-domain.com/api",
        "websocket_url": "ws://your-domain.com:6001/app/app-key",
        "branch_id": 2,
        "channel_name": "print-branch-2"
    },
    "printers": {
        "receipt": {
            "name": "EPSON TM-T20",
            "type": "thermal",
            "width": 58
        },
        "kitchen": {
            "name": "Star TSP143",
            "type": "thermal",
            "width": 80
        }
    },
    "settings": {
        "auto_retry": true,
        "retry_count": 3,
        "retry_delay": 5000,
        "log_level": "info"
    }
}
```

---

## 🔒 **Security**

### **1. Connection Security**

- Validate connection code format
- Store config securely (encrypted)
- Use HTTPS for API calls
- WSS for WebSocket in production

### **2. Error Handling**

- Graceful WebSocket reconnection
- API timeout handling
- Printer error recovery
- Log sensitive data carefully

---

## 📦 **Deployment**

### **Desktop App Packaging**

```bash
# Electron
npm run build:electron

# Tauri
cargo tauri build

# Flutter Desktop
flutter build windows/macos/linux
```

### **Mobile App**

```bash
# Flutter Mobile
flutter build apk/ios

# React Native
npx react-native run-android/ios
```

---

## 🧪 **Testing**

### **Test Connection**

```javascript
// Test với mã kết nối
const testCodes = ['KARINOX001', 'PIPPYKIDS001', 'PLAYGROUND01'];

testCodes.forEach(async (code) => {
    try {
        const result = await ConnectionManager.connect(code);
        console.log(`✅ ${code}: Connected to ${result.data.branch_name}`);
    } catch (error) {
        console.log(`❌ ${code}: ${error.message}`);
    }
});
```

---

## 📚 **Tài Liệu Tham Khảo**

### **API Documentation**

- **Connect:** Kết nối với chi nhánh
- **Confirm:** Xác nhận in thành công
- **Error:** Báo lỗi in
- **History:** Lịch sử in
- **Stats:** Thống kê in

### **WebSocket Events**

- **Event:** `print.requested`
- **Channel:** `print-branch-{branch_id}`
- **Data:** Print job với content và metadata

### **Print Job Types**

- **invoice:** Hóa đơn thanh toán
- **kitchen:** Phiếu bếp
- **label:** Tem sản phẩm

---

## 🎯 **Next Steps**

1. **Setup Development Environment**
2. **Implement Connection Manager**
3. **Build WebSocket Client**
4. **Develop Printer Integration**
5. **Create UI Components**
6. **Add Error Handling**
7. **Testing & Debugging**
8. **Package & Deploy**

---

**🚀 Chúc bạn phát triển ứng dụng thành công!**
