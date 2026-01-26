# Thay đổi cách áp dụng Voucher

## Tổng quan

Voucher giờ đây được áp dụng cho **từng order item** thay vì áp dụng cho toàn bộ đơn hàng.

## Chi tiết thay đổi

### Quy tắc áp dụng

1. **Chỉ áp dụng cho items chưa được giảm giá**: Voucher chỉ được áp dụng cho các `OrderItem` không có `discount_type` (chưa được giảm giá bởi bất kỳ phương thức nào khác).

2. **Phân bổ discount theo tỷ lệ**: Discount từ voucher được phân bổ cho từng item theo tỷ lệ giá trị của item so với tổng giá trị các items hợp lệ.

3. **Lưu thông tin voucher**: Thông tin voucher vẫn được lưu vào `Order` và `VoucherUsage` như trước để dễ quản lý.

### Ví dụ

**Đơn hàng có:**
- Item A: 100,000đ (chưa giảm giá)
- Item B: 200,000đ (đã giảm 10% = `discount_type = 'percent'`)
- Item C: 150,000đ (chưa giảm giá)

**Voucher giảm 20%:**
- Chỉ áp dụng cho Item A và Item C
- Tổng hợp lệ: 250,000đ
- Discount: 50,000đ
- Item A nhận: 50,000đ × (100,000đ / 250,000đ) = 20,000đ
- Item C nhận: 50,000đ × (150,000đ / 250,000đ) = 30,000đ

## Thay đổi trong code

### 1. Method `useVoucher()`

- Lọc ra các items hợp lệ (chưa có `discount_type`)
- Tính tổng discount
- Phân bổ discount cho từng item
- Cập nhật `discount_type = 'voucher'` cho mỗi item

### 2. Method `applyVoucher()`

- Kiểm tra validation dựa trên tổng giá trị items hợp lệ thay vì `total_price` của order

### 3. Method `restoreVoucherUsage()`

- Xóa `discount_type = 'voucher'` khỏi các items khi hoàn lại voucher

### 4. Helper method mới

```php
public function getEligibleTotal(Order $order): float
```

Tính tổng giá trị các items hợp lệ để áp dụng voucher (chỉ tính items chưa được giảm giá).

## Lưu ý cho Frontend

Khi gọi API để lấy danh sách voucher có thể sử dụng (`getMemberRewards`), cần truyền `$totalPrice` là tổng giá trị các items chưa được giảm giá:

```javascript
// Tính tổng giá trị items chưa được giảm giá
const eligibleTotal = order.items
  .filter(item => !item.discount_type)
  .reduce((sum, item) => sum + item.total_price, 0);

// Gọi API với eligibleTotal
const vouchers = await api.getMemberRewards(customerId, eligibleTotal);
```

Hoặc sử dụng helper method từ backend:

```php
$eligibleTotal = $voucherService->getEligibleTotal($order);
$vouchers = $voucherService->getMemberRewards($customerId, $eligibleTotal);
```

## Các trường hợp sử dụng

### Case 1: Tất cả items chưa được giảm giá
✅ Voucher áp dụng bình thường cho tất cả items

### Case 2: Một số items đã được giảm giá
✅ Voucher chỉ áp dụng cho các items chưa giảm giá

### Case 3: Tất cả items đã được giảm giá
❌ Voucher không thể áp dụng (trả về lỗi "Không có sản phẩm nào hợp lệ để áp dụng voucher")

## Migration

Không cần migration database vì:
- Sử dụng các trường hiện có trong `order_items` table
- `discount_type` hiện tại hỗ trợ: `null`, `'percent'`, `'fixed'`, và giờ thêm `'voucher'`

## Testing

Cần kiểm tra các trường hợp:
1. Áp dụng voucher khi tất cả items chưa giảm giá
2. Áp dụng voucher khi một số items đã giảm giá
3. Hoàn lại voucher và kiểm tra items được reset đúng
4. Kiểm tra phân bổ discount chính xác (không bị sai lệch làm tròn)
5. Kiểm tra validation với `min_order_value`
