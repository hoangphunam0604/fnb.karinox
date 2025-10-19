# 🖨️ Karinox Print Service - Standalone Application

## 📋 Tổng quan

**Karinox Print Service** là một ứng dụng độc lập để quản lý và xử lý tất cả các tác vụ in trong hệ thống F&B. Ứng dụng này hoạt động như một **microservice** riêng biệt, không phụ thuộc vào authentication của hệ thống chính.

### 🎯 Tính năng chính

- **Print Queue Management** - Quản lý hàng đợi in realtime
- **Multi-device Support** - Hỗ trợ nhiều máy in khác nhau
- **Print History Tracking** - Lịch sử in chi tiết
- **Device Status Monitoring** - Giám sát trạng thái thiết bị
- **Template Management** - Quản lý mẫu in
- **Auto Retry** - Tự động thử lại khi lỗi
- **Performance Analytics** - Thống kê hiệu suất

## 🏗️ Kiến trúc Hệ thống

### Architecture Overview

```
Main POS System → Print Service API → Print Clients
                      ↓
                 Print Queue DB ← Print History DB
                      ↓
              Device Management ← Template Engine
```

### Components

```
Print Service (Backend)
├── API Gateway (Express.js/Laravel)
├── Print Queue Manager
├── Device Registry
├── Template Engine
├── History Tracker
└── Performance Monitor

Print Client Apps (Frontend)
├── Desktop Client (Electron)
├── Web Client (React/Vue)
├── Mobile Client (React Native)
└── Hardware Integration
```

## 🚀 I. Print Service Backend Setup

### 1. Project Structure

```
karinox-print-service/
├── app/
│   ├── Controllers/
│   │   ├── QueueController.php
│   │   ├── DeviceController.php
│   │   ├── HistoryController.php
│   │   └── TemplateController.php
│   ├── Models/
│   │   ├── PrintJob.php
│   │   ├── PrintDevice.php
│   │   ├── PrintHistory.php
│   │   └── PrintTemplate.php
│   ├── Services/
│   │   ├── QueueService.php
│   │   ├── DeviceService.php
│   │   └── PrintProcessor.php
│   └── Middleware/
│       └── DeviceAuth.php
├── config/
│   ├── database.php
│   ├── devices.php
│   └── print.php
├── routes/
│   ├── api.php
│   └── web.php
├── database/
│   └── migrations/
└── public/
    └── client/ (Web client files)
```

### 2. Database Schema

#### Print Jobs Table

```sql
CREATE TABLE print_jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    job_id VARCHAR(255) UNIQUE NOT NULL,
    source_system VARCHAR(50) NOT NULL, -- 'karinox-pos', 'karinox-kitchen'
    source_id VARCHAR(255), -- order_id, invoice_id, etc.
    device_id VARCHAR(255),
    device_type ENUM('receipt', 'kitchen', 'label') NOT NULL,
    print_type ENUM('invoice', 'provisional', 'kitchen', 'label') NOT NULL,
    content LONGTEXT NOT NULL,
    metadata JSON,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    scheduled_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status_priority (status, priority),
    INDEX idx_device_status (device_id, status),
    INDEX idx_created_at (created_at),
    INDEX idx_source (source_system, source_id)
);
```

#### Print Devices Table

```sql
CREATE TABLE print_devices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    device_id VARCHAR(255) UNIQUE NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    device_type ENUM('receipt', 'kitchen', 'label') NOT NULL,
    branch_id INT,
    location VARCHAR(255), -- 'Counter 1', 'Kitchen Station 2'
    ip_address VARCHAR(45),
    port INT,
    connection_type ENUM('usb', 'network', 'bluetooth') NOT NULL,
    driver_type ENUM('escpos', 'cups', 'windows', 'raw') NOT NULL,
    settings JSON, -- printer-specific settings
    status ENUM('online', 'offline', 'error', 'maintenance') DEFAULT 'offline',
    last_ping TIMESTAMP NULL,
    last_job_at TIMESTAMP NULL,
    total_jobs INT DEFAULT 0,
    failed_jobs INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_device_type_branch (device_type, branch_id),
    INDEX idx_status (status),
    INDEX idx_active (is_active)
);
```

