# Báo Cáo Doanh Thu Theo Nhân Viên

## API Endpoints

### 1. Báo cáo doanh thu theo nhân viên theo ngày

**GET** `/admin/reports/daily-sales-by-employee`

**Parameters:**

- `date` (required): Ngày cần báo cáo (YYYY-MM-DD)
- `branch_id` (optional): ID chi nhánh (nếu không có sẽ lấy tất cả chi nhánh)

**Response Example:**

```json
{
    "success": true,
    "data": {
        "date": "2025-10-07",
        "branch_id": null,
        "employees": [
            {
                "employee_id": 1,
                "employee_name": "Nguyễn Văn A",
                "employee_email": "user@example.com",
                "total_revenue": 1500000,
                "total_voucher_discount": 150000,
                "total_reward_points_used": 1000,
                "total_reward_discount": 50000,
                "voucher_usage_count": 5,
                "invoice_count": 15,
                "payment_methods": [
                    {
                        "method": "cash",
                        "amount": 800000,
                        "count": 8
                    },
                    {
                        "method": "card",
                        "amount": 700000,
                        "count": 7
                    }
                ],
                "payment_breakdown": {
                    "cash": 800000,
                    "card": 700000
                }
            }
        ],
        "totals": {
            "total_employees": 3,
            "total_revenue": 4500000,
            "total_voucher_discount": 450000,
            "total_reward_points_used": 3000,
            "total_reward_discount": 150000,
            "total_voucher_usage_count": 15,
            "total_invoice_count": 45,
            "payment_method_totals": [
                {
                    "method": "cash",
                    "total_amount": 2400000,
                    "total_count": 24
                },
                {
                    "method": "card",
                    "total_amount": 2100000,
                    "total_count": 21
                }
            ]
        }
    }
}
```

### 2. Báo cáo doanh thu theo nhân viên theo khoảng thời gian

**GET** `/admin/reports/sales-by-employee-period`

**Parameters:**

- `start_date` (required): Ngày bắt đầu (YYYY-MM-DD)
- `end_date` (required): Ngày kết thúc (YYYY-MM-DD)
- `branch_id` (optional): ID chi nhánh

**Response Example:**

```json
{
    "success": true,
    "data": {
        "start_date": "2025-10-01",
        "end_date": "2025-10-07",
        "branch_id": null,
        "employees": [
            {
                "employee_id": 1,
                "employee_name": "Nguyễn Văn A",
                "total_revenue": 10500000,
                "total_voucher_discount": 1050000,
                "total_reward_discount": 350000,
                "total_invoice_count": 105,
                "daily_sales": [
                    {
                        "date": "2025-10-01",
                        "revenue": 1500000,
                        "voucher_discount": 150000,
                        "reward_discount": 50000,
                        "invoice_count": 15
                    }
                ]
            }
        ],
        "period_totals": {
            "total_revenue": 31500000,
            "total_voucher_discount": 3150000,
            "total_reward_discount": 1050000,
            "total_invoice_count": 315
        }
    }
}
```

## Cách sử dụng

### Frontend Integration

```javascript
// Lấy báo cáo theo ngày
const getDailySalesReport = async (date, branchId = null) => {
    const params = new URLSearchParams({ date });
    if (branchId) params.append('branch_id', branchId);

    const response = await fetch(`/admin/reports/daily-sales-by-employee?${params}`, {
        headers: {
            Authorization: `Bearer ${token}`,
            'X-Karinox-App': 'admin',
        },
    });

    return response.json();
};

// Lấy báo cáo theo khoảng thời gian
const getPeriodSalesReport = async (startDate, endDate, branchId = null) => {
    const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
    if (branchId) params.append('branch_id', branchId);

    const response = await fetch(`/admin/reports/sales-by-employee-period?${params}`, {
        headers: {
            Authorization: `Bearer ${token}`,
            'X-Karinox-App': 'admin',
        },
    });

    return response.json();
};
```

### Hiển thị dữ liệu trong bảng

```vue
<template>
    <div>
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Tổng thu</th>
                    <th>Tiền mặt</th>
                    <th>Thẻ</th>
                    <th>Voucher sử dụng</th>
                    <th>Điểm thưởng</th>
                    <th>Tiền giảm</th>
                    <th>Số hóa đơn</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="employee in reportData.employees" :key="employee.employee_id">
                    <td>{{ employee.employee_name }}</td>
                    <td>{{ formatCurrency(employee.total_revenue) }}</td>
                    <td>{{ formatCurrency(employee.payment_breakdown.cash || 0) }}</td>
                    <td>{{ formatCurrency(employee.payment_breakdown.card || 0) }}</td>
                    <td>{{ employee.voucher_usage_count }}</td>
                    <td>{{ employee.total_reward_points_used }}</td>
                    <td>{{ formatCurrency(employee.total_voucher_discount + employee.total_reward_discount) }}</td>
                    <td>{{ employee.invoice_count }}</td>
                </tr>
                <!-- Hàng tổng cộng -->
                <tr class="totals-row">
                    <td><strong>Tổng cộng</strong></td>
                    <td>
                        <strong>{{ formatCurrency(reportData.totals.total_revenue) }}</strong>
                    </td>
                    <td>
                        <strong>{{ formatCurrency(getPaymentMethodTotal('cash')) }}</strong>
                    </td>
                    <td>
                        <strong>{{ formatCurrency(getPaymentMethodTotal('card')) }}</strong>
                    </td>
                    <td>
                        <strong>{{ reportData.totals.total_voucher_usage_count }}</strong>
                    </td>
                    <td>
                        <strong>{{ reportData.totals.total_reward_points_used }}</strong>
                    </td>
                    <td>
                        <strong>{{ formatCurrency(reportData.totals.total_voucher_discount + reportData.totals.total_reward_discount) }}</strong>
                    </td>
                    <td>
                        <strong>{{ reportData.totals.total_invoice_count }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
```

## Authentication & Permissions

- Yêu cầu JWT token hợp lệ
- Header `X-Karinox-App: admin`
- User phải có role `admin` hoặc `manager`
- Middleware: `auth:api`, `is_karinox_app`, `set_karinox_branch_id`, `role:admin|manager`
