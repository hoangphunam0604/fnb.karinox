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

    - [x] Seed roles, RoleController, PermissionController
    - [ ] Map permission -> endpoints (policies/gates)

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

---

Cập nhật tệp này khi tiến độ thay đổi hoặc chọn mục bạn muốn tôi bắt đầu xử lý.
