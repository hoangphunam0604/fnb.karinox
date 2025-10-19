# 🖨️ Tài liệu API cho Frontend - Ứng dụng Quản lý In Karinox

## 📋 Tổng quan Dự án

**Ứng dụng Quản lý In Karinox** là hệ thống độc lập để:

- ✅ **Quản lý Print Jobs** - Xem, theo dõi, xử lý jobs in
- ✅ **Quản lý Print Templates** - CRUD templates với preview
- ✅ **Test Print** - In thử với dữ liệu mẫu
- ✅ **Device Management** - Quản lý thiết bị in
- ✅ **Print History** - Lịch sử và báo cáo in
- ✅ **Branch Selection** - Chọn chi nhánh làm việc

### 🏗️ Kiến trúc API

```
Frontend App → Print API → Print Service
             → Mock Data Service
             → Template Service
```

**Base URL**: `http://karinox-fnb.local/api/print`

---

## 🔐 Authentication

### 🆕 **Print Management App (KHÔNG CẦN ĐĂNG NHẬP)**

Ứng dụng quản lý in hoạt động độc lập, chỉ cần chọn chi nhánh:

```javascript
const headers = {
    'X-Branch-ID': branch_id, // Required - ID chi nhánh
    'Content-Type': 'application/json',
};
```

**Workflow đăng nhập:**

1. App khởi động → Gọi `GET /api/print/branches` để lấy danh sách chi nhánh
2. User chọn chi nhánh → Gọi `POST /api/print/branch/select`
3. Lưu `branch_id` vào localStorage
4. Tất cả API calls sau đó dùng header `X-Branch-ID`

### 2. **Device Authentication (API Key)**

Dành cho print clients/devices:

```javascript
const headers = {
    'X-API-Key': device_api_key,
    'Content-Type': 'application/json',
};
```

---

## 📊 API Endpoints Overview

### � **Branch Management** (No Auth Required)

| Method | Endpoint                   | Auth   | Description             |
| ------ | -------------------------- | ------ | ----------------------- |
| GET    | `/api/print/branches`      | None   | Danh sách chi nhánh     |
| POST   | `/api/print/branch/select` | None   | Chọn chi nhánh làm việc |
| GET    | `/api/print/settings`      | Branch | Lấy cài đặt ứng dụng    |
| POST   | `/api/print/settings`      | Branch | Cập nhật cài đặt        |

### �🎯 **Print Jobs Management**

| Method | Endpoint                       | Auth   | Description             |
| ------ | ------------------------------ | ------ | ----------------------- |
| GET    | `/api/print/queue`             | Branch | Lấy hàng đợi in         |
| PUT    | `/api/print/queue/{id}/status` | Branch | Cập nhật trạng thái job |
| DELETE | `/api/print/queue/{id}`        | Branch | Xóa job khỏi queue      |
| POST   | `/api/print/test`              | Branch | In thử với mock data    |

### 📄 **Template Management**

| Method | Endpoint                                | Auth   | Description           |
| ------ | --------------------------------------- | ------ | --------------------- |
| GET    | `/api/print/templates`                  | Branch | Danh sách templates   |
| GET    | `/api/print/templates/{id}`             | Branch | Chi tiết template     |
| POST   | `/api/print/templates`                  | Branch | Tạo template mới      |
| PUT    | `/api/print/templates/{id}`             | Branch | Cập nhật template     |
| DELETE | `/api/print/templates/{id}`             | Branch | Xóa template          |
| POST   | `/api/print/templates/{id}/duplicate`   | Branch | Sao chép template     |
| POST   | `/api/print/templates/{id}/set-default` | Branch | Đặt template mặc định |
| POST   | `/api/print/templates/{id}/preview`     | Branch | Xem trước template    |

### 🖥️ **Device Management**

| Method | Endpoint                              | Auth    | Description      |
| ------ | ------------------------------------- | ------- | ---------------- |
| GET    | `/api/print/client/queue`             | API Key | Queue cho device |
| PUT    | `/api/print/client/queue/{id}/status` | API Key | Update từ device |
| POST   | `/api/print/client/register`          | API Key | Đăng ký device   |
| PUT    | `/api/print/client/heartbeat`         | API Key | Device heartbeat |
| GET    | `/api/print/client/history`           | API Key | Lịch sử in       |

---

## 🔧 API Documentation Chi tiết

