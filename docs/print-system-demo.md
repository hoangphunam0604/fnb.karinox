# 🚀 Demo: Sử dụng Hệ thống In trong thực tế

## Scenario 1: POS Application - Quy trình bán hàng với in tự động

```javascript
// 1. Khi khách hàng đặt món và confirm order
async function confirmOrder(orderId) {
    try {
        // Confirm order qua API
        const confirmResponse = await fetch('/api/pos/orders/' + orderId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: 'confirmed' }),
        });

        if (confirmResponse.ok) {
            // Tự động in phiếu bếp và tem (nếu cần)
            const printResponse = await fetch('/api/pos/print/auto', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: orderId,
                    device_id: getDeviceId(),
                }),
            });

            const printResult = await printResponse.json();

            if (printResult.success) {
                showNotification('Đã gửi phiếu in tới bếp và máy in tem!');
            }
        }
    } catch (error) {
        console.error('Lỗi khi xử lý đơn hàng:', error);
    }
}
```

## Scenario 2: Khách hàng yêu cầu xem tạm tính

```javascript
// 2. In phiếu tạm tính khi khách yêu cầu
async function printProvisionalBill(orderId) {
    try {
        const response = await fetch('/api/pos/print/provisional', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                device_id: 'receipt_printer_001',
            }),
        });

        const result = await response.json();

        if (result.success) {
            showNotification(`Phiếu tạm tính đã được in (Job #${result.print_job_id})`);
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Lỗi khi in tạm tính:', error);
    }
}
```

## Scenario 3: Thanh toán và in hóa đơn

```javascript
// 3. Sau khi thanh toán thành công
async function completePayment(orderId, paymentData) {
    try {
        // Xử lý thanh toán
        const paymentResponse = await fetch(`/api/pos/payments/cash/${orderId}/confirm`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(paymentData),
        });

        if (paymentResponse.ok) {
            // In hóa đơn chính thức
            const invoiceResponse = await fetch('/api/pos/print/invoice', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: orderId,
                    device_id: 'receipt_printer_001',
                }),
            });

            const invoiceResult = await invoiceResponse.json();

            if (invoiceResult.success) {
                showNotification('Hóa đơn đã được in thành công!');
            }
        }
    } catch (error) {
        console.error('Lỗi khi hoàn tất thanh toán:', error);
    }
}
```

## Scenario 4: Print Client - Lắng nghe và xử lý hàng đợi in

```javascript
// 4. Application client lắng nghe print queue
class PrintClient {
    constructor(deviceId, branchId = null) {
        this.deviceId = deviceId;
        this.branchId = branchId;
        this.isPolling = false;
        this.pollInterval = 3000; // 3 seconds
    }

    // Bắt đầu lắng nghe hàng đợi in
    startPolling() {
        if (this.isPolling) return;

        this.isPolling = true;
        console.log(`🖨️  Print client started for device: ${this.deviceId}`);

        this.poll();
    }

    // Dừng lắng nghe
    stopPolling() {
        this.isPolling = false;
        console.log('🛑 Print client stopped');
    }

    // Polling hàng đợi in
    async poll() {
        if (!this.isPolling) return;

        try {
            const params = new URLSearchParams({
                device_id: this.deviceId,
                limit: '5',
            });

            if (this.branchId) {
                params.append('branch_id', this.branchId);
            }

            const response = await fetch(`/api/pos/print/queue?${params}`);
            const result = await response.json();

            if (result.success && result.jobs.length > 0) {
                console.log(`📋 Found ${result.jobs.length} print jobs`);

                for (const job of result.jobs) {
                    await this.processJob(job);
                }
            }
        } catch (error) {
            console.error('Lỗi khi lấy print queue:', error);
        }

        // Schedule next poll
        setTimeout(() => this.poll(), this.pollInterval);
    }

    // Xử lý một print job
    async processJob(job) {
        console.log(`🖨️  Processing job #${job.id} (${job.type})`);

        try {
            // Simulate printing process
            const success = await this.sendToPrinter(job);

            if (success) {
                // Đánh dấu job đã hoàn thành
                await this.markJobCompleted(job.id);
                console.log(`✅ Job #${job.id} completed`);
            } else {
                // Đánh dấu job thất bại
                await this.markJobFailed(job.id, 'Printer not responding');
                console.log(`❌ Job #${job.id} failed`);
            }
        } catch (error) {
            await this.markJobFailed(job.id, error.message);
            console.error(`💥 Job #${job.id} error:`, error);
        }
    }

    // Gửi tới máy in thực tế
    async sendToPrinter(job) {
        // Tùy thuộc vào loại máy in:
        // - ESC/POS printer: convert HTML to ESC/POS commands
        // - Network printer: send direct to printer IP
        // - PDF: convert to PDF and print

        console.log(`   📄 Printing ${job.type} content (${job.content.length} chars)`);

        // Simulate printing delay
        await new Promise((resolve) => setTimeout(resolve, 1000));

        // Simulate 95% success rate
        return Math.random() > 0.05;
    }

