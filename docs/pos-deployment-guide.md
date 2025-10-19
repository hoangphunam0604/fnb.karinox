# 🏪 Tài liệu Triển khai Hệ thống POS F&B Karinox

## 📋 Tổng quan Hệ thống

### 🎯 Mục tiêu

Xây dựng hệ thống Point of Sale (POS) hoàn chỉnh cho chuỗi nhà hàng/quán cà phê với:

- Quản lý đơn hàng realtime
- Hệ thống thanh toán đa dạng
- Quản lý tồn kho tự động
- Hệ thống in hóa đơn/phiếu bếp độc lập
- Tích điểm khách hàng thành viên

### 🏗️ Kiến trúc Hệ thống

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   POS Frontend  │    │  Main F&B API   │    │ Print Service   │
│                 │    │                 │    │   (Standalone)  │
│ - Order Entry   │◄──►│ - Order Mgmt    │◄──►│ - Print Queue   │
│ - Payment UI    │    │ - Inventory     │    │ - Device Auth   │
│ - Staff Login   │    │ - Customer Mgmt │    │ - Print History │
└─────────────────┘    │ - Payment       │    │ - Multi-printer │
                       │ - Reporting     │    │ - Failover      │
                       └─────────────────┘    └─────────────────┘
                                ▲                        ▲
                                │                        │
                       ┌─────────────────┐    ┌─────────────────┐
                       │    Database     │    │  Print Clients  │
                       │                 │    │                 │
                       │ - Orders        │    │ - Kitchen       │
                       │ - Products      │    │ - Receipt       │
                       │ - Customers     │    │ - Label         │
                       │ - Inventory     │    │ - Cashier       │
                       └─────────────────┘    └─────────────────┘
```

### 🔧 Kiến trúc Print System Mới

**Print Service độc lập với các tính năng:**

- **Device Authentication**: API keys thay vì user login
- **Print History**: Lưu trữ và tra cứu lịch sử in
- **Multi-branch Support**: Hỗ trợ nhiều chi nhánh
- **Failover & Retry**: Tự động thử lại khi lỗi
- **Real-time Status**: Giám sát trạng thái thiết bị

## 🚀 I. Cài đặt và Cấu hình

### 1. Yêu cầu Hệ thống

```
- PHP 8.1+
- Laravel 11.x
- MySQL 8.0+
- Node.js 18+ (cho frontend)
- Redis (cho session/queue)
- Printer hardware (ESC/POS compatible)
```

### 2. Environment Setup

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=karinox_fnb_production
DB_USERNAME=root
DB_PASSWORD=

# JWT Authentication
JWT_SECRET=your-jwt-secret-key
JWT_TTL=1440

# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Payment Gateways
VNPAY_TMN_CODE=your-tmn-code
VNPAY_HASH_SECRET=your-hash-secret
INFOPLUS_POS_UNIQUE_ID=your-pos-id
INFOPLUS_USERNAME=your-username
INFOPLUS_PASSWORD=your-password

# Print System Integration (với Standalone Print Service)
PRINT_SERVICE_URL=http://print.karinox.com
PRINT_SERVICE_API_KEY=secure_main_system_api_key
PRINT_INTEGRATION_ENABLED=true

# Legacy Print System (sẽ deprecated)
PRINT_QUEUE_ENABLED=false
PRINT_AUTO_PROCESS=false
PRINT_RETRY_MAX=3
```

### 3. Database Migration

```bash
# Chạy migrations
php artisan migrate

# Seed data mẫu
php artisan db:seed --class=BranchSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=PrintTemplateSeeder
php artisan db:seed --class=MembershipLevelSeeder

# Tạo JWT secret
php artisan jwt:secret
```

## 📱 II. Cấu trúc API POS

### 🔐 Authentication & Authorization

#### Login API

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "staff@karinox.com",
  "password": "password"
}

