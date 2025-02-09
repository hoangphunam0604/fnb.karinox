<?php

namespace App\Services;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AttributeService
{
  /**
   * Tạo thuộc tính mới
   */
  public function createAttribute(array $data)
  {
    return Attribute::create($data);
  }

  /**
   * Cập nhật thông tin thuộc tính
   */
  public function updateAttribute($attributeId, array $data)
  {
    $attribute = Attribute::findOrFail($attributeId);
    $attribute->update($data);
    return $attribute;
  }

  /**
   * Xóa thuộc tính
   */
  public function deleteAttribute($attributeId)
  {
    $attribute = Attribute::findOrFail($attributeId);
    return $attribute->delete();
  }

  /**
   * Tìm kiếm thuộc tính theo tên
   */
  public function findAttribute($keyword)
  {
    return Attribute::where('name', 'LIKE', "%{$keyword}%")->first();
  }

  /**
   * Lấy danh sách tất cả thuộc tính (phân trang)
   */
  public function getAttributes($perPage = 10)
  {
    return Attribute::orderBy('created_at', 'desc')->paginate($perPage);
  }
}
