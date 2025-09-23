# DANH SÁCH CÔNG VIỆC API QUẢN TRỊ (theo module)

Tệp này theo dõi tiến độ thực hiện API cho phần Quản trị, nhóm theo module chính để dễ theo dõi.

## Tổng quan trạng thái (cập nhật mới nhất)

- [x] Xác thực (Auth) — `app/Http/Controllers/Api/Auth/AuthController.php`, `routes/api-auth.php`, `config/jwt.php`
- [x] Người dùng (Users) — `app/Http/Controllers/Admin/UserController.php`, `app/Services/UserService.php`, `app/Http/Resources/Admin/UserResource.php`, `database/seeders/UserSeeder.php`
- [x] Sản phẩm (Products) — controller/service/request/resource, migration `create_products_table.php`
- [x] Danh mục (Categories) — controller, resource, request
- [x] Chi nhánh (Branches) — controller, resource, request, liên kết branch-user
- [x] Hoá đơn / Thanh toán (Invoices/Billing) — `InvoiceService`, `InvoiceResource` (tạo hoá đơn, trạng thái thanh toán, hoàn tiền)
- [x] Vai trò & Quyền (Roles & Permissions) — Spatie + controllers Role/Permission và seeders
- [x] Thông báo & Thời gian thực (Notifications & Real-time) — Reverb & events/listeners
- [-] FormRequests & Validation — đang kiểm tra/chuẩn hoá (FormRequest tồn tại nhiều, cần tách Store/Update và thêm `authorize()`)

## Danh sách kiểm tra theo module (chi tiết)

### Module: Người dùng (Users)

- [x] Controller + Service + Resource
- [x] Seeder admin
- [x] Xử lý vai trò (role) và chi nhánh (branches)
- [ ] Hoàn thiện FormRequests (Store/Update) và `authorize()`

### Module: Sản phẩm (Products)

- [x] Controller + Service + Requests + Resources
- [x] Import, đồng bộ pivot chi nhánh
- [ ] Tests tính năng cho import/CRUD

### Module: Danh mục (Categories)

- [x] Controller + Resource + Request
- [ ] Kiểm tra phân cấp danh mục (nếu có)

### Module: Chi nhánh (Branches)

- [x] Controller + Resource + Request
- [ ] Kiểm tra liên kết branch-user & quyền hạn

### Module: Hoá đơn / Thanh toán (Invoices/Billing)

- [x] Tạo hoá đơn từ đơn hàng, xử lý trạng thái thanh toán
- [ ] Tests cho luồng tạo hoá đơn và hoàn tiền

### Module: Vai trò & Quyền (Roles & Permissions)

- [x] Seed roles, RoleController, PermissionController
- [x] Endpoints chỉ đọc (index/show) đã đăng ký trong `routes/api-admin.php` cho frontend gán quyền
- [ ] Ánh xạ permission -> endpoints (policies/gates)
- **Ghi chú**: Roles và permissions đã được seed trong hệ thống. Controllers hỗ trợ CRUD nhưng chỉ có endpoints đọc (index/show) được expose trong admin routes để frontend có thể lấy danh sách khi gán roles/permissions cho users. Nếu muốn CRUD đầy đủ sau này, có thể thêm routes được bảo vệ và gate phù hợp.

### Module: Thông báo & Thời gian thực (Notifications & Realtime)

- [x] Cấu hình Broadcasting (Reverb), events & listeners
- [ ] Kiểm tra sức khoẻ / cài đặt production cho Reverb

## Nhiệm vụ chung (Cross-cutting tasks)

- [ ] Ánh xạ quyền & Phân quyền (Policies/Gates) — chưa hoàn thành
- [ ] Tests: Unit & Feature cho Admin API (auth + User/Product/Invoice) — chưa hoàn thành
- [ ] Tài liệu Admin API (OpenAPI / Scribe / README_admin_api.md) — chưa hoàn thành
- [ ] CI: PHPUnit & Lint workflow — chưa hoàn thành
- [ ] README - Hướng dẫn Admin API & cài đặt — chưa hoàn thành
- [ ] Bảo mật & Triển khai (CORS production, secrets, queue, monitoring) — chưa hoàn thành

## Hướng dẫn cài đặt nhanh (môi trường local)

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

## Kế hoạch tiếp theo (gợi ý)

Tôi đang kiểm tra và chuẩn hoá `FormRequest` cho Admin (Store/Update). Nếu bạn muốn, tôi sẽ:

1. Liệt kê tất cả `FormRequest` hiện có dưới `app/Http/Requests/Admin`.
2. Tạo/chuẩn hoá `StoreUserRequest` và `UpdateUserRequest` làm mẫu.
3. Cập nhật TODO khi hoàn thành.

## Ma trận bao phủ tính năng (tóm tắt)