#### Print History Table

```sql
CREATE TABLE print_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    job_id VARCHAR(255) NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    device_name VARCHAR(255),
    print_type VARCHAR(50) NOT NULL,
    source_system VARCHAR(50) NOT NULL,
    source_id VARCHAR(255),
    branch_id INT,
    user_name VARCHAR(255), -- staff name from source system
    content_preview TEXT, -- first 500 chars
    file_size INT, -- bytes
    page_count INT DEFAULT 1,
    print_duration INT, -- milliseconds
    status ENUM('success', 'failed', 'cancelled') NOT NULL,
    error_message TEXT NULL,
    printed_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_printed_at (printed_at),
    INDEX idx_device_branch (device_id, branch_id),
    INDEX idx_source (source_system, source_id),
    INDEX idx_status (status)
);
```

### 3. Print Service API

#### Device Registration & Management

```php
// POST /api/devices/register
{
  "device_id": "printer_counter_01",
  "device_name": "Counter Printer 1",
  "device_type": "receipt",
  "branch_id": 1,
  "location": "Counter Station 1",
  "connection_type": "network",
  "driver_type": "escpos",
  "ip_address": "192.168.1.100",
  "port": 9100,
  "settings": {
    "paper_width": 80,
    "cut_mode": "full",
    "charset": "utf8"
  }
}

// GET /api/devices
// GET /api/devices/{device_id}/status
// PUT /api/devices/{device_id}/ping
// DELETE /api/devices/{device_id}
```

#### Job Management

```php
// GET /api/jobs/pending?device_id=printer_001&limit=10
{
  "success": true,
  "jobs": [
    {
      "job_id": "PJ_20251019_001234",
      "print_type": "invoice",
      "content": "...",
      "priority": "high",
      "metadata": {
        "order_id": 123,
        "customer": "Nguyễn Văn A"
      },
      "created_at": "2025-10-19T14:30:00Z"
    }
  ]
}

// POST /api/jobs/{job_id}/start
// POST /api/jobs/{job_id}/complete
// POST /api/jobs/{job_id}/fail
// POST /api/jobs/{job_id}/retry
```

#### History & Analytics

```php
// GET /api/history?device_id=printer_001&date=2025-10-19&limit=50
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "job_id": "PJ_20251019_001234",
      "print_type": "invoice",
      "device_name": "Counter Printer 1",
      "source_id": "ORDER_123",
      "user_name": "Nhân viên A",
      "status": "success",
      "print_duration": 2500,
      "printed_at": "2025-10-19T14:35:22Z"
    }
  ],
  "pagination": {
    "total": 156,
    "page": 1,
    "per_page": 50
  }
}

// GET /api/analytics/devices
// GET /api/analytics/performance
// GET /api/analytics/errors
```

## 💻 II. Print Client Applications

### 🖥️ Desktop Client (Electron App)

#### Features

```
✅ Auto-connect to Print Service
✅ Multi-printer management
✅ Real-time job notifications
✅ Print preview
✅ History viewer với filters
✅ Device diagnostics
✅ Offline job queuing
✅ Auto-update mechanism
```

#### Main Window Layout

```javascript
// main-window.js
const { app, BrowserWindow, ipcMain } = require('electron');
const PrintClient = require('./services/print-client');

class MainWindow {
    constructor() {
        this.window = null;
        this.printClient = new PrintClient();
        this.initializeWindow();
        this.setupEventHandlers();
    }

    initializeWindow() {
        this.window = new BrowserWindow({
            width: 1200,
            height: 800,
            title: 'Karinox Print Service',
            webPreferences: {
                nodeIntegration: true,
                contextIsolation: false,
            },
        });

        this.window.loadFile('renderer/index.html');
    }

    setupEventHandlers() {
        // Print job events
        ipcMain.on('start-print-service', () => {
            this.printClient.start();
        });

        ipcMain.on('stop-print-service', () => {
            this.printClient.stop();
        });

        // Device management
        ipcMain.handle('get-devices', () => {
            return this.printClient.getDevices();
        });

        ipcMain.handle('register-device', (event, deviceConfig) => {
            return this.printClient.registerDevice(deviceConfig);
        });

        // History
        ipcMain.handle('get-history', (event, filters) => {
            return this.printClient.getHistory(filters);
        });
    }
}

module.exports = MainWindow;
```