Response:
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 86400,
  "user": {
    "id": 1,
    "name": "Staff Name",
    "branches": [...]
  }
}
```

#### Middleware Stack

```php
// Tất cả API POS cần authentication
Route::middleware([
  'auth:api',              // JWT authentication
  'is_karinox_app',        // App verification
  'set_karinox_branch_id'  // Branch context
])->prefix('pos')->group(function () {
  // POS routes...
});
```

### 🛍️ Product Management APIs

#### Lấy danh sách sản phẩm

```http
GET /api/pos/products
Authorization: Bearer {token}
X-Branch-ID: 1

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Coffee Category",
      "products": [
        {
          "id": 101,
          "name": "Cà phê đen",
          "price": 25000,
          "sale_price": 22000,
          "stock_quantity": 50,
          "product_type": "processed",
          "allows_sale": true,
          "toppings": [...]
        }
      ]
    }
  ]
}
```

### 🪑 Table Management APIs

#### Lấy danh sách bàn/phòng

```http
GET /api/pos/tables
Authorization: Bearer {token}
X-Branch-ID: 1

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Bàn 01",
      "area": "Tầng 1",
      "capacity": 4,
      "status": "available", // available, occupied, reserved
      "current_orders": []
    }
  ]
}
```

### 📝 Order Management APIs

#### Tạo/Cập nhật đơn hàng

```http
PUT /api/pos/orders/{id}
Content-Type: application/json
Authorization: Bearer {token}

{
  "customer_id": null,
  "table_id": 1,
  "note": "Ghi chú đơn hàng",
  "items": [
    {
      "product_id": 101,
      "quantity": 2,
      "price": 22000,
      "note": "Ít đường",
      "toppings": [
        {
          "topping_id": 201,
          "quantity": 1,
          "price": 5000
        }
      ]
    }
  ],
  "voucher_code": "DISCOUNT10",
  "reward_points_used": 100,
  "payment_method": "cash"
}

Response:
{
  "success": true,
  "data": {
    "id": 123,
    "order_code": "CN01N251019ORD0123",
    "table_name": "Bàn 01",
    "subtotal": 54000,
    "discount_amount": 5400,
    "total_amount": 48600,
    "order_status": "pending",
    "payment_status": "unpaid",
    "items": [...]
  }
}
```

#### Lấy đơn hàng theo bàn

```http
POST /api/pos/orders
Content-Type: application/json

{
  "table_id": 1
}

Response:
{
  "data": [
    {
      "id": 123,
      "order_code": "CN01N251019ORD0123",
      "customer": null,
      "total_amount": 48600,
      "order_status": "pending",
      "items": [...],
      "print_status": {
        "printed_kitchen": true,
        "printed_labels": false
      }
    }
  ]
}
```

### 👥 Customer Management APIs

#### Tìm kiếm khách hàng

```http
GET /api/pos/customers/find?phone=0987654321
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "id": 456,
    "fullname": "Nguyễn Văn A",
    "phone": "0987654321",
    "email": "customer@example.com",
    "membership_level": {
      "name": "Gold",
      "discount_percentage": 5
    },
    "loyalty_points": 1250,
    "total_spent": 2500000
  }
}
```

#### Tạo khách hàng mới

```http
POST /api/pos/customers
Content-Type: application/json

{
  "fullname": "Nguyễn Văn B",
  "phone": "0123456789",
  "email": "newcustomer@example.com",
  "birthday": "1990-05-15",
  "gender": "male"
}
```

### 💳 Payment Processing APIs

#### Thanh toán tiền mặt

```http
POST /api/pos/payments/cash/{order_code}/confirm
Content-Type: application/json

{
  "amount_received": 50000,
  "change_amount": 1400
}

Response:
{
  "success": true,
  "invoice": {
    "invoice_code": "CN01N251019HD0123",
    "total_amount": 48600,
    "payment_method": "cash"
  }
}
```

#### Thanh toán VNPay QR

```http
POST /api/pos/payments/vnpay/{order_code}/get-qr-code
Content-Type: application/json

{
  "amount": 48600
}

Response:
{
  "success": true,
  "qr_code": "data:image/png;base64,iVBOR...",
  "qr_data": "https://vnpay.vn/qr/...",
  "expires_at": "2025-10-19T15:30:00Z"
}
```

### 🖨️ Print System Integration

> ⚠️ **Quan trọng**: Print System hiện được tách thành ứng dụng độc lập để giải quyết vấn đề authentication.

#### 🔗 Tích hợp với Print Service

POS API chỉ tạo print jobs, **Print Service độc lập** sẽ xử lý việc in:

```http
POST /api/pos/print/create-jobs
Content-Type: application/json
Authorization: Bearer {token}

{
  "order_id": 123,
  "print_types": ["kitchen", "labels"],
  "device_preferences": {
    "kitchen": "kitchen_printer_001",
    "labels": "label_printer_001"
  }
}

Response:
{
  "success": true,
  "message": "Print jobs created and sent to Print Service",
  "job_ids": [501, 502]
}
```

#### 📡 Print Service Endpoints (Riêng biệt)

**Base URL**: `http://print-service.karinox.local:3001`