Phần này liệt kê nhanh module chính, trạng thái API admin hiện tại và đề xuất endpoint còn thiếu cho frontend.

### Người dùng (Users)

- **Trạng thái**: Controller (apiResource), Service, Resource có sẵn.
- **Thiếu**: Tách FormRequests (StoreUserRequest / UpdateUserRequest); endpoints tùy chọn: change-password, activity logs.

### Vai trò & Quyền (Roles & Permissions)

- **Trạng thái**: `RoleController` và `PermissionController` tồn tại; Resource classes có sẵn.
- **Thiếu**: Routes chưa được đăng ký trong `routes/api-admin.php`. Nếu admin UI quản lý roles/permissions, thêm `Route::apiResource('roles', RoleController::class)` và `Route::apiResource('permissions', PermissionController::class)`.

### Sản phẩm (Products)

- **Trạng thái**: Controller (apiResource), Service, Import endpoint, Resources có sẵn.
- **Thiếu**: Feature tests cho import/CRUD; hành động hàng loạt tùy chọn (bulk-update, bulk-delete).

### Danh mục (Categories)

- **Trạng thái**: Controller + Resource + Request + endpoint `all()` có sẵn.
- **Thiếu**: Tách FormRequest/authorize và tests.

### Chi nhánh (Branches)

- **Trạng thái**: Controller (apiResource) và endpoint `branches/all` có sẵn.
- **Thiếu**: APIs liên kết Branch-user (gán/bỏ gán users cho branches) nếu admin UI cần.

### Khách hàng (Customers)

- **Trạng thái**: Controller (apiResource), import endpoint, Service và Resources có sẵn.
- **Thiếu**: Endpoints điểm khách hàng (tóm tắt điểm, lịch sử điểm), endpoints điều chỉnh điểm thủ công.

### Cấp độ thành viên (Membership Levels)

- **Trạng thái**: Controller (apiResource), Service, Resources có sẵn.
- **Thiếu**: Tests và hành động admin cho nâng cấp/hạ cấp cưỡng bức.

### Hoá đơn / Thanh toán (Invoices/Billing)

- **Trạng thái**: `InvoiceController` expose index/show; InvoiceService và InvoiceResource có sẵn.
- **Thiếu**: Hành động admin cho huỷ/hoàn tiền/in lại (thêm `PUT admin/invoices/{id}/cancel`, `POST admin/invoices/{id}/refund` nếu cần).

### Đơn hàng - Góc nhìn Admin (Orders Admin view)

- **Trạng thái**: OrderService tồn tại; POS order endpoints nằm dưới POS routes.
- **Thiếu**: Nếu admin cần quản lý đơn hàng, thêm `Admin\OrderController` với endpoints index/show/update status kết nối với `OrderService`.

### Voucher/Phiếu giảm giá

- **Trạng thái**: VoucherService và unit tests tồn tại; POS có endpoints sử dụng voucher.
- **Thiếu**: Admin CRUD cho vouchers (không có `Admin\VoucherController`). Đề xuất `apiResource('vouchers', VoucherController::class)` và một `Admin\VoucherController`.

### Điểm / Chương trình khách hàng thân thiết (Points/Loyalty)

- **Trạng thái**: `PointService` và model `PointHistory` có sẵn.
- **Thiếu**: Endpoints admin để xem và điều chỉnh điểm (endpoints theo từng khách hàng).

### TableAndRoom, PrintTemplates, Areas, Attributes, Holidays

- **Trạng thái**: Controllers + Services + Resources có sẵn và đã đăng ký.
- **Thiếu**: Chủ yếu là tests và thực thi `authorize()` trong FormRequest.

## Danh sách triển khai ngắn (đề xuất commit tiếp theo)

1. Đăng ký Role/Permission routes trong `routes/api-admin.php` (patch nhanh).
2. Tạo khung `Admin\VoucherController` và `Admin\VoucherResource` + FormRequests, thêm routes.
3. Thêm endpoints điểm khách hàng trong `CustomerController` (tóm tắt & lịch sử điểm) và tạo `PointHistoryResource`.
4. Thêm hành động admin hoá đơn (huỷ/hoàn tiền) trong `InvoiceController` và tests.
5. Tách/chuẩn hoá FormRequests cho modules quan trọng (User, Product, Customer, Voucher) và triển khai `authorize()` gọi Spatie permissions.
6. Thêm feature tests cho endpoints admin mới (happy path + từ chối quyền).

---

**Tôi có thể bắt đầu với bước (1) tự động: thêm routes cho roles/permissions vào `routes/api-admin.php` và commit nhanh. Bạn có muốn tôi thực hiện bước đó ngay bây giờ không?**

---

Cập nhật tệp này khi tiến độ thay đổi hoặc chọn mục bạn muốn tôi bắt đầu xử lý.