### 0. **Branch Management** (Khởi tạo ứng dụng)

#### Lấy danh sách chi nhánh

```http
GET /api/print/branches
```

**Response:**

```json
{
    "success": true,
    "message": "Branches retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Chi nhánh Quận 1",
            "address": "123 Nguyễn Huệ, Quận 1, TP.HCM",
            "phone": "0909123456"
        },
        {
            "id": 2,
            "name": "Chi nhánh Quận 3",
            "address": "456 Lê Văn Sỹ, Quận 3, TP.HCM",
            "phone": "0909456789"
        }
    ]
}
```

#### Chọn chi nhánh làm việc

```http
POST /api/print/branch/select
Content-Type: application/json

{
    "branch_id": 1
}
```

**Response:**

```json
{
    "success": true,
    "message": "Branch selected successfully",
    "data": {
        "branch_id": 1,
        "branch_name": "Chi nhánh Quận 1",
        "address": "123 Nguyễn Huệ, Quận 1, TP.HCM",
        "phone": "0909123456"
    }
}
```

#### Lấy cài đặt ứng dụng

```http
GET /api/print/settings
X-Branch-ID: 1
```

**Response:**

```json
{
    "success": true,
    "data": {
        "branch_id": 1,
        "branch_name": "Chi nhánh Quận 1",
        "auto_print_enabled": true,
        "print_preview_enabled": true,
        "default_templates": {
            "provisional": 5,
            "invoice": 8,
            "kitchen": 12,
            "labels": 15
        },
        "available_devices": {
            "receipt_printer_001": "Receipt Printer 001",
            "kitchen_printer_001": "Kitchen Printer 001",
            "label_printer_001": "Label Printer 001"
        }
    }
}
```

#### Cập nhật cài đặt

```http
POST /api/print/settings
X-Branch-ID: 1
Content-Type: application/json

{
    "auto_print_enabled": false,
    "print_preview_enabled": true,
    "default_templates": {
        "provisional": 6,
        "invoice": 9
    }
}
```

### 1. **Print Queue Management**

#### Lấy hàng đợi in

```http
GET /api/print/queue?device_id=printer_001&status=pending&limit=20
X-Branch-ID: 1
```

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1001,
            "order_id": 123,
            "type": "provisional",
            "device_id": "printer_001",
            "priority": "high",
            "status": "pending",
            "content": "<html>...</html>",
            "created_at": "2025-10-19 15:30:00",
            "order_info": {
                "order_code": "ORD-001",
                "table_name": "Bàn 05",
                "total_amount": "150,000đ"
            }
        }
    ]
}
```

#### Cập nhật trạng thái job

```http
PUT /api/print/queue/1001/status
X-Branch-ID: 1
Content-Type: application/json

{
  "status": "completed",
  "error_message": null
}
```

### 2. **Test Print Feature**

#### In thử với mock data

```http
POST /api/print/test
X-Branch-ID: 1
Content-Type: application/json

{
  "print_type": "provisional",
  "template_id": 5,
  "mock_data_type": "complex"
}
```

**Mock Data Types:**

- `simple` - Đơn hàng cơ bản (2-3 món)
- `complex` - Có khách hàng, voucher, giảm giá
- `with_toppings` - Sản phẩm có topping
- `large_order` - Đơn hàng lớn 10+ món

**Response:**

```json
{
    "success": true,
    "message": "Tạo job in thử provisional thành công",
    "data": {
        "id": 2001,
        "type": "provisional",
        "status": "pending"
    },
    "mock_data_preview": {
        "order_code": "TEST-20251019153000",
        "table_name": "Bàn Test-01",
        "items_count": 4,
        "total_amount": 173000
    }
}
```

### 3. **Template Management**

#### Lấy danh sách templates

```http
GET /api/print/templates?type=invoice&is_active=true
Authorization: Bearer {jwt_token}
X-Branch-ID: 1
```

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Template Hóa đơn Karinox",
            "type": "invoice",
            "content": "{{branch_name}}\n...",
            "variables": ["order_code", "table_name", "total_amount"],
            "is_active": true,
            "is_default": true,
            "branch_id": 1,
            "created_at": "2025-10-19 10:00:00"
        }
    ],
    "branch_id": 1
}
```

#### Tạo template mới