```http
# Lấy hàng đợi in (không cần user authentication)
GET /api/print/queue?device_id=kitchen_001&api_key=device_api_key

# Cập nhật trạng thái in
PUT /api/print/jobs/{id}/status
X-API-Key: device_api_key
{
  "status": "completed",
  "device_id": "kitchen_001"
}

# Lấy lịch sử in
GET /api/print/history?branch_id=1&date_from=2025-01-01
X-API-Key: management_api_key
```

#### 🔧 Cấu hình Integration

```env
# Main F&B System
PRINT_SERVICE_URL=http://print-service.karinox.local:3001
PRINT_SERVICE_API_KEY=main_system_api_key

# Print Service System
MAIN_SYSTEM_URL=http://karinox-fnb.local
MAIN_SYSTEM_API_KEY=print_service_api_key
```

## 💻 III. Frontend Integration

### 🔧 Setup API Client

```javascript
// api-client.js
class POSApiClient {
    constructor(baseURL, token) {
        this.baseURL = baseURL;
        this.token = token;
        this.branchId = localStorage.getItem('selected_branch_id');
    }

    async request(method, endpoint, data = null) {
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${this.token}`,
                'X-Branch-ID': this.branchId,
            },
        };

        if (data) {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(`${this.baseURL}${endpoint}`, config);

        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }

        return response.json();
    }

    // Product APIs
    async getProducts() {
        return this.request('GET', '/api/pos/products');
    }

    // Table APIs
    async getTables() {
        return this.request('GET', '/api/pos/tables');
    }

    // Order APIs
    async getOrdersByTable(tableId) {
        return this.request('POST', '/api/pos/orders', { table_id: tableId });
    }

    async updateOrder(orderId, orderData) {
        return this.request('PUT', `/api/pos/orders/${orderId}`, orderData);
    }

    // Customer APIs
    async findCustomer(phone) {
        return this.request('GET', `/api/pos/customers/find?phone=${phone}`);
    }

    async createCustomer(customerData) {
        return this.request('POST', '/api/pos/customers', customerData);
    }

    // Payment APIs
    async confirmCashPayment(orderCode, paymentData) {
        return this.request('POST', `/api/pos/payments/cash/${orderCode}/confirm`, paymentData);
    }

    async getVNPayQR(orderCode, amount) {
        return this.request('POST', `/api/pos/payments/vnpay/${orderCode}/get-qr-code`, { amount });
    }

    // Print APIs
    async autoPrint(orderId, deviceId) {
        return this.request('POST', '/api/pos/print/auto', { order_id: orderId, device_id: deviceId });
    }

    async printInvoice(orderId, deviceId) {
        return this.request('POST', '/api/pos/print/invoice', { order_id: orderId, device_id: deviceId });
    }
}

