# 📦 Hệ thống Quản lý Tồn kho Thông minh - Karinox FnB (v2.0)

## 🎯 Tổng quan - Logic Nhất Quán Mới

Hệ thống quản lý tồn kho với **logic nhất quán** - tất cả sản phẩm đều có entries trong `ProductStockDependency`:

- **Nguyên liệu & Hàng hóa**: Self-reference (source_id = target_id, ratio = 1.0)
- **Hàng chế biến & Combo**: Component dependencies từ formulas
- **Dịch vụ**: Có thể có hoặc không có dependencies
- **Deduction Logic**: Luôn check `manage_stock` tại thời điểm trừ kho

### 🔄 Ưu điểm Logic Mới

✅ **Consistency**: Tất cả products đều follow cùng một pattern  
✅ **Flexibility**: `manage_stock` có thể thay đổi mà không cần rebuild dependencies  
✅ **Performance**: O(1) lookup cho mọi loại sản phẩm  
✅ **Maintainability**: Code đơn giản, dễ debug và maintain

## 🏗️ Kiến trúc

### Database Schema

```sql
-- Bảng lưu pre-computed dependencies
CREATE TABLE product_stock_dependencies (
    id BIGINT PRIMARY KEY,
    source_product_id BIGINT,     -- Sản phẩm gốc
    target_product_id BIGINT,     -- Sản phẩm cần trừ kho
    quantity_ratio DECIMAL(10,3), -- Tỷ lệ: 1 source = ? target
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE(source_product_id, target_product_id)
);
```

### Core Services

#### 1. ProductDependencyService

```php
// Tính toán và cập nhật dependencies
updateDependencies(Product $product)
calculateFlatDependencies(Product $product)
rebuildAllDependencies()
validateNoCircularDependency()
```

#### 2. StockDeductionService

```php
// Trừ kho sử dụng pre-computed dependencies
deductStockUsingDependencies(OrderItem $orderItem)
checkStockUsingDependencies(OrderItem $orderItem)
deductStockForTopping(InventoryTransaction $transaction, $topping)
```

#### 3. ProductService

```php
// Auto-update dependencies khi tạo/sửa sản phẩm
saveProduct() // Trigger dependency update
```

#### 4. OrderService

```php
// Trừ kho khi đơn hàng completed
deductStockForCompletedOrder(Order $order)
```

## 🔄 Workflow Hoạt động

### 1. Tạo/Cập nhật Sản phẩm

```mermaid
graph TD
    A[Tạo/Sửa sản phẩm] --> B[ProductService.saveProduct()]
    B --> C{Có formulas?}
    C -->|Có| D[ProductDependencyService.updateDependencies()]
    D --> E[Tính toán flat dependencies]
    E --> F[Lưu vào product_stock_dependencies]
    F --> G[Update parent dependencies]
    C -->|Không| H[Hoàn thành]
```

### 2. Bán hàng và Trừ kho

```mermaid
graph TD
    A[Order chuyển COMPLETED] --> B[OrderService.deductStockForCompletedOrder()]
    B --> C[Duyệt từng OrderItem]
    C --> D[StockDeductionService.deductStockUsingDependencies()]
    D --> E[Lấy pre-computed dependencies]
    E --> F[Trừ kho theo dependencies]
    F --> G[Xử lý toppings]
    G --> H{Topping có dependencies?}
    H -->|Có| I[Trừ theo dependencies]
    H -->|Không| J[Trừ trực tiếp]
    I --> K[Hoàn thành]
    J --> K
```

## 🛠️ Implementation Details

### Tính toán Dependencies

```php
// Ví dụ: Combo Café Sữa
Combo Café Sữa = {
    Cà phê rang: 20g,
    Sữa tươi: 100ml,
    Đường: 10g
}

// Dependencies được tính:
product_stock_dependencies:
- source: Combo Café Sữa, target: Cà phê rang, ratio: 20
- source: Combo Café Sữa, target: Sữa tươi, ratio: 100
- source: Combo Café Sữa, target: Đường, ratio: 10
```

