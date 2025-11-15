- Lỗi Import sản phẩm do tạo danh mục mới từ data (thiếu code_prefix)
- Sản phẩm nên tách riêng từng chi nhánh
- Thông báo sau khi lưu thành công
- Mã đã bán thì không được huỷ bỏ/ thay thế
- Tạo mới, mặc định chi nhánh đang chọn
- Tự động tạo mã theo loại sản phẩm

# Tạo sản phẩm

- Hàng chế biến khi thêm thành phần chỉ được thêm nguyên liệu và hàng hoá
- Hàng hoá không được có thành phần/món thêm
- Hàng chế biến chỉ được thêm hàng hoá/nguyên liệu
- Combo chỉ được thêm hàng hoá/nguyên liệu/hàng chế biến
- Dịch vụ chỉ được thêm hàng hoá/nguyên liệu/hàng chế biến/combo
- Nguyên liệu cần thêm nhóm hàng (dễ kiểm hàng)
- Kho hàng: Chọn nguyên liệu/hàng hoá hiển thị thông tin xuất/nhập theo ngày

# Nhân viên
- Thêm nhân viên order
- Web cho nhân viên order.
- Cho phép thanh toán qua mã QR => chỉ in bill khi thu ngân xác nhận

# POS

- Hiển thị nút dùng điểm theo món => Giá món = 0
- Cho phép thu ngân chỉnh sửa giá món (Cần ghi chú báo cáo)
- Voucher % thì giảm giá tất cả món để báo cáo thuế đúng giá
- Phương thức thanh toán thêm Grab (giống)
- Tự động chọn chương trình giảm giá phù hợp cho thành viên
- Màn hình khách hiển thị sinh nhật nhật, áp dụng giảm giá thành viên hiển thị % và số lượng sử dụng
- Màn hình khách:
  + Danh sách món gọn lại
- Cập nhật trạng thái bàn 

# Admin Hoá đơn

- Số bàn, Giảm giá, người nhận đơn (nv order), người tạo đơn (nv thu ngân), phương thức thanh toán, kênh bán
- Cho phép quản lý kênh bán: Mặc định tại quầy, nếu chọn kênh khác sẽ ẩn phương thức thanh toán, khi thanh toán sẽ lưu phương thức thanh toán theo kênh


Gửi tin nhắn sinh nhật/voucher cho khách qua zalo