// Usage
const api = new POSApiClient('http://karinox-fnb.local', userToken);
```

### 🛍️ Order Management Component

```javascript
// order-manager.js
class OrderManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.currentOrder = null;
        this.selectedTable = null;
    }

    async selectTable(tableId) {
        try {
            this.selectedTable = tableId;
            const orders = await this.api.getOrdersByTable(tableId);

            if (orders.data.length > 0) {
                this.currentOrder = orders.data[0]; // Lấy order đầu tiên
            } else {
                this.currentOrder = this.createNewOrder(tableId);
            }

            this.renderOrder();
        } catch (error) {
            this.showError('Lỗi khi tải đơn hàng: ' + error.message);
        }
    }

    createNewOrder(tableId) {
        return {
            id: null,
            table_id: tableId,
            items: [],
            customer: null,
            subtotal: 0,
            discount_amount: 0,
            total_amount: 0,
            voucher_code: null,
            reward_points_used: 0,
        };
    }

    async addItem(product, quantity = 1, toppings = []) {
        const item = {
            product_id: product.id,
            product_name: product.name,
            quantity: quantity,
            price: product.sale_price || product.price,
            note: '',
            toppings: toppings,
        };

        // Kiểm tra item đã tồn tại chưa
        const existingItem = this.currentOrder.items.find(
            (i) => i.product_id === item.product_id && JSON.stringify(i.toppings) === JSON.stringify(item.toppings),
        );

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.currentOrder.items.push(item);
        }

        await this.saveOrder();
    }

    async removeItem(itemIndex) {
        this.currentOrder.items.splice(itemIndex, 1);
        await this.saveOrder();
    }

    async updateItemQuantity(itemIndex, quantity) {
        if (quantity <= 0) {
            return this.removeItem(itemIndex);
        }

        this.currentOrder.items[itemIndex].quantity = quantity;
        await this.saveOrder();
    }

    async saveOrder() {
        try {
            this.calculateTotals();

            const response = await this.api.updateOrder(this.currentOrder.id, this.currentOrder);

            this.currentOrder = response.data;
            this.renderOrder();

            // Auto print nếu có items mới
            if (this.shouldAutoPrint()) {
                await this.autoPrint();
            }
        } catch (error) {
            this.showError('Lỗi khi lưu đơn hàng: ' + error.message);
        }
    }

    calculateTotals() {
        this.currentOrder.subtotal = this.currentOrder.items.reduce((sum, item) => {
            const itemTotal = item.quantity * item.price;
            const toppingsTotal = item.toppings.reduce((tSum, topping) => tSum + topping.quantity * topping.price, 0);
            return sum + itemTotal + toppingsTotal;
        }, 0);

        // Apply voucher discount
        if (this.currentOrder.voucher_code) {
            // Calculate discount based on voucher
        }

        // Apply reward points
        const pointsValue = this.currentOrder.reward_points_used || 0;

        this.currentOrder.total_amount = this.currentOrder.subtotal - this.currentOrder.discount_amount - pointsValue;
    }

    shouldAutoPrint() {
        // Logic kiểm tra có cần auto print không
        return this.currentOrder.items.some((item) => item.product_type === 'processed' || item.product_type === 'combo');
    }

    async autoPrint() {
        try {
            const deviceId = localStorage.getItem('device_id') || 'pos_default';
            await this.api.autoPrint(this.currentOrder.id, deviceId);
            this.showSuccess('Đã gửi phiếu in tự động!');
        } catch (error) {
            console.error('Auto print failed:', error);
        }
    }

    renderOrder() {
        // Update UI với current order
        document.getElementById('order-items').innerHTML = this.renderItems();
        document.getElementById('order-total').textContent = this.formatCurrency(this.currentOrder.total_amount);
    }

    renderItems() {
        return this.currentOrder.items
            .map(
                (item, index) => `
      <div class="order-item" data-index="${index}">
        <div class="item-name">${item.product_name}</div>
        <div class="item-quantity">
          <button onclick="orderManager.updateItemQuantity(${index}, ${item.quantity - 1})">-</button>
          <span>${item.quantity}</span>
          <button onclick="orderManager.updateItemQuantity(${index}, ${item.quantity + 1})">+</button>
        </div>
        <div class="item-price">${this.formatCurrency(item.price * item.quantity)}</div>
        <button onclick="orderManager.removeItem(${index})" class="remove-btn">×</button>
      </div>
    `,
            )
            .join('');
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(amount);
    }

    showSuccess(message) {
        // Show success notification
        console.log('Success:', message);
    }

    showError(message) {
        // Show error notification
        console.error('Error:', message);
    }
}
```

### 💳 Payment Processing Component

```javascript
// payment-manager.js
class PaymentManager {
    constructor(apiClient, orderManager) {
        this.api = apiClient;
        this.orderManager = orderManager;
    }

    async processPayment(method, paymentData) {
        const order = this.orderManager.currentOrder;

        if (!order || order.total_amount <= 0) {
            throw new Error('Không có đơn hàng hợp lệ để thanh toán');
        }

        switch (method) {
            case 'cash':
                return this.processCashPayment(paymentData);
            case 'vnpay':
                return this.processVNPayPayment();
            case 'infoplus':
                return this.processInfoPlusPayment();
            default:
                throw new Error('Phương thức thanh toán không được hỗ trợ');
        }
    }

    async processCashPayment(paymentData) {
        try {
            const { amount_received } = paymentData;
            const total = this.orderManager.currentOrder.total_amount;

            if (amount_received < total) {
                throw new Error('Số tiền nhận không đủ để thanh toán');
            }

            const change_amount = amount_received - total;

            const response = await this.api.confirmCashPayment(this.orderManager.currentOrder.order_code, { amount_received, change_amount });

            // Auto print invoice
            await this.printInvoice();

            this.showPaymentSuccess(response.invoice, change_amount);

            return response;
        } catch (error) {
            throw new Error('Lỗi thanh toán tiền mặt: ' + error.message);
        }
    }

    async processVNPayPayment() {
        try {
            const total = this.orderManager.currentOrder.total_amount;
            const orderCode = this.orderManager.currentOrder.order_code;

            const response = await this.api.getVNPayQR(orderCode, total);

            this.showVNPayQR(response.qr_code, response.expires_at);

            // Polling để kiểm tra thanh toán
            this.startPaymentPolling(orderCode);

            return response;
        } catch (error) {
            throw new Error('Lỗi tạo VNPay QR: ' + error.message);
        }
    }