```http
POST /api/print/templates
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "name": "Template Tạm tính Custom",
  "type": "provisional",
  "content": "{{branch_name}}\n---\nĐơn: {{order_code}}\nBàn: {{table_name}}\nTổng: {{total_amount}}đ",
  "variables": ["branch_name", "order_code", "table_name", "total_amount"],
  "is_active": true,
  "description": "Template tùy chỉnh cho phiếu tạm tính"
}
```

#### Xem trước template

```http
POST /api/print/templates/5/preview
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "mock_data_type": "complex"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "template": {
            "id": 5,
            "name": "Template Tạm tính Custom",
            "type": "provisional"
        },
        "rendered_content": "Karinox Coffee Test\n---\nĐơn: TEST-20251019153000\nBàn: Bàn Test-01\nTổng: 173,000đ",
        "mock_data_used": "complex"
    }
}
```

#### Sao chép template

```http
POST /api/print/templates/5/duplicate
Authorization: Bearer {jwt_token}
```

#### Đặt template mặc định

```http
POST /api/print/templates/10/set-default
Authorization: Bearer {jwt_token}
```

---

## 📱 Frontend UI Requirements

### 1. **Dashboard Overview**

```
┌─────────────────────────────────────────┐
│ 🖨️ Print Management Dashboard           │
├─────────────────────────────────────────┤
│ 📊 Quick Stats                          │
│   • Jobs Pending: 12                   │
│   • Jobs Completed Today: 156          │
│   • Active Devices: 8                  │
│   • Failed Jobs: 2                     │
├─────────────────────────────────────────┤
│ 🔴 Recent Alerts                       │
│   • Kitchen Printer offline (2 min)    │
│   • Receipt Printer low paper          │
└─────────────────────────────────────────┘
```

### 2. **Print Queue Management**

```
┌─────────────────────────────────────────┐
│ 📋 Print Queue                          │
├─────────────────────────────────────────┤
│ 🔍 Filters:                            │
│ [Device ▼] [Status ▼] [Type ▼] [🔄]    │
├─────────────────────────────────────────┤
│ Job ID | Order | Device | Type | Status │
│ #1001  | ORD-123 | Kitchen | kitchen | ⏳│
│ #1002  | ORD-124 | Receipt | invoice | ✅│
│ #1003  | TEST-001| Label   | labels  | ❌│
├─────────────────────────────────────────┤
│ [Retry Failed] [Clear Completed] [Test] │
└─────────────────────────────────────────┘
```

### 3. **Template Management**

```
┌─────────────────────────────────────────┐
│ 📄 Template Management                  │
├─────────────────────────────────────────┤
│ [+ New Template] [📥 Import] [📤 Export]│
├─────────────────────────────────────────┤
│ Template Name        | Type     | Action│
│ 📄 Invoice Default ⭐| invoice  | [✏️🔍📋]│
│ 📄 Kitchen Ticket    | kitchen  | [✏️🔍📋]│
│ 📄 Receipt Custom    | receipt  | [✏️🔍📋]│
├─────────────────────────────────────────┤
│ ⭐ = Default Template               │
│ ✏️ = Edit | 🔍 = Preview | 📋 = Duplicate  │
└─────────────────────────────────────────┘
```

### 4. **Template Editor**

```
┌─────────────────────────────────────────┐
│ ✏️ Template Editor                      │
├─────────────────────────────────────────┤
│ Name: [________________] Type: [▼]      │
│ ☑️ Active  ☑️ Default                   │
├─────────────────────────────────────────┤
│ Content:                                │
│ ┌─────────────────────────────────────┐ │
│ │ {{branch_name}}                     │ │
│ │ ===========================         │ │
│ │ Đơn: {{order_code}}                 │ │
│ │ Bàn: {{table_name}}                 │ │
│ │ {{#items}}                          │ │
│ │ - {{product_name}} x{{quantity}}    │ │
│ │ {{/items}}                          │ │
│ │ ===========================         │ │
│ │ Tổng: {{total_amount}}đ             │ │
│ └─────────────────────────────────────┘ │
├─────────────────────────────────────────┤
│ [🔍 Preview] [💾 Save] [❌ Cancel]      │
└─────────────────────────────────────────┘
```

### 5. **Device Management**

