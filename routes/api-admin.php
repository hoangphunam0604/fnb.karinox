<?php

use Illuminate\Support\Facades\Route;

use App\Http\Admin\Controllers\AreaController;
use App\Http\Admin\Controllers\AttributeController;
use App\Http\Admin\Controllers\BranchController;
use App\Http\Admin\Controllers\TableAndRoomController;
use App\Http\Admin\Controllers\MenuController;
use App\Http\Admin\Controllers\CustomerController;
use App\Http\Admin\Controllers\FileUploadController;
use App\Http\Admin\Controllers\InvoiceController;
use App\Http\Admin\Controllers\MembershipLevelController;
use App\Http\Admin\Controllers\ProductController;
use App\Http\Admin\Controllers\PrintTemplateController;
use App\Http\Admin\Controllers\HolidayController;
use App\Http\Admin\Controllers\UserController;
use App\Http\Admin\Controllers\VoucherCampaignController;
use App\Http\Admin\Controllers\RoleController;
use App\Http\Admin\Controllers\PermissionController;
use App\Http\Admin\Controllers\ReportController;
use App\Http\Admin\Controllers\InventoryController;

Route::get('/admin/products/kiot-viet', [ProductController::class, 'kiotViet']);

Route::get('/admin/products/sync', [ProductController::class, 'syncFromKiotViet']);

// Admin routes: require API auth, app check, branch context and only allow users with role admin or manager
Route::middleware(['auth:api', 'is_karinox_app', 'set_karinox_branch_id', 'role:admin|manager'])->prefix('admin')->group(function () {


  Route::post('upload', [FileUploadController::class, 'upload']);

  Route::apiResource('areas', AreaController::class);

  Route::apiResource('attributes', AttributeController::class);

  Route::get('branches/all', [BranchController::class, 'all']);
  Route::apiResource('branches', BranchController::class);

  Route::get('menus/all', [MenuController::class, 'all']);
  Route::apiResource('menus', MenuController::class);

  Route::post('customers/import', [CustomerController::class, 'import']);
  Route::apiResource('customers', CustomerController::class);

  Route::apiResource('membership-levels', MembershipLevelController::class);

  Route::apiResource('tables-and-rooms', TableAndRoomController::class);

  Route::get('invoices', [InvoiceController::class, 'index']);
  Route::get('invoices/{id}', [InvoiceController::class, 'show']);

  Route::get('products/manufacturing-autocomplete', [ProductController::class, 'manufacturingAutocomplete']);
  Route::post('products/import', [ProductController::class, 'import']);

  Route::apiResource('products', ProductController::class);

  Route::post('print-templates/{id}/set-default', [PrintTemplateController::class, 'setDefault']);
  Route::apiResource('print-templates', PrintTemplateController::class);
  Route::apiResource('holidays', HolidayController::class);
  Route::apiResource('users', UserController::class);

  // Voucher Campaigns with additional endpoints
  Route::apiResource('voucher-campaigns', VoucherCampaignController::class);
  Route::post('voucher-campaigns/{voucherCampaign}/generate-vouchers', [VoucherCampaignController::class, 'generateVouchers']);
  Route::get('voucher-campaigns/{voucherCampaign}/analytics', [VoucherCampaignController::class, 'analytics']);
  Route::get('voucher-campaigns/{voucherCampaign}/export-codes', [VoucherCampaignController::class, 'exportCodes']);
  Route::put('voucher-campaigns/{voucherCampaign}/activate-vouchers', [VoucherCampaignController::class, 'activateVouchers']);
  Route::put('voucher-campaigns/{voucherCampaign}/deactivate-vouchers', [VoucherCampaignController::class, 'deactivateVouchers']);
  Route::get('voucher-campaigns/{voucherCampaign}/vouchers', [VoucherCampaignController::class, 'vouchers']);

  // Roles & Permissions (read-only endpoints for frontend user-role assignment)
  Route::get('roles', [RoleController::class, 'index']);
  Route::get('roles/{role}', [RoleController::class, 'show']);
  Route::get('permissions', [PermissionController::class, 'index']);
  Route::get('permissions/{permission}', [PermissionController::class, 'show']);

  // Reports
  Route::get('reports/daily-sales-by-employee', [ReportController::class, 'dailySalesByEmployee']);
  Route::get('reports/sales-by-employee-period', [ReportController::class, 'salesByEmployeePeriod']);

  // Inventory Management
  Route::prefix('inventory')->group(function () {
    // Lấy danh sách giao dịch kho
    Route::get('transactions', [InventoryController::class, 'index']);

    // Xem chi tiết giao dịch kho
    Route::get('transactions/{id}', [InventoryController::class, 'show']);

    // Báo cáo tồn kho
    Route::get('stock-report', [InventoryController::class, 'getStockReport']);

    // Kiểm kho
    Route::post('stocktaking', [InventoryController::class, 'stocktaking']);

    // Nhập kho
    Route::post('import', [InventoryController::class, 'import']);

    // Xuất kho
    Route::post('export', [InventoryController::class, 'export']);

    // Chuyển kho
    Route::post('transfer', [InventoryController::class, 'transfer']);

    // Thẻ kho sản phẩm
    Route::get('product-card/{product_id}', [InventoryController::class, 'getProductStockCard']);

    // Tóm tắt thẻ kho sản phẩm
    Route::get('product-summary/{product_id}', [InventoryController::class, 'getProductStockSummary']);
  });
});
