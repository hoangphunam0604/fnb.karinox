<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Area;
use App\Models\Branch;
use App\Services\AreaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AreaServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $areaService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->areaService = new AreaService();
  }

  /**
   * Test tạo khu vực mới
   */
  public function test_create_area()
  {
    $branch = Branch::factory()->create();
    $areaData = [
      'name' => 'Khu vực VIP',
      'branch_id' => $branch->id,
      'note' => 'Dành cho khách hàng đặc biệt',
    ];

    $area = $this->areaService->saveArea($areaData);

    $this->assertDatabaseHas('areas', ['name' => 'Khu vực VIP']);
    $this->assertInstanceOf(Area::class, $area);
  }

  /**
   * Test cập nhật khu vực
   */
  public function test_update_area()
  {
    $area = Area::factory()->create();

    $updatedData = ['name' => 'Khu vực thường'];
    $updatedArea = $this->areaService->saveArea($updatedData, $area->id);

    $this->assertEquals('Khu vực thường', $updatedArea->name);
    $this->assertDatabaseHas('areas', ['id' => $area->id, 'name' => 'Khu vực thường']);
  }

  /**
   * Test tìm kiếm khu vực theo tên
   */
  public function test_find_area_by_name()
  {
    $area = Area::factory()->create(['name' => 'Khu sân thượng']);

    $foundArea = $this->areaService->findArea('Khu sân thượng');

    $this->assertNotNull($foundArea);
    $this->assertEquals('Khu sân thượng', $foundArea->name);
  }

  /**
   * Test xóa khu vực
   */
  public function test_delete_area()
  {
    $area = Area::factory()->create();

    $this->areaService->deleteArea($area->id);

    $this->assertDatabaseMissing('areas', ['id' => $area->id]);
  }
}