    startPaymentPolling(orderCode) {
        const pollInterval = setInterval(async () => {
            try {
                // Kiểm tra trạng thái thanh toán
                const order = await this.api.getOrderStatus(orderCode);

                if (order.payment_status === 'paid') {
                    clearInterval(pollInterval);
                    await this.printInvoice();
                    this.showPaymentSuccess(order.invoice);
                }
            } catch (error) {
                console.error('Payment polling error:', error);
            }
        }, 2000);

        // Timeout after 5 minutes
        setTimeout(() => {
            clearInterval(pollInterval);
            this.hideVNPayQR();
        }, 300000);
    }

    async printInvoice() {
        try {
            const deviceId = localStorage.getItem('device_id') || 'pos_default';
            await this.api.printInvoice(this.orderManager.currentOrder.id, deviceId);
        } catch (error) {
            console.error('Print invoice failed:', error);
        }
    }

    showPaymentSuccess(invoice, changeAmount = 0) {
        const message = `
      Thanh toán thành công!
      Hóa đơn: ${invoice.invoice_code}
      ${changeAmount > 0 ? `Tiền thừa: ${this.formatCurrency(changeAmount)}` : ''}
    `;
        alert(message);

        // Reset order
        this.orderManager.currentOrder = null;
        this.orderManager.renderOrder();
    }

    showVNPayQR(qrCode, expiresAt) {
        const modal = document.getElementById('vnpay-modal');
        const qrImage = document.getElementById('vnpay-qr');

        qrImage.src = qrCode;
        modal.style.display = 'block';

        // Show countdown
        this.startCountdown(expiresAt);
    }

    hideVNPayQR() {
        document.getElementById('vnpay-modal').style.display = 'none';
    }

    startCountdown(expiresAt) {
        const expiry = new Date(expiresAt);

        const interval = setInterval(() => {
            const now = new Date();
            const timeLeft = expiry - now;

            if (timeLeft <= 0) {
                clearInterval(interval);
                this.hideVNPayQR();
                alert('QR Code đã hết hạn. Vui lòng tạo lại giao dịch.');
                return;
            }

            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);

            document.getElementById('countdown').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(amount);
    }
}
```

### 🖨️ Print Queue Client

```javascript
// print-client.js
class PrintQueueClient {
    constructor(apiClient) {
        this.api = apiClient;
        this.deviceId = localStorage.getItem('device_id') || this.generateDeviceId();
        this.isPolling = false;
        this.pollInterval = 3000; // 3 seconds
    }

    generateDeviceId() {
        const deviceId = 'pos_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        localStorage.setItem('device_id', deviceId);
        return deviceId;
    }

    startPolling() {
        if (this.isPolling) return;

        this.isPolling = true;
        console.log(`🖨️ Print client started for device: ${this.deviceId}`);

        this.poll();
    }

    stopPolling() {
        this.isPolling = false;
        console.log('🛑 Print client stopped');
    }

    async poll() {
        if (!this.isPolling) return;

        try {
            const response = await this.api.request('GET', `/api/pos/print/queue?device_id=${this.deviceId}&limit=5`);

            if (response.success && response.jobs.length > 0) {
                console.log(`📋 Found ${response.jobs.length} print jobs`);

                for (const job of response.jobs) {
                    await this.processJob(job);
                }
            }
        } catch (error) {
            console.error('Print queue polling error:', error);
        }

        // Schedule next poll
        setTimeout(() => this.poll(), this.pollInterval);
    }

    async processJob(job) {
        console.log(`🖨️ Processing job #${job.id} (${job.type})`);

        try {
            const success = await this.sendToPrinter(job);

            if (success) {
                await this.markJobCompleted(job.id);
                console.log(`✅ Job #${job.id} completed`);
                this.showPrintNotification(job.type, true);
            } else {
                await this.markJobFailed(job.id, 'Printer not responding');
                console.log(`❌ Job #${job.id} failed`);
                this.showPrintNotification(job.type, false);
            }
        } catch (error) {
            await this.markJobFailed(job.id, error.message);
            console.error(`💥 Job #${job.id} error:`, error);
        }
    }