#### Print Client Service

```javascript
// services/print-client.js
const axios = require('axios');
const EventEmitter = require('events');

class PrintClient extends EventEmitter {
    constructor() {
        super();
        this.config = require('../config/print-service.json');
        this.devices = new Map();
        this.isRunning = false;
        this.pollingInterval = 3000;
    }

    async start() {
        if (this.isRunning) return;

        this.isRunning = true;
        console.log('🖨️ Print Client Started');

        // Load saved devices
        await this.loadDevices();

        // Start polling for jobs
        this.startPolling();

        // Register with print service
        await this.registerClient();
    }

    stop() {
        this.isRunning = false;
        console.log('🛑 Print Client Stopped');
    }

    async loadDevices() {
        try {
            const response = await axios.get(`${this.config.api_url}/api/devices`, {
                headers: {
                    'X-Client-ID': this.config.client_id,
                    'X-API-Key': this.config.api_key,
                },
            });

            response.data.devices.forEach((device) => {
                this.devices.set(device.device_id, device);
            });

            console.log(`📱 Loaded ${this.devices.size} devices`);
        } catch (error) {
            console.error('Failed to load devices:', error);
        }
    }

    async startPolling() {
        if (!this.isRunning) return;

        try {
            for (const [deviceId, device] of this.devices) {
                await this.pollDeviceJobs(deviceId);
            }
        } catch (error) {
            console.error('Polling error:', error);
        }

        // Schedule next poll
        setTimeout(() => this.startPolling(), this.pollingInterval);
    }

    async pollDeviceJobs(deviceId) {
        try {
            const response = await axios.get(`${this.config.api_url}/api/jobs/pending`, {
                params: { device_id: deviceId, limit: 10 },
                headers: {
                    'X-Client-ID': this.config.client_id,
                    'X-API-Key': this.config.api_key,
                },
            });

            if (response.data.jobs.length > 0) {
                console.log(`📋 Found ${response.data.jobs.length} jobs for ${deviceId}`);

                for (const job of response.data.jobs) {
                    await this.processJob(job, deviceId);
                }
            }
        } catch (error) {
            console.error(`Polling error for ${deviceId}:`, error);
        }
    }

    async processJob(job, deviceId) {
        try {
            console.log(`🖨️ Processing job ${job.job_id} on ${deviceId}`);

            // Mark job as started
            await this.updateJobStatus(job.job_id, 'processing');

            // Send to physical printer
            const success = await this.sendToPrinter(job, deviceId);

            if (success) {
                await this.updateJobStatus(job.job_id, 'completed');
                this.emit('job-completed', { job, deviceId });
            } else {
                await this.updateJobStatus(job.job_id, 'failed', 'Print failed');
                this.emit('job-failed', { job, deviceId });
            }
        } catch (error) {
            console.error(`Job processing error:`, error);
            await this.updateJobStatus(job.job_id, 'failed', error.message);
        }
    }

    async sendToPrinter(job, deviceId) {
        const device = this.devices.get(deviceId);

        if (!device) {
            throw new Error(`Device ${deviceId} not found`);
        }

        switch (device.driver_type) {
            case 'escpos':
                return this.sendToESCPOSPrinter(job, device);
            case 'cups':
                return this.sendToCUPSPrinter(job, device);
            case 'windows':
                return this.sendToWindowsPrinter(job, device);
            default:
                throw new Error(`Unsupported driver: ${device.driver_type}`);
        }
    }

    async sendToESCPOSPrinter(job, device) {
        const escpos = require('escpos');
        const net = require('net');

        return new Promise((resolve, reject) => {
            try {
                // Connect to network printer
                const networkDevice = new escpos.Network(device.ip_address, device.port);
                const printer = new escpos.Printer(networkDevice);

                networkDevice.open(() => {
                    // Convert HTML to ESC/POS commands
                    const commands = this.convertHTMLToESCPOS(job.content);

                    printer.raw(commands);
                    printer.cut();
                    printer.close();

                    resolve(true);
                });

                networkDevice.on('error', reject);
            } catch (error) {
                reject(error);
            }
        });
    }

    convertHTMLToESCPOS(html) {
        // Simple HTML to ESC/POS converter
        // In production, use proper HTML parser
        let commands = Buffer.alloc(0);

        // Initialize printer
        commands = Buffer.concat([commands, Buffer.from([0x1b, 0x40])]);

        // Set encoding UTF-8
        commands = Buffer.concat([commands, Buffer.from([0x1b, 0x74, 0x10])]);

        // Convert HTML content (simplified)
        const text = html.replace(/<[^>]*>/g, '\n').replace(/\n+/g, '\n');
        commands = Buffer.concat([commands, Buffer.from(text, 'utf8')]);

        // Cut paper
        commands = Buffer.concat([commands, Buffer.from([0x1d, 0x56, 0x00])]);

        return commands;
    }

    async updateJobStatus(jobId, status, errorMessage = null) {
        const data = { status };
        if (errorMessage) data.error_message = errorMessage;

        await axios.post(`${this.config.api_url}/api/jobs/${jobId}/${status}`, data, {
            headers: {
                'X-Client-ID': this.config.client_id,
                'X-API-Key': this.config.api_key,
            },
        });
    }

    async getHistory(filters = {}) {
        try {
            const response = await axios.get(`${this.config.api_url}/api/history`, {
                params: filters,
                headers: {
                    'X-Client-ID': this.config.client_id,
                    'X-API-Key': this.config.api_key,
                },
            });

            return response.data;
        } catch (error) {
            console.error('Failed to get history:', error);
            return { success: false, data: [] };
        }
    }
}

module.exports = PrintClient;
```