```
┌─────────────────────────────────────────┐
│ 🖨️ Device Management                    │
├─────────────────────────────────────────┤
│ Device Name    | Type    | Status | Last │
│ Kitchen-001    | Kitchen | 🟢 Online | 1m│
│ Receipt-Main   | Receipt | 🟢 Online | 2m│
│ Label-Counter  | Label   | 🔴 Offline| 5m│
│ Cashier-001    | Cashier | 🟡 Busy   | 0m│
├─────────────────────────────────────────┤
│ [+ Add Device] [🔄 Refresh] [⚙️ Settings]│
└─────────────────────────────────────────┘
```

---

## 🎨 Template Variables Reference

### **Basic Variables:**

```javascript
const basicVariables = {
    '{{order_code}}': 'Mã đơn hàng',
    '{{table_name}}': 'Tên bàn/phòng',
    '{{branch_name}}': 'Tên chi nhánh',
    '{{branch_address}}': 'Địa chỉ chi nhánh',
    '{{branch_phone}}': 'SĐT chi nhánh',
    '{{staff_name}}': 'Tên nhân viên',
    '{{total_amount}}': 'Tổng tiền (đã format)',
    '{{subtotal}}': 'Tạm tính (đã format)',
    '{{discount_amount}}': 'Giảm giá (đã format)',
    '{{created_at}}': 'Thời gian (dd/mm/yyyy hh:mm)',
};
```

### **Loop Variables:**

```html
<!-- Items Loop -->
{{#items}} - {{product_name}} x{{quantity}} {{#toppings}} + {{name}}: {{price}}đ {{/toppings}} Giá: {{total}}đ {{note}} {{/items}}
```

### **Conditional Variables:**

```html
{{#customer}} Khách hàng: {{name}} ({{membership_level}}) SĐT: {{phone}} {{/customer}} {{#voucher_code}} Voucher: {{voucher_code}} {{/voucher_code}}
```

---

## 🧪 Testing Features

### **Test Print với UI:**

```javascript
// Component TestPrintDialog
const testPrintOptions = {
    printTypes: ['provisional', 'invoice', 'labels', 'kitchen'],
    mockDataTypes: ['simple', 'complex', 'with_toppings', 'large_order'],
    devices: ['test_printer', 'kitchen_001', 'receipt_main'],
};

// API Call
async function testPrint(options) {
    const response = await fetch('/api/print/test', {
        method: 'POST',
        headers: authHeaders,
        body: JSON.stringify({
            print_type: options.printType,
            template_id: options.templateId,
            mock_data_type: options.mockDataType,
        }),
    });

    return response.json();
}
```

---

## 🎯 Frontend Implementation Guide

### 1. **Setup & Authentication**

```javascript
// api.js
class PrintAPI {
    constructor(baseURL, authToken) {
        this.baseURL = baseURL;
        this.authToken = authToken;
        this.branchId = localStorage.getItem('branch_id');
    }

    async request(method, endpoint, data = null) {
        const headers = {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${this.authToken}`,
            'X-Branch-ID': this.branchId,
        };

        const config = { method, headers };
        if (data) config.body = JSON.stringify(data);

        const response = await fetch(`${this.baseURL}${endpoint}`, config);
        return response.json();
    }

    // Print Queue
    async getQueue(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request('GET', `/print/queue?${params}`);
    }

    async updateJobStatus(jobId, status, errorMessage = null) {
        return this.request('PUT', `/print/queue/${jobId}/status`, {
            status,
            error_message: errorMessage,
        });
    }

    // Templates
    async getTemplates(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request('GET', `/print/templates?${params}`);
    }

    async createTemplate(templateData) {
        return this.request('POST', '/print/templates', templateData);
    }

    async previewTemplate(templateId, mockDataType = 'simple') {
        return this.request('POST', `/print/templates/${templateId}/preview`, {
            mock_data_type: mockDataType,
        });
    }

    // Test Print
    async testPrint(options) {
        return this.request('POST', '/print/test', options);
    }
}
```

### 2. **State Management (Redux/Vuex)**

```javascript
// store/print.js
const printStore = {
    state: {
        queue: [],
        templates: [],
        devices: [],
        stats: {},
        loading: false,
    },

    actions: {
        async fetchQueue({ commit }, filters) {
            commit('SET_LOADING', true);
            try {
                const response = await api.getQueue(filters);
                commit('SET_QUEUE', response.data);
            } catch (error) {
                commit('SET_ERROR', error.message);
            } finally {
                commit('SET_LOADING', false);
            }
        },

        async testPrint({ dispatch }, options) {
            const response = await api.testPrint(options);
            if (response.success) {
                // Refresh queue to show new test job
                dispatch('fetchQueue');
                return response;
            }
            throw new Error(response.message);
        },
    },
};
```

### 3. **Components Structure**

```
src/
├── components/
│   ├── Dashboard/
│   │   ├── StatsCards.vue
│   │   ├── RecentAlerts.vue
│   │   └── QuickActions.vue
│   ├── Queue/
│   │   ├── QueueTable.vue
│   │   ├── QueueFilters.vue
│   │   └── JobDetails.vue
│   ├── Templates/
│   │   ├── TemplateList.vue
│   │   ├── TemplateEditor.vue
│   │   ├── TemplatePreview.vue
│   │   └── VariableHelper.vue
│   ├── Devices/
│   │   ├── DeviceList.vue
│   │   ├── DeviceStatus.vue
│   │   └── DeviceConfig.vue
│   └── Testing/
│       ├── TestPrintDialog.vue
│       └── MockDataSelector.vue
├── pages/
│   ├── Dashboard.vue
│   ├── Queue.vue
│   ├── Templates.vue
│   └── Devices.vue
└── utils/
    ├── api.js
    ├── helpers.js
    └── constants.js
