# Cập nhật chức năng quản lý khu vực (Area Management)

## Tổng quan

Chức năng quản lý khu vực đã được cập nhật để hỗ trợ tạo tự động phòng/bàn khi tạo hoặc cập nhật khu vực. Điều này giúp tiết kiệm thời gian và tăng hiệu quả trong việc thiết lập khu vực mới.

## Các tính năng mới

### 1. Tạo tự động phòng/bàn khi tạo khu vực

Khi tạo khu vực mới, có thể chỉ định:

- `table_prefix`: Tên gọi cho phòng/bàn (ví dụ: "Bàn", "Phòng", "PV")
- `table_count`: Số lượng phòng/bàn cần tạo (tối đa 100)
- `table_capacity`: Số ghế mặc định cho mỗi phòng/bàn (từ 1-20, mặc định 4)

### 2. Thêm phòng/bàn khi cập nhật khu vực

Khi cập nhật khu vực, có thể tạo thêm phòng/bàn mới với việc đánh số tiếp tục từ số hiện tại.

### 3. Validation nâng cao

- Giới hạn số lượng phòng/bàn tối đa 100 để tránh tạo quá nhiều
- Validation tên gọi phòng/bàn (tối đa 50 ký tự)
- Validation số ghế (từ 1-20 chỗ ngồi)
- Thông báo lỗi bằng tiếng Việt

## API Endpoints

### POST /api/admin/areas

**Headers:**

```
Authorization: Bearer {token}
Karinox-Branch-Id: {branch_id}
karinox-app-id: karinox-app-admin
```

**Request Body:**

````json
**Request Body:**
```json
{
  "name": "Tầng 1",
  "note": "Khu vực tầng 1",
  "table_prefix": "Bàn",
  "table_count": 10,
  "table_capacity": 6
}
````

````

**Response:**

```json
{
    "data": {
        "id": 1,
        "name": "Tầng 1",
        "note": "Khu vực tầng 1",
        "branch_id": 1,
        "tables_count": 10,
        "tables_and_rooms": [
            {
                "id": 1,
                "name": "Bàn 01",
                "capacity": 6,
                "status": "available"
            }
        ],
        "created_at": "2025-10-19T01:12:07.000000Z",
        "updated_at": "2025-10-19T01:12:07.000000Z"
    }
}
````

### PUT /api/admin/areas/{id}

**Request Body:**

```json
{
    "name": "Tầng 1 - Updated",
    "table_prefix": "Bàn",
    "table_count": 5
}
```

## Quy tắc đánh số

1. **Tạo mới**: Phòng/bàn được đánh số từ 01, 02, 03...
2. **Thêm mới**: Tiếp tục từ số cuối cùng hiện có
3. **Format**: Luôn có 2 chữ số với số 0 đầu (01, 02, ..., 10, 11...)

## Ví dụ sử dụng

### Tạo khu vực với bàn tự động

```bash
curl -X POST /api/admin/areas \
  -H "Authorization: Bearer {token}" \
  -H "Karinox-Branch-Id: 1" \
  -H "karinox-app-id: karinox-app-admin" \
  -d '{
    "name": "Khu vực VIP",
    "table_prefix": "Phòng VIP",
    "table_count": 5,
    "table_capacity": 8
  }'
```

Kết quả: Tạo 5 phòng với tên "Phòng VIP 01", "Phòng VIP 02", ..., "Phòng VIP 05", mỗi phòng có 8 chỗ ngồi### Thêm bàn cho khu vực hiện có

```bash
curl -X PUT /api/admin/areas/1 \
  -H "Authorization: Bearer {token}" \
  -H "karinox-app-id: karinox-app-admin" \
  -d '{
    "name": "Khu vực VIP",
    "table_prefix": "Bàn",
    "table_count": 3
  }'
```

Nếu đã có 5 bàn, sẽ tạo thêm 3 bàn: "Bàn 06", "Bàn 07", "Bàn 08"

## Các thay đổi technical

### 1. AreaRequest

- Thêm fields: `table_prefix`, `table_count`
- Validation rules và messages tiếng Việt

### 2. AreaService

- Method `create()`: Tạo area và tự động tạo tables
- Method `createTablesForArea()`: Logic tạo tables với đánh số thông minh

### 3. AreaResource

- Bao gồm thông tin về tables: `tables_count`, `tables_and_rooms`
- Conditional loading để tối ưu performance

### 4. AreaController

- Xử lý branch_id từ request hoặc middleware
- Validation và error handling

## Testing

Đã tạo comprehensive test suite với 6 test cases:

- Tạo khu vực không có bàn
- Tạo khu vực với bàn tự động
- Custom table prefix
- Đánh số tiếp tục khi có bàn sẵn
- Validation giới hạn
- Lấy thông tin khu vực với bàn

Chạy tests:

```bash
php artisan test tests/Feature/AreaManagementTest.php
```