    // Đánh dấu job hoàn thành
    async markJobCompleted(jobId) {
        await fetch(`/api/pos/print/queue/${jobId}/processed`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        });
    }

    // Đánh dấu job thất bại
    async markJobFailed(jobId, errorMessage) {
        await fetch(`/api/pos/print/queue/${jobId}/failed`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ error_message: errorMessage }),
        });
    }
}

// Sử dụng Print Client
const printClient = new PrintClient('pos_terminal_001', 1);
printClient.startPolling();
```

## Scenario 5: Kitchen Display System

```javascript
// 5. Hệ thống hiển thị bếp
class KitchenDisplay {
    constructor() {
        this.orders = new Map();
        this.setupEventListeners();
    }

    async loadPendingOrders() {
        try {
            const response = await fetch('/api/kitchen/orders/pending');
            const orders = await response.json();

            for (const order of orders) {
                this.displayOrder(order);
            }
        } catch (error) {
            console.error('Lỗi khi tải orders cho bếp:', error);
        }
    }

    displayOrder(order) {
        // Hiển thị order lên màn hình bếp
        console.log(`👨‍🍳 New kitchen order: ${order.order_code}`);

        // Tự động in phiếu bếp nếu chưa in
        if (!order.items.every((item) => item.printed_kitchen)) {
            this.printKitchenTickets(order.id);
        }
    }

    async printKitchenTickets(orderId) {
        try {
            const response = await fetch('/api/pos/print/kitchen', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: orderId,
                    device_id: 'kitchen_printer_001',
                }),
            });

            const result = await response.json();

            if (result.success) {
                console.log(`📄 Kitchen tickets printed for order ${orderId}`);
            }
        } catch (error) {
            console.error('Lỗi khi in phiếu bếp:', error);
        }
    }
}

const kitchenDisplay = new KitchenDisplay();
kitchenDisplay.loadPendingOrders();
```

## Scenario 6: Manager Dashboard - Theo dõi hệ thống in

```javascript
// 6. Dashboard quản lý hệ thống in
class PrintDashboard {
    constructor() {
        this.refreshInterval = 10000; // 10 seconds
        this.startMonitoring();
    }

    async startMonitoring() {
        setInterval(() => {
            this.updateStatistics();
            this.checkFailedJobs();
        }, this.refreshInterval);
    }

    async updateStatistics() {
        try {
            // Lấy thống kê từ tất cả orders
            const orders = await this.getRecentOrders();

            for (const order of orders) {
                const response = await fetch(`/api/pos/print/order/${order.id}/status`);
                const status = await response.json();

                this.updateOrderStatus(order.id, status.status);
            }
        } catch (error) {
            console.error('Lỗi khi cập nhật thống kê:', error);
        }
    }

    async checkFailedJobs() {
        try {
            // Kiểm tra jobs thất bại và retry
            const response = await fetch('/api/pos/print/queue?status=failed');
            const result = await response.json();

            if (result.jobs && result.jobs.length > 0) {
                console.warn(`⚠️  ${result.jobs.length} failed print jobs found`);

                // Auto retry failed jobs
                for (const job of result.jobs) {
                    if (job.retry_count < 3) {
                        await this.retryJob(job.id);
                    }
                }
            }
        } catch (error) {
            console.error('Lỗi khi kiểm tra failed jobs:', error);
        }
    }

    async retryJob(jobId) {
        try {
            await fetch(`/api/pos/print/queue/${jobId}/retry`, {
                method: 'POST',
            });
            console.log(`🔄 Retried failed job #${jobId}`);
        } catch (error) {
            console.error(`Lỗi khi retry job ${jobId}:`, error);
        }
    }

    async getRecentOrders() {
        const response = await fetch('/api/pos/orders?limit=50');
        return response.json();
    }

    updateOrderStatus(orderId, status) {
        // Cập nhật UI hiển thị trạng thái in của order
        console.log(`Order ${orderId} print status:`, status);
    }
}

const dashboard = new PrintDashboard();
```

## Cấu hình Environment cho Production

```env
# .env
# Print system settings
PRINT_QUEUE_ENABLED=true
PRINT_AUTO_PROCESS=true
PRINT_RETRY_MAX=3
PRINT_POLL_INTERVAL=5000

# Device configurations
RECEIPT_PRINTER_IP=192.168.1.100
KITCHEN_PRINTER_IP=192.168.1.101
LABEL_PRINTER_IP=192.168.1.102
```

## Crontab cho Auto Processing

```bash
# Xử lý print queue mỗi phút
* * * * * cd /path/to/project && php artisan print:process-queue --limit=50 >> /var/log/print-queue.log 2>&1

# Cleanup old jobs mỗi ngày
0 2 * * * cd /path/to/project && php artisan print:cleanup-old-jobs --days=7 >> /var/log/print-cleanup.log 2>&1
```