### 🌐 Web Client Interface

#### History Dashboard

```html
<!-- renderer/history.html -->
<!DOCTYPE html>
<html>
    <head>
        <title>Print History - Karinox Print Service</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    </head>
    <body class="bg-gray-100">
        <div id="app" class="container mx-auto p-6">
            <!-- Header -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow">
                <h1 class="text-2xl font-bold text-gray-800">📊 Print History</h1>
                <p class="text-gray-600">Lịch sử in và thống kê</p>
            </div>

            <!-- Filters -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Device</label>
                        <select id="device-filter" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All Devices</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Print Type</label>
                        <select id="type-filter" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All Types</option>
                            <option value="invoice">Invoice</option>
                            <option value="kitchen">Kitchen</option>
                            <option value="label">Label</option>
                            <option value="provisional">Provisional</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status-filter" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All Status</option>
                            <option value="success">Success</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" id="date-filter" class="mt-1 block w-full rounded-md border-gray-300" />
                    </div>
                </div>

                <div class="mt-4">
                    <button onclick="loadHistory()" class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700">🔍 Search</button>
                    <button onclick="exportHistory()" class="ml-2 rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700">
                        📄 Export CSV
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="rounded-lg bg-blue-500 p-2">
                            <span class="text-xl text-white">📄</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Jobs</p>
                            <p id="total-jobs" class="text-2xl font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="rounded-lg bg-green-500 p-2">
                            <span class="text-xl text-white">✅</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Success Rate</p>
                            <p id="success-rate" class="text-2xl font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="rounded-lg bg-yellow-500 p-2">
                            <span class="text-xl text-white">⚡</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Avg Duration</p>
                            <p id="avg-duration" class="text-2xl font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="rounded-lg bg-red-500 p-2">
                            <span class="text-xl text-white">❌</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Failed Jobs</p>
                            <p id="failed-jobs" class="text-2xl font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800">Print Jobs History</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Job ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="history-table" class="divide-y divide-gray-200 bg-white">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button
                            onclick="previousPage()"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Previous
                        </button>
                        <button
                            onclick="nextPage()"
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span id="showing-from">1</span> to <span id="showing-to">10</span> of
                                <span id="total-results">0</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm" id="pagination">
                                <!-- Dynamic pagination -->
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="js/history.js"></script>
    </body>
</html>
```

