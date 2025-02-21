<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\TableAndRoom;
use App\Models\Area;
use App\Services\TableAndRoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TableAndRoomServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $tableAndRoomService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->tableAndRoomService = new TableAndRoomService();
  }

  public function test_create_table_or_room()
  {
    $area = Area::factory()->create();
    $data = [
      'name' => 'Phòng VIP 1',
      'area_id' => $area->id,
      'capacity' => 10,
      'status' => 'reserved',
      'note' => 'Phòng đặc biệt cho khách VIP',
    ];

    $tableOrRoom = $this->tableAndRoomService->saveTableOrRoom($data);

    $this->assertDatabaseHas('tables_and_rooms', ['name' => 'Phòng VIP 1']);
    $this->assertEquals('reserved', $tableOrRoom->status);
  }

  public function test_update_table_or_room()
  {
    $tableOrRoom = TableAndRoom::factory()->create();

    $updatedData = ['name' => 'Bàn số 5', 'capacity' => 4, 'status' => 'occupied'];
    $updatedTableOrRoom = $this->tableAndRoomService->saveTableOrRoom($updatedData, $tableOrRoom->id);

    $this->assertEquals('Bàn số 5', $updatedTableOrRoom->name);
    $this->assertEquals('occupied', $updatedTableOrRoom->status);
    $this->assertDatabaseHas('tables_and_rooms', ['id' => $tableOrRoom->id, 'name' => 'Bàn số 5', 'status' => 'occupied']);
  }

  public function test_find_table_or_room_by_name()
  {
    $tableOrRoom = TableAndRoom::factory()->create(['name' => 'Bàn ngoài trời']);

    $foundTableOrRoom = $this->tableAndRoomService->findTableOrRoom('Bàn ngoài trời');

    $this->assertNotNull($foundTableOrRoom);
    $this->assertEquals('Bàn ngoài trời', $foundTableOrRoom->name);
  }

  public function test_delete_table_or_room()
  {
    $tableOrRoom = TableAndRoom::factory()->create();

    $this->tableAndRoomService->deleteTableOrRoom($tableOrRoom->id);

    $this->assertDatabaseMissing('tables_and_rooms', ['id' => $tableOrRoom->id]);
  }

  /**
   * Test lấy danh sách phòng/bàn theo trạng thái
   */
  public function test_get_tables_and_rooms_by_status()
  {
    TableAndRoom::factory()->count(3)->create(['status' => 'available']);
    TableAndRoom::factory()->count(2)->create(['status' => 'reserved']);

    $availableTables = $this->tableAndRoomService->getTablesAndRoomsByStatus('available');

    $this->assertCount(3, $availableTables);
  }

  /**
   * Test cập nhật trạng thái phòng/bàn
   */
  public function test_update_table_or_room_status()
  {
    $tableOrRoom = TableAndRoom::factory()->create(['status' => 'available']);

    $updatedTableOrRoom = $this->tableAndRoomService->updateTableOrRoomStatus($tableOrRoom->id, 'occupied');

    $this->assertEquals('occupied', $updatedTableOrRoom->status);
    $this->assertDatabaseHas('tables_and_rooms', ['id' => $tableOrRoom->id, 'status' => 'occupied']);
  }

  /**
   * Test kiểm tra phòng/bàn có thể sử dụng không
   */
  public function test_can_use_table_or_room()
  {
    $availableTable = TableAndRoom::factory()->create(['status' => 'available']);
    $occupiedTable = TableAndRoom::factory()->create(['status' => 'occupied']);

    $this->assertTrue($this->tableAndRoomService->canUseTableOrRoom($availableTable->id));
    $this->assertFalse($this->tableAndRoomService->canUseTableOrRoom($occupiedTable->id));
  }
}