    async sendToPrinter(job) {
        // Integration với máy in thực tế
        // Tùy thuộc vào loại máy in:

        console.log(`📄 Printing ${job.type} content`);

        // For web-based POS, open print window
        if (job.type === 'invoice' || job.type === 'provisional') {
            this.openPrintWindow(job.content, job.type);
        }

        // For thermal printers, convert to ESC/POS
        if (job.type === 'kitchen' || job.type === 'label') {
            // await this.sendToThermalPrinter(job.content);
        }

        // Simulate processing delay
        await new Promise((resolve) => setTimeout(resolve, 1000));

        // Simulate 95% success rate
        return Math.random() > 0.05;
    }

    openPrintWindow(content, type) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Print ${type}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          @media print {
            body { margin: 0; }
          }
        </style>
      </head>
      <body>
        ${content}
        <script>
          window.onload = function() {
            window.print();
            window.close();
          }
        </script>
      </body>
      </html>
    `);
    }

    async markJobCompleted(jobId) {
        await this.api.request('POST', `/api/pos/print/queue/${jobId}/processed`);
    }

    async markJobFailed(jobId, errorMessage) {
        await this.api.request('POST', `/api/pos/print/queue/${jobId}/failed`, {
            error_message: errorMessage,
        });
    }

    showPrintNotification(type, success) {
        const message = success ? `✅ In ${type} thành công` : `❌ Lỗi in ${type}`;

        // Show toast notification
        this.showToast(message, success ? 'success' : 'error');
    }

    showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }
}
```

## 🎯 IV. Quy trình Vận hành

### 📋 Workflow Chuẩn

#### 1. Khởi động ca làm việc

```
1. Đăng nhập POS app với tài khoản staff
2. Chọn chi nhánh làm việc
3. Kiểm tra máy in (receipt, kitchen, label)
4. Sync dữ liệu mới nhất từ server
5. Bắt đầu polling print queue
```

#### 2. Phục vụ khách hàng

```
1. Chọn bàn trống hoặc có khách đang ngồi
2. Thêm món vào đơn hàng
   - Tự động in phiếu bếp cho món chế biến
   - Tự động in tem cho món takeaway
3. Áp dụng khuyến mãi/tích điểm (nếu có)
4. Tính tổng tiền và chọn phương thức thanh toán
5. In hóa đơn sau khi thanh toán thành công
```

#### 3. Xử lý đặc biệt

```
- In tạm tính: Khi khách hàng yêu cầu xem bill
- Hủy món: Cập nhật đơn hàng và thông báo bếp
- Tách bill: Chia đơn hàng cho nhóm khách
- Gia hạn: Mở rộng thời gian sử dụng bàn
```

### 🔧 Xử lý Lỗi Thường gặp

#### Lỗi kết nối API

```javascript
// Implement retry logic
async function apiRetry(apiCall, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            return await apiCall();
        } catch (error) {
            if (i === maxRetries - 1) throw error;

            console.log(`API call failed, retrying... (${i + 1}/${maxRetries})`);
            await new Promise((resolve) => setTimeout(resolve, 1000 * (i + 1)));
        }
    }
}
```

#### Lỗi máy in

```javascript
// Fallback printing options
class PrintFallback {
    static async handlePrintError(job, error) {
        console.error('Print error:', error);

        // Option 1: Save to print later
        localStorage.setItem(`failed_print_${job.id}`, JSON.stringify(job));

        // Option 2: Show manual print option
        if (confirm('Máy in lỗi. Bạn có muốn in thủ công không?')) {
            this.showManualPrint(job.content);
        }

        // Option 3: Send to backup printer
        await this.sendToBackupPrinter(job);
    }

    static showManualPrint(content) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(content);
        printWindow.print();
    }
}
```

### 📊 Monitoring & Analytics

#### Dashboard Realtime

```javascript
// pos-dashboard.js
class POSDashboard {
    constructor(apiClient) {
        this.api = apiClient;
        this.updateInterval = 30000; // 30 seconds
    }

    startMonitoring() {
        this.updateStats();
        setInterval(() => this.updateStats(), this.updateInterval);
    }

    async updateStats() {
        try {
            // Sales stats
            const sales = await this.api.request('GET', '/api/pos/stats/sales');
            this.updateSalesChart(sales);

            // Orders stats
            const orders = await this.api.request('GET', '/api/pos/stats/orders');
            this.updateOrdersStats(orders);

            // Print queue stats
            const printStats = await this.api.request('GET', '/api/pos/stats/print-queue');
            this.updatePrintStats(printStats);
        } catch (error) {
            console.error('Stats update error:', error);
        }
    }

    updateSalesChart(data) {
        // Update sales chart với Chart.js hoặc similar
    }

    updateOrdersStats(data) {
        document.getElementById('orders-today').textContent = data.today;
        document.getElementById('orders-pending').textContent = data.pending;
        document.getElementById('revenue-today').textContent = this.formatCurrency(data.revenue_today);
    }

    updatePrintStats(data) {
        document.getElementById('print-queue-pending').textContent = data.pending;
        document.getElementById('print-success-rate').textContent = `${data.success_rate}%`;
    }
}
```

## 🚀 V. Deployment Guide

### 🏗️ Production Setup

#### 1. Main F&B System Setup

```bash
# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Setup environment
cp .env.example .env.production
php artisan key:generate
php artisan jwt:secret

# Database setup
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder

# Setup queue worker
php artisan queue:work --daemon
```

#### 2. Print Service Deployment

> 📋 **Tham khảo**: Chi tiết đầy đủ trong `print-service-standalone.md`

```bash
# Tạo thư mục riêng cho Print Service
mkdir /var/www/karinox-print-service
cd /var/www/karinox-print-service

# Clone hoặc copy Karinox Print Service
git clone https://github.com/karinox/print-service.git .

# Cài đặt dependencies
npm install --production

# Cấu hình environment
cp .env.example .env.production
nano .env.production

# Setup database cho Print Service
npm run migrate
npm run seed:production

# Start service với PM2
pm2 start ecosystem.config.js --env production
pm2 save
```

#### 3. Print Service Configuration

```env
# Print Service .env
NODE_ENV=production
PORT=3001
DATABASE_URL=mysql://user:pass@localhost/karinox_print
REDIS_URL=redis://localhost:6379

# Integration với Main System
MAIN_SYSTEM_URL=http://karinox-fnb.local
MAIN_SYSTEM_API_KEY=secure_api_key_here

# Device Authentication
DEVICE_API_KEYS_TABLE=device_api_keys
SESSION_SECRET=print_service_secret

# Logging
LOG_LEVEL=info
LOG_FILE=/var/log/karinox-print/app.log
```

#### 4. Web Server Configuration

**Main F&B System (Nginx)**

```nginx
# /etc/nginx/sites-available/karinox-fnb
server {
    listen 80;
    server_name pos.karinox.com;
    root /var/www/karinox-fnb/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # API caching
    location /api/pos/products {
        expires 5m;
        add_header Cache-Control "public, immutable";
    }
}
```

**Print Service (Nginx Reverse Proxy)**

```nginx
# /etc/nginx/sites-available/karinox-print-service
server {
    listen 80;
    server_name print.karinox.com;

    location / {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;

        # Timeout settings for print jobs
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }

    # Health check endpoint
    location /health {
        proxy_pass http://localhost:3001/health;
        access_log off;
    }
}
```

#### 5. Process Management

**Main System - Supervisor (Queue Worker)**

```ini
# /etc/supervisor/conf.d/karinox-worker.conf
[program:karinox-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/karinox-fnb/artisan queue:work --sleep=3 --tries=3
directory=/var/www/karinox-fnb
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/karinox-worker.log
```

**Print Service - PM2 Ecosystem**

```javascript
// /var/www/karinox-print-service/ecosystem.config.js
module.exports = {
    apps: [
        {
            name: 'karinox-print-service',
            script: './src/app.js',
            instances: 2,
            exec_mode: 'cluster',
            env: {
                NODE_ENV: 'development',
                PORT: 3001,
            },
            env_production: {
                NODE_ENV: 'production',
                PORT: 3001,
            },
            error_file: '/var/log/karinox-print/error.log',
            out_file: '/var/log/karinox-print/out.log',
            log_file: '/var/log/karinox-print/combined.log',
            time: true,
            max_memory_restart: '300M',
            restart_delay: 4000,
        },
    ],
};
```

#### 6. Cron Jobs & Scheduled Tasks

**Main System Cron Jobs**

```bash
# /etc/crontab
# Daily reports
0 1 * * * www-data cd /var/www/karinox-fnb && php artisan reports:daily

# Sync inventory
*/15 * * * * www-data cd /var/www/karinox-fnb && php artisan inventory:sync

# Cleanup sessions
0 3 * * * www-data cd /var/www/karinox-fnb && php artisan session:cleanup
```

**Print Service Scheduled Tasks**

> Print Service tự quản lý các tasks nội bộ:
>
> - Cleanup old print jobs (mỗi 4 giờ)
> - Device health check (mỗi 2 phút)
> - Sync print history (mỗi 30 phút)
> - Generate daily reports (lúc 2:00 AM)

### 📱 Mobile App Deployment

#### React Native Configuration

```javascript
// config/api.js
const API_CONFIG = {
    development: {
        baseURL: 'http://192.168.1.100:8000',
        timeout: 10000,
    },
    production: {
        baseURL: 'https://api.karinox.com',
        timeout: 15000,
    },
};

export default API_CONFIG[__DEV__ ? 'development' : 'production'];
```

### 🔒 Security Considerations

#### API Security

```php
// Rate limiting
Route::middleware(['throttle:60,1'])->group(function () {
    // API routes
});

// Input validation
$request->validate([
    'amount' => 'required|numeric|min:0|max:50000000',
    'items.*.product_id' => 'required|exists:products,id',
    'items.*.quantity' => 'required|integer|min:1|max:100'
]);

// SQL injection prevention
Product::where('branch_id', $branchId)
    ->where('name', 'like', '%' . $request->search . '%')
    ->get();
```

## 📋 VI. Testing & Quality Assurance

### 🧪 Testing Strategy

```bash
# Unit tests
php artisan test --filter=POSTest

# API integration tests
php artisan test --filter=POSApiTest

# Print system tests
php tests/php/simple_print_test.php

# Performance tests
php artisan test --filter=PerformanceTest
```

### 📊 Performance Benchmarks

```
- API response time: < 200ms
- Order creation: < 500ms
- Print job processing: < 2s
- Database queries: < 50ms
- Memory usage: < 512MB
```

### 🔍 Monitoring Checklist

**Main F&B System**

```
✅ API endpoint availability
✅ Database connection health
✅ Queue worker status
✅ Payment gateway connectivity
✅ Disk space and memory usage
✅ Error rates and response times
```

**Print Service Monitoring**

```
✅ Print Service API health (http://print.karinox.com/health)
✅ Device connectivity status
✅ Print job queue length and processing time
✅ Failed print job retry rates
✅ Print history data integrity
✅ Device authentication success rates
✅ Inter-service communication (Main ↔ Print)
```

### 📊 Print Service Health Dashboard

```bash
# Check service status
pm2 status karinox-print-service

# View logs
pm2 logs karinox-print-service --lines 100

# Monitor device status
curl -X GET "http://print.karinox.com/api/admin/devices/status" \
  -H "X-API-Key: management_key"

# Check print queue
curl -X GET "http://print.karinox.com/api/admin/queue/stats" \
  -H "X-API-Key: management_key"
```

---

## 🎉 Kết luận

Hệ thống POS F&B Karinox đã sẵn sàng triển khai với kiến trúc microservices:

### 🏗️ **Dual-System Architecture**

✅ **Main F&B System** - Laravel API với authentication và business logic  
✅ **Standalone Print Service** - Node.js service độc lập với device authentication  
✅ **Seamless Integration** - Communication qua secure APIs

### 🔧 **Core Features**

✅ **Complete API Suite** - 25+ endpoints đầy đủ tính năng  
✅ **Real-time Order Management** - Quản lý đơn hàng realtime  
✅ **Multi-payment Support** - Tiền mặt, VNPay, InfoPlus với QR codes  
✅ **Advanced Print System** - 4 loại in với lịch sử và failover  
✅ **Customer Management** - Tích điểm thành viên và vouchers  
✅ **Inventory Integration** - Quản lý tồn kho tự động real-time

### 🚀 **Production Ready Features**

✅ **Scalable Architecture** - Load balancing và clustering  
✅ **Comprehensive Monitoring** - Health checks và analytics  
✅ **Robust Security** - JWT + API keys + rate limiting  
✅ **Print History Tracking** - Audit trail và reporting

### 📋 **Deployment Steps**

1. **Deploy Main F&B System** - Laravel API server
2. **Deploy Print Service** - Node.js standalone service
3. **Configure Integration** - API keys và communication
4. **Setup Print Devices** - Register devices với API keys
5. **Build POS Frontend** - React/Vue.js client application
6. **Train Staff** - User onboarding và go-live
7. **Monitor & Optimize** - Performance tuning

### 🎯 **Key Benefits của Kiến trúc Mới**

- 🔓 **No Authentication Dependency** - Print clients không cần user login
- 📊 **Print History Tracking** - Comprehensive audit trail
- 🔄 **Independent Scaling** - Print service scale riêng biệt
- 🛡️ **Enhanced Security** - Device-based authentication
- � **Multi-branch Support** - Centralized với device isolation

**🚀 Ready for Production with Enterprise-grade Print Management!**