#### History JavaScript

```javascript
// renderer/js/history.js
class HistoryManager {
    constructor() {
        this.currentPage = 1;
        this.perPage = 50;
        this.currentFilters = {};
        this.init();
    }

    async init() {
        await this.loadDevices();
        await this.loadHistory();
        this.setupEventListeners();

        // Auto refresh every 30 seconds
        setInterval(() => this.loadHistory(), 30000);
    }

    setupEventListeners() {
        // Filter changes
        ['device-filter', 'type-filter', 'status-filter', 'date-filter'].forEach((id) => {
            document.getElementById(id).addEventListener('change', () => {
                this.currentPage = 1;
                this.loadHistory();
            });
        });
    }

    async loadDevices() {
        try {
            const devices = await window.electronAPI.getDevices();
            const deviceSelect = document.getElementById('device-filter');

            devices.forEach((device) => {
                const option = document.createElement('option');
                option.value = device.device_id;
                option.textContent = `${device.device_name} (${device.device_id})`;
                deviceSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load devices:', error);
        }
    }

    async loadHistory() {
        try {
            // Collect filters
            this.currentFilters = {
                device_id: document.getElementById('device-filter').value,
                print_type: document.getElementById('type-filter').value,
                status: document.getElementById('status-filter').value,
                date: document.getElementById('date-filter').value,
                page: this.currentPage,
                per_page: this.perPage,
            };

            // Remove empty filters
            Object.keys(this.currentFilters).forEach((key) => {
                if (!this.currentFilters[key]) delete this.currentFilters[key];
            });

            const response = await window.electronAPI.getHistory(this.currentFilters);

            if (response.success) {
                this.renderHistory(response.data);
                this.updateStats(response.stats);
                this.updatePagination(response.pagination);
            }
        } catch (error) {
            console.error('Failed to load history:', error);
        }
    }

    renderHistory(jobs) {
        const tbody = document.getElementById('history-table');
        tbody.innerHTML = '';

        if (jobs.length === 0) {
            tbody.innerHTML = `
        <tr>
          <td colspan="8" class="px-6 py-4 text-center text-gray-500">
            No print jobs found
          </td>
        </tr>
      `;
            return;
        }

        jobs.forEach((job) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';

            const statusColor = {
                success: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800',
                cancelled: 'bg-yellow-100 text-yellow-800',
            };

            row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
          ${this.formatDateTime(job.printed_at)}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
          ${job.job_id}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            ${job.print_type}
          </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
          ${job.device_name}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
          ${job.user_name || '-'}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColor[job.status]}">
            ${job.status}
          </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
          ${job.print_duration ? this.formatDuration(job.print_duration) : '-'}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
          <button onclick="viewJobDetails('${job.job_id}')" class="text-indigo-600 hover:text-indigo-900 mr-3">
            View
          </button>
          ${
              job.status === 'failed'
                  ? `
            <button onclick="retryJob('${job.job_id}')" class="text-green-600 hover:text-green-900">
              Retry
            </button>
          `
                  : ''
          }
        </td>
      `;

            tbody.appendChild(row);
        });
    }

    updateStats(stats) {
        document.getElementById('total-jobs').textContent = stats.total_jobs || 0;
        document.getElementById('success-rate').textContent = `${stats.success_rate || 0}%`;
        document.getElementById('avg-duration').textContent = stats.avg_duration ? this.formatDuration(stats.avg_duration) : '-';
        document.getElementById('failed-jobs').textContent = stats.failed_jobs || 0;
    }

    updatePagination(pagination) {
        document.getElementById('showing-from').textContent = pagination.from || 0;
        document.getElementById('showing-to').textContent = pagination.to || 0;
        document.getElementById('total-results').textContent = pagination.total || 0;

        // Update pagination buttons (implement as needed)
    }

    formatDateTime(datetime) {
        return new Date(datetime).toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    }

    formatDuration(ms) {
        if (ms < 1000) return `${ms}ms`;
        if (ms < 60000) return `${(ms / 1000).toFixed(1)}s`;
        return `${(ms / 60000).toFixed(1)}m`;
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.historyManager = new HistoryManager();
});

