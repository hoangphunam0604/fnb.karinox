# Auto-Generate Product Code - Hướng Dẫn

## 🎯 **Tổng Quan**

Hệ thống tự động sinh mã sản phẩm theo format: **{PREFIX}{0000}**

**Ví dụ:**

- `CF0001`, `CF0002`, `CF0003` - Cà phê
- `TEA0001`, `TEA0002` - Trà
- `MILK0001`, `MILK0002` - Sữa

---

## 🏗️ **Kiến Trúc**

### **1. Database Changes**

```sql
-- Thêm vào bảng categories
ALTER TABLE categories ADD COLUMN code_prefix VARCHAR(10) UNIQUE;

-- Bảng products đã có code UNIQUE
```

### **2. Services Created**

- `ProductCodeService` - Logic generate code
- `CategoryService` - Enhanced với auto-prefix

### **3. Model Changes**

- `Category` - Thêm `code_prefix` vào fillable
- `Product` - Auto-generate code trong boot event

---

## 🚀 **Cách Sử Dụng**

### **Tạo Category Mới**

```php
$categoryService = new CategoryService(new ProductCodeService());

// Cách 1: Tự động generate prefix
$category = $categoryService->create([
    'name' => 'Cà phê',
    // code_prefix sẽ tự động = 'CF'
]);

// Cách 2: Chỉ định prefix thủ công
$category = $categoryService->create([
    'name' => 'Cà phê',
    'code_prefix' => 'COFFEE'
]);
```

### **Tạo Product Mới**

```php
// Code sẽ tự động generate dựa trên category
$product = Product::create([
    'name' => 'Cà phê đen',
    'category_id' => 1, // Category có prefix 'CF'
    // code sẽ tự động = 'CF0001', 'CF0002', etc.
]);

// Hoặc chỉ định code thủ công
$product = Product::create([
    'name' => 'Cà phê đen',
    'category_id' => 1,
    'code' => 'CF9999' // Override auto-generation
]);
```

---

## 🧠 **Logic Generate Code**

### **1. Auto-Generate Prefix từ Tên**

```php
$codeService = new ProductCodeService();

// Mapping thông minh cho tiếng Việt
'Cà phê' → 'CF'
'Trà xanh' → 'TEA'
'Sữa tươi' → 'MILK'
'Topping' → 'TOP'
'Bánh ngọt' → 'CAKE'

// Fallback: Lấy 3 ký tự đầu (bỏ dấu)
'Gia vị' → 'GIA'
'Đồ uống' → 'DOD'
```

### **2. Sequential Numbering**

```php
// Tìm product cuối cùng của category (theo ID)
$lastProduct = Product::where('category_id', $categoryId)
    ->where('code', 'LIKE', $prefix . '%')
    ->orderBy('id', 'desc') // ⚠️ Quan trọng: theo ID, không theo code
    ->first();

// Tăng số
$nextNumber = extractNumber($lastProduct->code) + 1;
$newCode = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
```

### **3. Xử Lý Gap (Sản phẩm bị xóa)**

❌ **KHÔNG fill gap** - Tiếp tục sequence

```
CF0001 ✅
CF0002 ❌ (đã xóa)
CF0003 ✅
CF0004 ← Sản phẩm mới (không dùng CF0002)
```

---

## 🔧 **API Methods**

### **ProductCodeService**

```php
// Generate code cho product
generateProductCode(?int $categoryId): string

// Generate prefix từ tên
generatePrefixFromName(string $categoryName): string

// Validate code format
isValidProductCode(string $code): bool

// Check code exists
isCodeExists(string $code, ?int $excludeProductId = null): bool
```

### **CategoryService**

```php
// Tạo category với auto-prefix
create(array $data): Category

// Update category
update(int $id, array $data): Category

// Suggest prefix cho frontend
suggestPrefix(string $categoryName): array
```

---

## ✅ **Validation Rules**

### **Product Code Format**

- **Pattern:** `^[A-Z]{2,10}\d{4}$`
- **Prefix:** 2-10 ký tự IN HOA
- **Number:** Đúng 4 digits (0001-9999)

### **Examples**

```
✅ CF0001, TEA0023, MILK1234, TOPPING0001
❌ cf0001 (lowercase), CF01 (thiếu digits), VERYLONGPREFIX0001 (prefix quá dài)
```

---

## 🧪 **Testing**

### **Test Cases**

1. **Generate prefix từ tên tiếng Việt**
2. **Auto-generate code sequence**
3. **Handle gap khi xóa sản phẩm**
4. **Validate code format**
5. **Unique constraint**

### **Manual Test**

```bash
# Tạo categories
curl -X POST /api/admin/categories \
  -d '{"name": "Cà phê"}' # → code_prefix: CF

# Tạo products
curl -X POST /api/admin/products \
  -d '{"name": "Cà phê đen", "category_id": 1}' # → code: CF0001

curl -X POST /api/admin/products \
  -d '{"name": "Cà phê sữa", "category_id": 1}' # → code: CF0002

# Xóa CF0002, tạo product mới
# → code: CF0003 (không fill gap)
```

---

## ⚠️ **Lưu Ý Quan Trọng**

1. **Migration:** Cần migrate database trước khi dùng
2. **Existing Data:** Sản phẩm cũ cần update code manually
3. **Performance:** Query `orderBy('id', 'desc')` có index
4. **Unique Constraint:** Database enforce unique code
5. **Transaction:** Auto-generation trong transaction
6. **Fallback:** Nếu category không có prefix → PRD0001

---

## 🎉 **Benefits**

✅ **Consistency** - Format code nhất quán  
✅ **Automation** - Không cần nhập code thủ công  
✅ **Scalability** - Support 9999 products/category  
✅ **Flexibility** - Có thể override khi cần  
✅ **Vietnamese-friendly** - Smart mapping cho tiếng Việt  
✅ **Gap-safe** - Không conflict khi xóa products

---

## 🔮 **Future Enhancements**

- [ ] Reset counter theo năm (CF2025001)
- [ ] Bulk import với auto-code
- [ ] Code history/audit trail
- [ ] Custom format per category
- [ ] Barcode integration