```

---

## 🚀 Deployment & Environment

### **Environment Variables:**

```env
# Frontend Environment
VITE_API_BASE_URL=http://karinox-fnb.local/api
VITE_PRINT_API_BASE_URL=http://karinox-fnb.local/api/print
VITE_WEBSOCKET_URL=ws://karinox-fnb.local:6001

# Print Service Integration
VITE_PRINT_SERVICE_URL=http://print-service.karinox.local:3001
```

### **Build Configuration:**

```javascript
// vite.config.js
export default {
    server: {
        proxy: {
            '/api': {
                target: 'http://karinox-fnb.local',
                changeOrigin: true,
            },
        },
    },
    build: {
        outDir: 'dist',
        sourcemap: true,
    },
};
```

---

## 📋 Development Checklist

### **Phase 1: Core Features**

- [ ] ✅ Setup project với Vue 3/React + TypeScript
- [ ] ✅ Authentication integration với JWT
- [ ] ✅ Print Queue management UI
- [ ] ✅ Template CRUD operations
- [ ] ✅ Basic device monitoring

### **Phase 2: Advanced Features**

- [ ] ✅ Template editor với syntax highlighting
- [ ] ✅ Template preview với mock data
- [ ] ✅ Test print functionality
- [ ] ✅ Real-time updates với WebSocket
- [ ] ✅ Print history & reporting

### **Phase 3: Production Ready**

- [ ] ✅ Error handling & user feedback
- [ ] ✅ Performance optimization
- [ ] ✅ Mobile responsive design
- [ ] ✅ Accessibility compliance
- [ ] ✅ Documentation & testing

---

## 🔍 Error Handling

### **Common Error Responses:**

```javascript
// API Error Format
{
  "success": false,
  "message": "Template không tồn tại",
  "errors": {
    "template_id": ["Template với ID này không được tìm thấy"]
  }
}

// Error Handling
async function handleApiCall(apiFunction) {
  try {
    const response = await apiFunction()
    if (!response.success) {
      throw new Error(response.message)
    }
    return response.data
  } catch (error) {
    console.error('API Error:', error)
    showNotification(error.message, 'error')
    throw error
  }
}
```

### **Status Codes:**

- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Server Error

---

## 💡 Best Practices

### **1. Real-time Updates:**

```javascript
// WebSocket integration for real-time queue updates
const ws = new WebSocket('ws://karinox-fnb.local:6001');
ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'print_job_updated') {
        store.dispatch('updateJob', data.job);
    }
};
```

### **2. Caching Strategy:**

```javascript
// Cache templates and devices for performance
const cache = {
    templates: { data: null, expires: Date.now() + 300000 }, // 5 min
    devices: { data: null, expires: Date.now() + 60000 }, // 1 min
};
```

### **3. User Experience:**

- Loading states cho tất cả API calls
- Optimistic updates cho status changes
- Confirmation dialogs cho destructive actions
- Toast notifications cho feedback
- Auto-refresh queue mỗi 30s

**Tài liệu này cung cấp đầy đủ thông tin để frontend team phát triển ứng dụng Print Management hoàn chỉnh!** 🎯
