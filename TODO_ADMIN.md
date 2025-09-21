# TODO API Admin (theo module)

Tệp này theo dõi tiến độ thực hiện API cho phần Admin, nhóm theo module chính để dễ theo dõi.

## Tổng quan trạng thái (cập nhật)

- [x] Xác thực (Auth) — `app/Http/Controllers/Api/Auth/AuthController.php`, `routes/api-auth.php`, `config/jwt.php`
- [x] Users — `app/Http/Controllers/Admin/UserController.php`, `app/Services/UserService.php`, `app/Http/Resources/Admin/UserResource.php`, `database/seeders/UserSeeder.php`
- [x] Products — controller/service/request/resource, migration `create_products_table.php`
- [x] Categories — controller, resource, request
- [x] Branches — controller, resource, request, branch-user linking
- [x] Invoices / Billing — `InvoiceService`, `InvoiceResource` (create invoice, payment status, refunds)
- [x] Roles & Permissions — Spatie + Role/Permission controllers and seeders
- [x] Notifications & Real-time — Reverb & events/listeners
- [-] FormRequests & Validation — đang audit/chuẩn hoá (FormRequest tồn tại nhiều, cần tách Store/Update và thêm `authorize()`)

## Checklist theo module (chi tiết)

- Module: Users

    - [x] Controller + Service + Resource
    - [x] Seeder admin
    - [ ] Hoàn thiện FormRequests (Store/Update) và `authorize()`

- Module: Products

    - [x] Controller + Service + Requests + Resources
    - [x] Import, branch pivot sync
    - [ ] Tests feature cho import/CRUD

- Module: Categories

    - [x] Controller + Resource + Request

- Module: Branches

    - [x] Controller + Resource + Request
    - [ ] Kiểm tra branch-user linking & permissions

- Module: Invoices / Billing

    - [x] Invoice creation from order, payment status handling
    - [ ] Tests for invoice creation and refund flow

- Module: Roles & Permissions
- Module: Roles & Permissions

    - [x] Seed roles, RoleController, PermissionController
    - [x] Read-only endpoints (index/show) registered in `routes/api-admin.php` for frontend assignment
    - [ ] Map permission -> endpoints (policies/gates)
    - Note: Roles and permissions are seeded in the system. Controllers support CRUD but only read endpoints (index/show) have been exposed in admin routes to allow the frontend to fetch lists when assigning roles/permissions to users. If you want full CRUD exposed later, we can add protected routes and gate them appropriately.

- Module: Notifications & Realtime

    - [x] Broadcasting config (Reverb), events & listeners
    - [ ] Healthcheck / production setup for Reverb

- Cross-cutting tasks
    - [ ] Mapping quyền & Authorization (Policies/Gates) — chưa xong
    - [ ] Tests: Unit & Feature cho Admin API (auth + User/Product/Invoice) — chưa xong
    - [ ] Tài liệu Admin API (OpenAPI / Scribe / README_admin_api.md) — chưa xong
    - [ ] CI: PHPUnit & Lint workflow — chưa xong
    - [ ] README - Hướng dẫn Admin API & setup — chưa xong
    - [ ] Bảo mật & Triển khai (CORS production, secrets, queue, monitoring) — chưa xong

## Ghi chú cài đặt nhanh (local)

1. Sao chép `.env.example` -> `.env` và cấu hình DB, JWT, REVERB.
2. Chạy các lệnh:

```bash
php artisan key:generate
php artisan jwt:secret
php artisan storage:link
php artisan migrate --seed
```

3. Khởi động Reverb nếu cần:

```bash
php artisan reverb:start
```

## Tiếp theo (gợi ý)

- Tôi đang audit và chuẩn hoá `FormRequest` cho Admin (Store/Update). Nếu bạn muốn, tôi sẽ:
    1. Liệt kê tất cả `FormRequest` hiện có dưới `app/Http/Requests/Admin`.
    2. Tạo/chuẩn hoá `StoreUserRequest` và `UpdateUserRequest` làm mẫu.
    3. Cập nhật TODO khi xong.

## Coverage matrix (tóm tắt)

Mục này liệt kê nhanh module chính, trạng thái API admin hiện tại và đề xuất endpoint còn thiếu cho frontend.

- Users

    - Status: Controller (apiResource), Service, Resource present.
    - Missing: Split FormRequests (StoreUserRequest / UpdateUserRequest); optional endpoints: change-password, activity logs.

- Roles & Permissions

    - Status: `RoleController` and `PermissionController` exist; Resource classes present.
    - Missing: Routes not registered in `routes/api-admin.php`. If admin UI manages roles/permissions, add `Route::apiResource('roles', RoleController::class)` and `Route::apiResource('permissions', PermissionController::class)`.

- Products

    - Status: Controller (apiResource), Service, Import endpoint, Resources present.
    - Missing: Feature tests for import/CRUD; optional bulk actions (bulk-update, bulk-delete).

- Categories

    - Status: Controller + Resource + Request + `all()` endpoint present.
    - Missing: FormRequest split/authorize and tests.

- Branches

    - Status: Controller (apiResource) and `branches/all` endpoint present.
    - Missing: Branch-user linking APIs (assign/unassign users to branches) if needed by admin UI.

- Customers

    - Status: Controller (apiResource), import endpoint, Service and Resources present.
    - Missing: Customer points endpoints (points summary, points history), manual point adjustment endpoints.

- Membership Levels

    - Status: Controller (apiResource), Service, Resources present.
    - Missing: Tests and any admin actions for forced upgrades/downgrades.

- Invoices / Billing

    - Status: `InvoiceController` exposes index/show; InvoiceService and InvoiceResource present.
    - Missing: Admin actions for cancel/refund/reprint (add `PUT admin/invoices/{id}/cancel`, `POST admin/invoices/{id}/refund` if needed).

- Orders (Admin view)

    - Status: OrderService exists; POS order endpoints live under POS routes.
    - Missing: If admin needs order management, add `Admin\OrderController` with index/show/update status endpoints wired to `OrderService`.

- Vouchers

    - Status: VoucherService and unit tests exist; POS has voucher usage endpoints.
    - Missing: Admin CRUD for vouchers (no `Admin\VoucherController` present). Recommend `apiResource('vouchers', VoucherController::class)` and an `Admin\VoucherController`.

- Points / Loyalty

    - Status: `PointService` and `PointHistory` model present.
    - Missing: Admin endpoints for viewing and adjusting points (per-customer endpoints).

- TableAndRoom, PrintTemplates, Areas, Attributes, Holidays
    - Status: Controllers + Services + Resources present and registered.
    - Missing: Mostly tests and FormRequest `authorize()` enforcement.

## Short implementation checklist (recommended next commits)

1. Register Role/Permission routes in `routes/api-admin.php` (quick patch).
2. Scaffold `Admin\VoucherController` and `Admin\VoucherResource` + FormRequests, and add routes.
3. Add customer points endpoints in `CustomerController` (points summary & history) and create `PointHistoryResource`.
4. Add invoice admin actions (cancel/refund) in `InvoiceController` and tests.
5. Split/normalize FormRequests for critical modules (User, Product, Customer, Voucher) and implement `authorize()` calling Spatie permissions.
6. Add feature tests for the new admin endpoints (happy path + permission denied).

---

Tôi có thể bắt đầu với bước (1) tự động: thêm routes cho roles/permissions vào `routes/api-admin.php` và commit nhanh. Bạn muốn tôi thực hiện bước đó bây giờ chứ?

---

Cập nhật tệp này khi tiến độ thay đổi hoặc chọn mục bạn muốn tôi bắt đầu xử lý.
