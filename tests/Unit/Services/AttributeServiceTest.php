<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Attribute;
use App\Services\AttributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttributeServiceTest extends TestCase
{
  use RefreshDatabase; // Reset database trước mỗi test

  protected $attributeService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->attributeService = new AttributeService();
  }

  /**
   * Test tạo thuộc tính mới
   */
  public function test_create_attribute()
  {
    $attributeData = [
      'name' => 'Màu sắc',
    ];

    $attribute = $this->attributeService->createAttribute($attributeData);

    $this->assertDatabaseHas('attributes', $attributeData);
    $this->assertInstanceOf(Attribute::class, $attribute);
  }

  /**
   * Test cập nhật thông tin thuộc tính
   */
  public function test_update_attribute()
  {
    $attribute = Attribute::factory()->create();

    $updatedData = ['name' => 'Kích thước'];
    $updatedAttribute = $this->attributeService->updateAttribute($attribute->id, $updatedData);

    $this->assertEquals('Kích thước', $updatedAttribute->name);
    $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'name' => 'Kích thước']);
  }

  /**
   * Test tìm kiếm thuộc tính theo tên
   */
  public function test_find_attribute_by_name()
  {
    $attribute = Attribute::factory()->create(['name' => 'Chất liệu']);

    $foundAttribute = $this->attributeService->findAttribute('Chất liệu');

    $this->assertNotNull($foundAttribute);
    $this->assertEquals('Chất liệu', $foundAttribute->name);
  }

  /**
   * Test xóa thuộc tính
   */
  public function test_delete_attribute()
  {
    $attribute = Attribute::factory()->create();

    $this->attributeService->deleteAttribute($attribute->id);

    $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
  }
}
