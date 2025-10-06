<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
  /**
   * Báo cáo doanh thu theo nhân viên theo ngày
   */
  public function getDailySalesReportByEmployee(string $date, ?int $branchId = null): array
  {
    $selectedDate = Carbon::parse($date)->format('Y-m-d');

    // Query cơ bản lấy invoices trong ngày
    $invoicesQuery = Invoice::whereDate('created_at', $selectedDate)
      ->where('invoice_status', '!=', 'canceled')
      ->with(['order.user', 'order.branch']);

    if ($branchId) {
      $invoicesQuery->where('branch_id', $branchId);
    }

    $invoices = $invoicesQuery->get();

    // Nhóm theo nhân viên
    $employeeSales = $invoices->groupBy('order.user_id')->map(function ($employeeInvoices, $userId) {
      $user = $employeeInvoices->first()->order->user;

      if (!$user) {
        return null; // Skip nếu không có user
      }

      $totalRevenue = $employeeInvoices->sum('total_price');
      $totalVoucherDiscount = $employeeInvoices->sum('discount_amount');
      $totalRewardPointsUsed = $employeeInvoices->sum('reward_points_used');
      $totalRewardDiscount = $employeeInvoices->sum('reward_discount');
      $voucherCount = $employeeInvoices->where('discount_amount', '>', 0)->count();

      // Nhóm theo phương thức thanh toán
      $paymentMethods = $employeeInvoices->groupBy('payment_method')->map(function ($methodInvoices, $method) {
        return [
          'method' => $method,
          'amount' => $methodInvoices->sum('total_price'),
          'count' => $methodInvoices->count()
        ];
      })->values();

      return [
        'employee_id' => $user->id,
        'employee_name' => $user->name,
        'employee_email' => $user->email,
        'total_revenue' => $totalRevenue,
        'total_voucher_discount' => $totalVoucherDiscount,
        'total_reward_points_used' => $totalRewardPointsUsed,
        'total_reward_discount' => $totalRewardDiscount,
        'voucher_usage_count' => $voucherCount,
        'invoice_count' => $employeeInvoices->count(),
        'payment_methods' => $paymentMethods,
        'payment_breakdown' => $paymentMethods->pluck('amount', 'method')->toArray()
      ];
    })->filter()->values(); // Loại bỏ null values

    // Tính tổng cộng
    $totals = [
      'total_employees' => $employeeSales->count(),
      'total_revenue' => $employeeSales->sum('total_revenue'),
      'total_voucher_discount' => $employeeSales->sum('total_voucher_discount'),
      'total_reward_points_used' => $employeeSales->sum('total_reward_points_used'),
      'total_reward_discount' => $employeeSales->sum('total_reward_discount'),
      'total_voucher_usage_count' => $employeeSales->sum('voucher_usage_count'),
      'total_invoice_count' => $employeeSales->sum('invoice_count'),
      'payment_method_totals' => []
    ];

    // Tính tổng theo từng phương thức thanh toán
    $allPaymentMethods = $employeeSales->flatMap(function ($employee) {
      return $employee['payment_methods'];
    })->groupBy('method')->map(function ($methods, $method) {
      return [
        'method' => $method,
        'total_amount' => $methods->sum('amount'),
        'total_count' => $methods->sum('count')
      ];
    });

    $totals['payment_method_totals'] = $allPaymentMethods->values()->toArray();

    return [
      'date' => $selectedDate,
      'branch_id' => $branchId,
      'employees' => $employeeSales->toArray(),
      'totals' => $totals
    ];
  }

  /**
   * Báo cáo doanh thu theo nhân viên theo khoảng thời gian
   */
  public function getSalesReportByEmployeePeriod(string $startDate, string $endDate, ?int $branchId = null): array
  {
    $start = Carbon::parse($startDate)->startOfDay();
    $end = Carbon::parse($endDate)->endOfDay();

    // Query cơ bản
    $invoicesQuery = Invoice::whereBetween('created_at', [$start, $end])
      ->where('invoice_status', '!=', 'canceled')
      ->with(['order.user', 'order.branch']);

    if ($branchId) {
      $invoicesQuery->where('branch_id', $branchId);
    }

    $invoices = $invoicesQuery->get();

    // Nhóm theo nhân viên và ngày
    $employeeSales = $invoices->groupBy('order.user_id')->map(function ($employeeInvoices, $userId) use ($start, $end) {
      $user = $employeeInvoices->first()->order->user;

      if (!$user) {
        return null;
      }

      // Nhóm theo ngày
      $dailySales = $employeeInvoices->groupBy(function ($invoice) {
        return Carbon::parse($invoice->created_at)->format('Y-m-d');
      })->map(function ($dayInvoices, $date) {
        return [
          'date' => $date,
          'revenue' => $dayInvoices->sum('total_price'),
          'voucher_discount' => $dayInvoices->sum('discount_amount'),
          'reward_discount' => $dayInvoices->sum('reward_discount'),
          'invoice_count' => $dayInvoices->count()
        ];
      })->values();

      return [
        'employee_id' => $user->id,
        'employee_name' => $user->name,
        'total_revenue' => $employeeInvoices->sum('total_price'),
        'total_voucher_discount' => $employeeInvoices->sum('discount_amount'),
        'total_reward_discount' => $employeeInvoices->sum('reward_discount'),
        'total_invoice_count' => $employeeInvoices->count(),
        'daily_sales' => $dailySales
      ];
    })->filter()->values();

    return [
      'start_date' => $start->format('Y-m-d'),
      'end_date' => $end->format('Y-m-d'),
      'branch_id' => $branchId,
      'employees' => $employeeSales->toArray(),
      'period_totals' => [
        'total_revenue' => $employeeSales->sum('total_revenue'),
        'total_voucher_discount' => $employeeSales->sum('total_voucher_discount'),
        'total_reward_discount' => $employeeSales->sum('total_reward_discount'),
        'total_invoice_count' => $employeeSales->sum('total_invoice_count')
      ]
    ];
  }
}