### Xử lý Combo lồng nhau

```php
// Combo A chứa Combo B
Combo Premium = {
    Combo Café Sữa: 1,
    Bánh ngọt: 1
}

// Dependencies cuối cùng:
- source: Combo Premium, target: Cà phê rang, ratio: 20
- source: Combo Premium, target: Sữa tươi, ratio: 100
- source: Combo Premium, target: Đường, ratio: 10
- source: Combo Premium, target: Bánh ngọt, ratio: 1
```

### Xử lý Toppings

```php
// Logic xử lý topping
if (topping has dependencies) {
    // Trừ theo pre-computed dependencies
    deductByDependencies(topping);
} else {
    // Trừ trực tiếp (goods/ingredient)
    deductDirectly(topping);
}
```

## 📊 Performance & Optimization

### Caching Strategy

- **Cache dependencies**: 1 giờ per product
- **Batch processing**: Insert dependencies theo batch
- **Lazy loading**: Chỉ load relationships cần thiết

### Query Optimization

```php
// Thay vì recursive queries
O(depth^n) → O(1) lookup

// Pre-computed dependencies
1 query thay vì N recursive queries
```

## 🚀 Commands & Tools

### Artisan Commands

```bash
# Rebuild tất cả dependencies
php artisan product:rebuild-dependencies

# Kiểm tra dependencies
php artisan tinker --execute="App\Models\ProductStockDependency::count()"
```

### Debugging

```php
// Kiểm tra dependencies của 1 sản phẩm
$dependencies = app(ProductDependencyService::class)->getDependencies($productId);

// Kiểm tra stock cho order item
$hasStock = app(StockDeductionService::class)->checkStockUsingDependencies($orderItem);
```

## ⚠️ Error Handling

### Circular Dependencies

- Validate trước khi save
- Exception với path chi tiết
- Prevent infinite loops

### Stock Insufficient

- Log warning nhưng không block order
- Tiếp tục xử lý các items khác
- Detailed logging cho tracking

### Transaction Safety

- DB transactions cho data consistency
- Rollback khi có lỗi critical
- Separate transactions cho performance

## 🧪 Testing

### Unit Tests

```php
// Test dependency calculation
test_calculate_dependencies_for_combo()
test_circular_dependency_detection()
test_topping_dependency_handling()

// Test stock deduction
test_deduct_stock_using_dependencies()
test_insufficient_stock_handling()
```

### Integration Tests

```php
// Test full workflow
test_order_completion_stock_deduction()
test_complex_combo_stock_calculation()
test_mixed_products_and_toppings()
```

## 📈 Monitoring & Logs

### Key Metrics

- Dependencies calculation time
- Stock deduction success rate
- Cache hit rate
- Error frequency

### Log Patterns

```php
// Success
"Stock deducted successfully" [order_id, product_id, quantity]

// Warning
"Insufficient stock" [product_id, required, available]

// Error
"Error deducting stock" [order_id, error_message]
```

## 🔧 Maintenance

### Regular Tasks

1. **Monitor dependencies table size**
2. **Check for orphaned dependencies**
3. **Validate stock accuracy**
4. **Performance monitoring**

### Troubleshooting

```php
// Rebuild dependencies nếu có vấn đề
php artisan product:rebuild-dependencies

// Clear cache
Cache::flush();

// Check logs
tail -f storage/logs/laravel.log | grep -i stock
```

## 📝 Notes

### Khi thêm loại sản phẩm mới

1. Update `ProductType` enum
2. Update `isPhysicalStockProduct()` method
3. Update dependency calculation logic
4. Update tests

### Khi thay đổi business rules

1. Update validation logic
2. Rebuild dependencies
3. Update documentation
4. Notify team

---

**Created**: 2025-10-16  
**Last Updated**: 2025-10-16  
**Version**: 1.0.0
