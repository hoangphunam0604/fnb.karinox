<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryService
{
  /**
   * Tạo danh mục mới
   */
  public function createCategory(array $data)
  {
    return Category::create($data);
  }

  /**
   * Cập nhật thông tin danh mục
   */
  public function updateCategory($categoryId, array $data)
  {
    $category = Category::findOrFail($categoryId);
    $category->update($data);
    return $category;
  }

  /**
   * Xóa danh mục
   */
  public function deleteCategory($categoryId)
  {
    $category = Category::findOrFail($categoryId);
    return $category->delete();
  }

  /**
   * Tìm kiếm danh mục theo tên
   */
  public function findCategory($keyword)
  {
    return Category::where('name', 'LIKE', "%{$keyword}%")->first();
  }

  /**
   * Lấy danh sách tất cả danh mục (phân trang)
   */
  public function getCategories($perPage = 10)
  {
    return Category::orderBy('created_at', 'desc')->paginate($perPage);
  }
}
