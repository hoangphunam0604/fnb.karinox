# 📋 **CÁC TRƯỜNG HỢP THẺ KHO (STOCK CARD)**

Thẻ kho là báo cáo theo dõi chi tiết lịch sử xuất nhập tồn của từng sản phẩm tại mỗi chi nhánh.

---

## 🔄 **1. NHẬP KHO (IMPORT)**

### **Mô tả**

- Tăng số lượng tồn kho
- Ghi nhận hàng hóa vào kho

### **Trường hợp sử dụng**

- ✅ **Nhập hàng từ nhà cung cấp** - Mua hàng mới
- ✅ **Bổ sung tồn kho** - Đặt thêm hàng khi hết
- ✅ **Nhập nguyên liệu** - Coffee beans, sữa, đường...
- ✅ **Nhập packaging** - Ly, nắp, ống hút...

### **API Response**

```json
{
    "type": "import",
    "type_label": "Nhập kho",
    "quantity_change": 100, // Số dương
    "quantity_before": 50,
    "quantity_after": 150
}
```

### **Ví dụ thực tế**

```
Ngày: 18/10/2024 09:00
Sản phẩm: Cà phê Arabica
Số lượng: +50 kg
Ghi chú: "Nhập hàng từ nhà cung cấp Trung Nguyên"
```

---

## 📤 **2. XUẤT KHO (EXPORT)**

### **Mô tả**

- Giảm số lượng tồn kho
- Hàng hóa ra khỏi kho (không phải bán)

### **Trường hợp sử dụng**

- ✅ **Chuyển hàng nội bộ** - Từ kho chính đến bar
- ✅ **Loại bỏ hàng hỏng** - Expiry, damaged
- ✅ **Sample/Testing** - Thử nghiệm công thức mới
- ✅ **Staff consumption** - Tiêu thụ nội bộ

### **API Response**

```json
{
    "type": "export",
    "type_label": "Xuất kho",
    "quantity_change": -20, // Số âm
    "quantity_before": 150,
    "quantity_after": 130
}
```

### **Ví dụ thực tế**

```
Ngày: 18/10/2024 14:30
Sản phẩm: Sữa tươi
Số lượng: -5 lít
Ghi chú: "Loại bỏ sữa quá hạn sử dụng"
```

---

## 🛒 **3. BÁN HÀNG (SALE)**

### **Mô tả**

- Giảm tồn kho do bán cho khách hàng
- Giao dịch tạo doanh thu

### **Trường hợp sử dụng**

- ✅ **Bán lẻ** - Khách order tại quầy
- ✅ **Delivery** - Giao hàng tận nơi
- ✅ **Takeaway** - Khách mang về
- ✅ **Corporate orders** - Đơn hàng công ty

### **API Response**

```json
{
    "type": "sale",
    "type_label": "Bán hàng",
    "quantity_change": -3, // Số âm
    "quantity_before": 130,
    "quantity_after": 127
}
```

### **Ví dụ thực tế**

```
Ngày: 18/10/2024 16:45
Sản phẩm: Cà phê đen đá
Số lượng: -3 ly
Ghi chú: "Đơn hàng #KRX-2024101800123"
```

---

## 🔄 **4. TRẢ HÀNG (RETURN)**

### **Mô tả**

- Tăng tồn kho do khách trả lại
- Hoàn trả sản phẩm vào kho

### **Trường hợp sử dụng**

- ✅ **Customer return** - Khách không hài lòng
- ✅ **Wrong order** - Giao nhầm đơn hàng
- ✅ **Quality issue** - Vấn đề chất lượng
- ✅ **Order cancellation** - Hủy đơn sau khi làm

### **API Response**

```json
{
    "type": "return",
    "type_label": "Trả hàng",
    "quantity_change": 2, // Số dương
    "quantity_before": 127,
    "quantity_after": 129
}
```

### **Ví dụ thực tế**

```
Ngày: 18/10/2024 17:20
Sản phẩm: Cappuccino
Số lượng: +1 ly
Ghi chú: "Khách không hài lòng về vị"
```

---

## 🚚 **5. CHUYỂN ĐI (TRANSFER_OUT)**

### **Mô tả**

- Xuất hàng để chuyển đến chi nhánh khác
- Giảm tồn kho chi nhánh hiện tại

### **Trường hợp sử dụng**

- ✅ **Rebalancing stock** - Cân bằng tồn kho giữa chi nhánh
- ✅ **New branch support** - Hỗ trợ chi nhánh mới
- ✅ **Emergency supply** - Cung cấp khẩn cấp
- ✅ **Centralized distribution** - Phân phối từ kho trung tâm

### **API Response**

```json
{
    "type": "transfer_out",
    "type_label": "Chuyển đi",
    "quantity_change": -25, // Số âm
    "quantity_before": 129,
    "quantity_after": 104
}
```

### **Ví dụ thực tế**