// Global functions for buttons
async function viewJobDetails(jobId) {
    const details = await window.electronAPI.getJobDetails(jobId);
    // Show modal with job details
    showJobDetailsModal(details);
}

async function retryJob(jobId) {
    if (confirm('Are you sure you want to retry this job?')) {
        await window.electronAPI.retryJob(jobId);
        window.historyManager.loadHistory();
    }
}

async function exportHistory() {
    const filters = window.historyManager.currentFilters;
    await window.electronAPI.exportHistory(filters);
}

function loadHistory() {
    window.historyManager.loadHistory();
}

function previousPage() {
    if (window.historyManager.currentPage > 1) {
        window.historyManager.currentPage--;
        window.historyManager.loadHistory();
    }
}

function nextPage() {
    window.historyManager.currentPage++;
    window.historyManager.loadHistory();
}
```

## 🔗 III. Integration với Main POS System

### Main System Integration

```php
// POS System - PrintServiceClient.php
class PrintServiceClient {
    private $baseUrl;
    private $apiKey;

    public function __construct() {
        $this->baseUrl = config('print_service.url');
        $this->apiKey = config('print_service.api_key');
    }

    public function submitPrintJob(array $jobData) {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'X-Source-System' => 'karinox-pos'
        ])->post("{$this->baseUrl}/api/jobs", [
            'job_id' => $this->generateJobId(),
            'source_system' => 'karinox-pos',
            'source_id' => $jobData['order_id'],
            'device_type' => $jobData['device_type'],
            'print_type' => $jobData['print_type'],
            'content' => $jobData['content'],
            'metadata' => $jobData['metadata'],
            'priority' => $jobData['priority'] ?? 'normal'
        ]);

        return $response->json();
    }

    public function getJobStatus($jobId) {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey
        ])->get("{$this->baseUrl}/api/jobs/{$jobId}/status");

        return $response->json();
    }

    private function generateJobId() {
        return 'PJ_' . date('Ymd_His') . '_' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
```

### Updated PrintService in Main System

```php
// Modified PrintService.php in main system
public function printInvoice(Order $order, ?string $deviceId = null)
{
    try {
        if ($order->payment_status !== 'paid') {
            return [
                'success' => false,
                'message' => 'Chỉ có thể in hóa đơn khi đơn hàng đã thanh toán'
            ];
        }

        $template = $this->getTemplate($order->branch_id, 'invoice');
        $content = $this->renderTemplate($template->content, $order);

        // Submit to Print Service instead of local queue
        $printClient = new PrintServiceClient();
        $result = $printClient->submitPrintJob([
            'order_id' => $order->id,
            'device_type' => 'receipt',
            'print_type' => 'invoice',
            'content' => $content,
            'metadata' => [
                'order_code' => $order->order_code,
                'customer_name' => $order->customer_name,
                'total_amount' => $order->total_amount,
                'staff_name' => $order->staff->name ?? ''
            ],
            'priority' => 'high'
        ]);

        if ($result['success']) {
            $order->update([
                'printed_bill' => true,
                'printed_bill_at' => now()
            ]);
        }

        return $result;

    } catch (\Exception $e) {
        Log::error("Failed to submit invoice print job: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Lỗi khi gửi job in hóa đơn: ' . $e->getMessage()
        ];
    }
}
```

## 📊 IV. Advanced Analytics & Reporting

### Print Performance Dashboard

```javascript
// Analytics Dashboard Component
class PrintAnalytics {
    async getDashboardData(timeRange = 'today') {
        const response = await fetch(`/api/analytics/dashboard?range=${timeRange}`);
        return response.json();
    }

    renderPerformanceChart(data) {
        // Chart.js implementation
        const ctx = document.getElementById('performanceChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Success Rate',
                        data: data.success_rates,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    },
                    {
                        label: 'Average Duration (ms)',
                        data: data.avg_durations,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    renderDeviceStatus(devices) {
        const container = document.getElementById('device-status');
        container.innerHTML = '';

        devices.forEach((device) => {
            const statusColor = device.status === 'online' ? 'green' : device.status === 'offline' ? 'red' : 'yellow';

            container.innerHTML += `
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-semibold">${device.device_name}</h3>
              <p class="text-sm text-gray-600">${device.location}</p>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-${statusColor}-500 mr-2"></div>
              <span class="text-sm capitalize">${device.status}</span>
            </div>
          </div>
          <div class="mt-2 text-xs text-gray-500">
            <p>Jobs today: ${device.jobs_today}</p>
            <p>Success rate: ${device.success_rate}%</p>
            <p>Last job: ${device.last_job_at || 'Never'}</p>
          </div>
        </div>
      `;
        });
    }
}
```

## 🚀 V. Deployment & Configuration

### Print Service Deployment

```dockerfile
# Dockerfile for Print Service
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 3000

CMD ["node", "server.js"]
```

### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'
services:
    print-service:
        build: .
        ports:
            - '3000:3000'
        environment:
            - NODE_ENV=production
            - DB_HOST=print-db
            - DB_USER=print_user
            - DB_PASSWORD=secure_password
            - API_KEY=your_secure_api_key
        depends_on:
            - print-db

    print-db:
        image: mysql:8.0
        environment:
            - MYSQL_DATABASE=karinox_print
            - MYSQL_USER=print_user
            - MYSQL_PASSWORD=secure_password
            - MYSQL_ROOT_PASSWORD=root_password
        volumes:
            - print_data:/var/lib/mysql

volumes:
    print_data:
```

### Installation Script

```bash
#!/bin/bash
# install-print-service.sh

echo "🖨️ Installing Karinox Print Service..."

# Create directories
mkdir -p /opt/karinox/print-service
mkdir -p /var/log/karinox/print-service

# Download and extract
curl -L https://releases.karinox.com/print-service/latest.tar.gz | tar -xz -C /opt/karinox/print-service

# Install dependencies
cd /opt/karinox/print-service
npm install --production

# Create service file
cat > /etc/systemd/system/karinox-print.service << EOF
[Unit]
Description=Karinox Print Service
After=network.target

[Service]
Type=simple
User=karinox
WorkingDirectory=/opt/karinox/print-service
ExecStart=/usr/bin/node server.js
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=karinox-print

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
systemctl enable karinox-print.service
systemctl start karinox-print.service

echo "✅ Print Service installed and started!"
echo "🌐 Access dashboard at: http://localhost:3000"
```

---

## 📋 VI. Migration Guide

### From Integrated to Standalone

```php
// Migration script to move existing print jobs
class PrintServiceMigration {
    public function migratePrintQueue() {
        $jobs = PrintQueue::all();
        $printClient = new PrintServiceClient();

        foreach ($jobs as $job) {
            $printClient->submitPrintJob([
                'job_id' => "MIGRATED_{$job->id}",
                'source_system' => 'karinox-pos',
                'source_id' => $job->metadata['order_id'] ?? null,
                'device_type' => $this->mapPrintTypeToDevice($job->type),
                'print_type' => $job->type,
                'content' => $job->content,
                'metadata' => $job->metadata,
                'priority' => $job->priority,
                'status' => $job->status
            ]);
        }
    }

    private function mapPrintTypeToDevice($type) {
        return match($type) {
            'invoice', 'provisional' => 'receipt',
            'kitchen' => 'kitchen',
            'label' => 'label',
            default => 'receipt'
        };
    }
}
```

## 🎉 Kết luận

**Karinox Print Service** cung cấp:

✅ **Standalone Architecture** - Hoàn toàn độc lập
✅ **Rich History Tracking** - Lịch sử chi tiết với analytics
✅ **Multi-client Support** - Desktop, Web, Mobile
✅ **Enterprise Features** - Device management, monitoring
✅ **Easy Integration** - Simple API for main system
✅ **Production Ready** - Docker deployment, systemd service

**Next Steps:**

1. Deploy Print Service as microservice
2. Install Print Client apps on workstations
3. Migrate existing print queue
4. Configure devices and test printing
5. Train staff on new interface

🚀 **Production ready standalone print solution!**
