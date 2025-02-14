# Hệ thống FnB KarinoX

## Yêu cầu

## Cài đặt

## Test

```sh
php artisan migrate:fresh --env=testing

php artisan test --filter=OrderServiceTest --testdox
php artisan test --filter=VoucherServiceTest --testdox
php artisan test --filter=InvoiceServiceTest --testdox
php artisan test --filter=SystemSettingServiceTest --testdox
```
```sql
select * from `vouchers` where `is_active` = ? and `start_date` <= ? and `end_date` >= ? and (`valid_days_of_week` is null or json_contains(`valid_days_of_week`, ?)) 
and (`valid_weeks_of_month` is null or json_contains(`valid_weeks_of_month`, ?)) and (`valid_months` is null or json_contains(`valid_months`, ?)) and (`valid_time_ranges` is null or JSON_CONTAINS(valid_time_ranges, '"05:28"')) and (`excluded_dates` is null or NOT JSON_CONTAINS(excluded_dates, '"2025-02-14"')) and (`usage_limit` is null or `applied_count` < `usage_limit`) and (`per_customer_limit` is null or (SELECT COUNT(*) FROM voucher_usages WHERE voucher_usages.voucher_id = vouchers.id \n  
                              AND voucher_usages.customer_id = ?) < vouchers.per_customer_limit and ((`id` = ? and ? < per_customer_daily_limit)) and `applicable_membership_levels` is null or JSON_CONTAINS(applicable_membership_levels, ?))
```
## Phân tích hệ thống

### **Tổng hợp các bảng dữ liệu quan trọng của hệ thống bán hàng quán cà phê**

---

-   ~~**`shelves`**: Lưu danh sách kệ sản phẩm để tái sử dụng.~~
-   ~~**`product_shelves`**: Lưu thông tin kệ sản phẩm để tái sử dụng.~~

## **Quản lý khách hàng và thành viên**

-   **`customers`**: Lưu thông tin khách hàng.

-   **`customer_points`**: Quản lý điểm thưởng của khách hàng.

## **1. Quản lý chi nhánh**

-   **`branches`**: Lưu thông tin các chi nhánh của hệ thống.
-   **`areas`**: Quản lý khu vực trong quán.
-   **`tables_and_rooms`**: Quản lý bàn/phòng sử dụng trong quán.

---

## **2. Quản lý sản phẩm**

-   **`categories`**: Quản lý danh mục sản phẩm theo dạng cha - con.
-   **`attributes`**: Lưu danh sách các thuộc tính sản phẩm.
-   **`products`**: Lưu thông tin sản phẩm bao gồm tên, mã, giá bán, loại sản phẩm, v.v.
-   **`product_branches`**: Quản lý sản phẩm theo từng chi nhánh và tồn kho tại mỗi chi nhánh.
-   **`product_attributes`**: Liên kết sản phẩm với thuộc tính và giá trị của nó.
-   **`product_formulas`**: Quản lý công thức của sản phẩm chế biến.
-   **`product_toppings`**: Lưu danh sách sản phẩm có thể được bán kèm (topping).

---

## **3. Quản lý kho**

-   **`inventory_receipts`**: Lưu thông tin nhập kho, xuất kho và chuyển kho.
-   **`inventory_transactions`**: Chi tiết từng sản phẩm nhập/xuất trong mỗi phiếu kho.

---

## **4. Quản lý đặt hàng và bán hàng**

-   **`orders`**: Lưu thông tin đơn đặt hàng chưa thanh toán.
-   **`order_items`**: Chi tiết từng sản phẩm trong đơn đặt hàng.
-   **`order_toppings`**: Mỗi sản phẩm trong đơn hàng có thể có thêm topping
-   **`order_histories`**: Lưu lịch sử thay đổi trạng thái đơn hàng.

---

## **5. Quản lý hóa đơn**

-   **`invoices`**: Lưu thông tin giao dịch đã thanh toán.
-   **`invoice_details`**: Lưu chi tiết sản phẩm trong hóa đơn.

---

## **6. Quản lý mã giảm giá**

-   **`vouchers`**: Quản lý danh sách mã giảm giá.
-   **`order_vouchers`**: Lưu thông tin mã giảm giá đã sử dụng trong đơn hàng.

---

## Các trường trong từng bảng dữ liệu

-   Bảng dữ liệu **`branches`**: Lưu thông tin các chi nhánh của hệ thống.

    Nội dung file migration tạo các trường của nó như sau:

```php
  $table->string('name');
  $table->string('phone_number')->nullable();
  $table->string('email')->nullable();
  $table->string('address')->nullable();
  $table->enum('status', ['active', 'inactive'])->default('active');
```

---

-   bảng **`areas`**: Quản lý khu vực trong quán.

    Nội dung file migration tạo các trường của nó như sau:

````php
```

---

-   bảng **`tables_and_rooms`**: Quản lý bàn/phòng sử dụng trong quán.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`categories`**: Quản lý danh mục sản phẩm theo dạng cha - con.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`attributes`**: Lưu danh sách các thuộc tính sản phẩm.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`products`**: Lưu thông tin sản phẩm bao gồm tên, mã, giá bán, loại sản phẩm, v.v.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`product_branches`**: Quản lý sản phẩm theo từng chi nhánh và tồn kho tại mỗi chi nhánh.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`product_attributes`**: Liên kết sản phẩm với thuộc tính và giá trị của nó.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`product_formulas`**: Quản lý công thức của sản phẩm chế biến.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`product_toppings`**: Lưu danh sách sản phẩm có thể được bán kèm (topping).



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`inventory_receipts`**: Lưu thông tin nhập kho, xuất kho và chuyển kho.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`inventory_transactions`**: Chi tiết từng sản phẩm nhập/xuất trong mỗi phiếu kho.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`orders`**: Lưu thông tin đơn đặt hàng chưa thanh toán.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`order_items`**: Chi tiết từng sản phẩm trong đơn đặt hàng.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`order_toppings`**: Mỗi sản phẩm trong đơn hàng có thể có thêm topping



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`order_histories`**: Lưu lịch sử thay đổi trạng thái đơn hàng.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`invoices`**: Lưu thông tin giao dịch đã thanh toán.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

-   bảng **`invoice_details`**: Lưu chi tiết sản phẩm trong hóa đơn.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

## **6. Quản lý mã giảm giá**

-   bảng **`vouchers`**: Quản lý danh sách mã giảm giá.



    Nội dung file migration tạo các trường của nó như sau:

```php
```
-   bảng **`order_vouchers`**: Lưu thông tin mã giảm giá đã sử dụng trong đơn hàng.



    Nội dung file migration tạo các trường của nó như sau:

```php
```

---

## **7. Quản lý khách hàng và thành viên**

-   bảng **`customers`**: Lưu thông tin khách hàng.



    Nội dung file migration tạo các trường của nó như sau:

```php
```
-   bảng **`customer_points`**: Quản lý điểm thưởng của khách hàng.



    Nội dung file migration tạo các trường của nó như sau:

```php
````

```

```