```
Ngày: 18/10/2024 10:00
Sản phẩm: Green tea
Từ: Chi nhánh Quận 1
Đến: Chi nhánh Quận 7
Số lượng: -25 gói
Ghi chú: "Chuyển hàng hỗ trợ Q7 thiếu stock"
```

---

## 📦 **6. CHUYỂN ĐẾN (TRANSFER_IN)**

### **Mô tả**

- Nhận hàng từ chi nhánh khác
- Tăng tồn kho chi nhánh hiện tại

### **Trường hợp sử dụng**

- ✅ **Receiving transfer** - Nhận hàng chuyển từ chi nhánh khác
- ✅ **Stock redistribution** - Tái phân bổ tồn kho
- ✅ **Backup supply** - Nguồn cung dự phòng
- ✅ **Hub distribution** - Nhận từ trung tâm phân phối

### **API Response**

```json
{
    "type": "transfer_in",
    "type_label": "Chuyển đến",
    "quantity_change": 25, // Số dương
    "quantity_before": 80,
    "quantity_after": 105
}
```

### **Ví dụ thực tế**

```
Ngày: 18/10/2024 15:30
Sản phẩm: Green tea
Từ: Chi nhánh Quận 1
Đến: Chi nhánh Quận 7 (hiện tại)
Số lượng: +25 gói
Ghi chú: "Nhận hàng từ Q1"
```

---

## 📊 **7. KIỂM KHO (STOCKTAKING)**

### **Mô tả**

- Điều chỉnh tồn kho dựa trên kiểm đếm thực tế
- Có thể tăng hoặc giảm tùy theo kết quả kiểm kê

### **Trường hợp sử dụng**

- ✅ **Physical count** - Kiểm đếm thực tế định kỳ
- ✅ **Cycle counting** - Kiểm kê luân phiên
- ✅ **Audit adjustment** - Điều chỉnh sau kiểm toán
- ✅ **Discrepancy resolution** - Giải quyết sai lệch

### **API Response (Thiếu hàng)**

```json
{
    "type": "stocktaking",
    "type_label": "Kiểm kho",
    "quantity_change": -3, // Thiếu 3 sản phẩm
    "quantity_before": 105,
    "quantity_after": 102
}
```

### **API Response (Thừa hàng)**

```json
{
    "type": "stocktaking",
    "type_label": "Kiểm kho",
    "quantity_change": 2, // Thừa 2 sản phẩm
    "quantity_before": 105,
    "quantity_after": 107
}
```

### **Ví dụ thực tế**

```
Ngày: 31/10/2024 18:00
Sản phẩm: Coffee beans
Số lượng hệ thống: 105 kg
Số lượng thực tế: 102 kg
Chênh lệch: -3 kg
Ghi chú: "Kiểm kê cuối tháng - phát hiện thiếu hụt"
```

---

## 🔍 **PHÂN LOẠI THEO TÁC ĐỘNG TỒN KHO**

### **📈 TĂNG TỒN KHO**

- ✅ **Import** - Nhập kho
- ✅ **Return** - Trả hàng
- ✅ **Transfer In** - Chuyển đến
- ✅ **Stocktaking** (+) - Kiểm kho thặng dư

### **📉 GIẢM TỒN KHO**

- ❌ **Export** - Xuất kho
- ❌ **Sale** - Bán hàng
- ❌ **Transfer Out** - Chuyển đi
- ❌ **Stocktaking** (-) - Kiểm kho thiếu hụt

---

## 🎯 **SỬ DỤNG API THẺ KHO**

### **Xem tất cả giao dịch**

```bash
GET /api/admin/inventory/product-card/1
```

### **Filter theo loại giao dịch**

```bash
GET /api/admin/inventory/product-card/1?type=sale
GET /api/admin/inventory/product-card/1?type=import
```

### **Filter theo thời gian**

```bash
GET /api/admin/inventory/product-card/1?from_date=2024-10-01&to_date=2024-10-31
```

### **Kết hợp filters**

```bash
GET /api/admin/inventory/product-card/1?type=sale&from_date=2024-10-18&per_page=50
```

---

## 💡 **LƯU Ý QUAN TRỌNG**

1. **Quantity Change**:

    - Số dương (+) = Tăng tồn
    - Số âm (-) = Giảm tồn

2. **Reference Number**:

    - Import: `IMP-2024101800001`
    - Sale: `SALE-2024101800002`
    - Transfer: `TRF-2024101800003`

3. **Branch Context**:

    - Mỗi giao dịch thuộc về 1 chi nhánh cụ thể
    - Transfer có cả source và destination branch

4. **User Tracking**:

    - Ghi nhận người thực hiện giao dịch
    - Audit trail đầy đủ

5. **Cost Tracking**:
    - Unit cost và total cost
    - Tính toán giá trị tồn kho

Thẻ kho giúp **theo dõi chi tiết mọi biến động** của sản phẩm, từ nhập hàng đến bán hàng, đảm bảo **tính chính xác và minh bạch** trong quản lý kho! 📊
