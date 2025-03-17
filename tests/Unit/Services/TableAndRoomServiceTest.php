<?php

namespace Tests\Unit\Services;

use App\Enums\TableAndRoomStatus;
use Tests\TestCase;
use App\Models\TableAndRoom;
use App\Models\Area;
use App\Services\TableAndRoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

class TableAndRoomServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $tableAndRoomService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->tableAndRoomService = new TableAndRoomService();
  }

  #[Test]
  public function test_create_table_or_room()
  {
    $area = Area::factory()->create();
    $data = [
      'name' => 'Phòng VIP 1',
      'area_id' => $area->id,
      'capacity' => 10,
      'status' => TableAndRoomStatus::RESERVED,
      'note' => 'Phòng đặc biệt cho khách VIP',
    ];

    $tableOrRoom = $this->tableAndRoomService->saveTableOrRoom($data);

    $this->assertDatabaseHas('tables_and_rooms', ['name' => 'Phòng VIP 1']);
    $this->assertEquals(TableAndRoomStatus::RESERVED, $tableOrRoom->status);
  }

  #[Test]
  public function test_update_table_or_room()
  {
    $tableOrRoom = TableAndRoom::factory()->create();

    $updatedData = ['name' => 'Bàn số 5', 'capacity' => 4, 'status' => TableAndRoomStatus::OCCUPIED];
    $updatedTableOrRoom = $this->tableAndRoomService->saveTableOrRoom($updatedData, $tableOrRoom->id);

    $this->assertEquals('Bàn số 5', $updatedTableOrRoom->name);
    $this->assertEquals(TableAndRoomStatus::OCCUPIED, $updatedTableOrRoom->status);
    $this->assertDatabaseHas('tables_and_rooms', [
      'id' => $tableOrRoom->id,
      'name' => 'Bàn số 5',
      'status' => TableAndRoomStatus::OCCUPIED
    ]);
  }

  #[Test]
  public function test_find_table_or_room_by_name()
  {
    TableAndRoom::factory()->create(['name' => 'Bàn ngoài trời']);
    $foundTableOrRoom = $this->tableAndRoomService->findTableOrRoom('Bàn ngoài trời');

    $this->assertNotNull($foundTableOrRoom);
    $this->assertEquals('Bàn ngoài trời', $foundTableOrRoom->name);
  }

  #[Test]
  public function test_delete_table_or_room()
  {
    $tableOrRoom = TableAndRoom::factory()->create();

    $this->tableAndRoomService->deleteTableOrRoom($tableOrRoom->id);

    $this->assertDatabaseMissing('tables_and_rooms', ['id' => $tableOrRoom->id]);
  }

  #[Test]
  #[TestDox("Test lấy danh sách phòng/bàn theo trạng thái")]
  public function test_get_tables_and_rooms_by_status()
  {
    TableAndRoom::factory()->count(3)->create(['status' => TableAndRoomStatus::AVAILABLE]);
    TableAndRoom::factory()->count(2)->create(['status' =>  TableAndRoomStatus::RESERVED]);

    $availableTables = $this->tableAndRoomService->getTablesAndRoomsByStatus('available');

    $this->assertCount(3, $availableTables);
  }


  #[Test]
  #[TestDox("Test cập nhật trạng thái phòng/bàn")]
  public function test_update_table_or_room_status()
  {
    $tableOrRoom = TableAndRoom::factory()->create(['status' => 'available']);

    $updatedTableOrRoom = $this->tableAndRoomService->updateTableOrRoomStatus($tableOrRoom->id, TableAndRoomStatus::OCCUPIED);

    $this->assertEquals(TableAndRoomStatus::OCCUPIED, $updatedTableOrRoom->status);
    $this->assertDatabaseHas('tables_and_rooms', ['id' => $tableOrRoom->id, 'status' => TableAndRoomStatus::OCCUPIED]);
  }


  #[Test]
  #[TestDox("Test kiểm tra phòng/bàn có thể sử dụng không")]
  public function test_can_use_table_or_room()
  {
    $availableTable = TableAndRoom::factory()->create(['status' => TableAndRoomStatus::AVAILABLE]);
    $occupiedTable = TableAndRoom::factory()->create(['status' => TableAndRoomStatus::OCCUPIED]);

    $this->assertTrue($this->tableAndRoomService->canUseTableOrRoom($availableTable->id));
    $this->assertFalse($this->tableAndRoomService->canUseTableOrRoom($occupiedTable->id));
  }
}
