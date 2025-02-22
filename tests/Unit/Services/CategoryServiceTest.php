<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CategoryServiceTest extends TestCase
{
  use RefreshDatabase; // Reset database trước mỗi test

  protected $categoryService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->categoryService = new CategoryService();
  }

  /**
   * Test tạo danh mục mới
   */
  #[Test]
  public function test_create_category()
  {
    $categoryData = [
      'name' => 'Đồ uống',
    ];

    $category = $this->categoryService->createCategory($categoryData);

    $this->assertDatabaseHas('categories', $categoryData);
    $this->assertInstanceOf(Category::class, $category);
  }

  /**
   * Test cập nhật thông tin danh mục
   */
  #[Test]
  public function test_update_category()
  {
    $category = Category::factory()->create();

    $updatedData = ['name' => 'Thực phẩm'];
    $updatedCategory = $this->categoryService->updateCategory($category->id, $updatedData);

    $this->assertEquals('Thực phẩm', $updatedCategory->name);
    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Thực phẩm']);
  }

  /**
   * Test tìm kiếm danh mục theo tên
   */
  #[Test]
  public function test_find_category_by_name()
  {
    $category = Category::factory()->create(['name' => 'Bánh ngọt']);

    $foundCategory = $this->categoryService->findCategory('Bánh ngọt');

    $this->assertNotNull($foundCategory);
    $this->assertEquals('Bánh ngọt', $foundCategory->name);
  }

  /**
   * Test xóa danh mục
   */
  #[Test]
  public function test_delete_category()
  {
    $category = Category::factory()->create();

    $this->categoryService->deleteCategory($category->id);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
  }
}
