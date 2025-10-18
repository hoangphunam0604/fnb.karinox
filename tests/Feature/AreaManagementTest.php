<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Branch;
use App\Models\TableAndRoom;
use App\Models\User;
use App\Enums\TableAndRoomStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AreaManagementTest extends TestCase
{
  use RefreshDatabase;

  protected $branch;
  protected $user;
  protected $token;

  protected function setUp(): void
  {
    parent::setUp();
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create();

    // Tạo role admin để user có quyền truy cập
    $adminRole = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    $this->user->assignRole($adminRole);

    $this->token = JWTAuth::fromUser($this->user);
  }

  private function getHeaders(): array
  {
    return [
      'Authorization' => 'Bearer ' . $this->token,
      'Accept' => 'application/json',
      'Karinox-Branch-Id' => $this->branch->id,
      'karinox-app-id' => 'karinox-app-admin',
    ];
  }

  public function test_can_create_area_without_tables()
  {
    $response = $this->withHeaders($this->getHeaders())
      ->postJson('/api/admin/areas', [
        'name' => 'Tầng 1',
        'note' => 'Khu vực tầng 1',
      ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
      'data' => [
        'id',
        'name',
        'note',
        'branch_id',
        'tables_count',
        'created_at',
        'updated_at',
      ]
    ]);

    $this->assertEquals('Tầng 1', $response->json('data.name'));
    $this->assertEquals($this->branch->id, $response->json('data.branch_id'));
    $this->assertEquals(0, $response->json('data.tables_count'));
  }

  public function test_can_create_area_with_automatic_table_generation()
  {
    $response = $this->withHeaders($this->getHeaders())
      ->postJson('/api/admin/areas', [
        'name' => 'Tầng 2',
        'note' => 'Khu vực tầng 2',
        'table_prefix' => 'Bàn',
        'table_count' => 5,
        'table_capacity' => 6,
      ]);

    $response->assertStatus(201);

    $areaId = $response->json('data.id');

    // Kiểm tra area được tạo
    $this->assertEquals('Tầng 2', $response->json('data.name'));
    $this->assertEquals($this->branch->id, $response->json('data.branch_id'));

    // Kiểm tra tables được tạo tự động
    $tables = TableAndRoom::where('area_id', $areaId)->get();
    $this->assertCount(5, $tables);

    // Kiểm tra tên tables
    $expectedNames = ['Bàn 01', 'Bàn 02', 'Bàn 03', 'Bàn 04', 'Bàn 05'];
    $this->assertEquals($expectedNames, $tables->pluck('name')->toArray());

    // Kiểm tra tất cả tables đều có status AVAILABLE
    $this->assertTrue($tables->every(fn($table) => $table->status === TableAndRoomStatus::AVAILABLE));

    // Kiểm tra capacity theo request
    $this->assertTrue($tables->every(fn($table) => $table->capacity === 6));
  }

  public function test_can_create_area_with_custom_table_prefix()
  {
    $response = $this->withHeaders($this->getHeaders())
      ->postJson('/api/admin/areas', [
        'name' => 'VIP',
        'table_prefix' => 'Phòng VIP',
        'table_count' => 3,
      ]);

    $response->assertStatus(201);

    $areaId = $response->json('data.id');
    $tables = TableAndRoom::where('area_id', $areaId)->get();

    $expectedNames = ['Phòng VIP 01', 'Phòng VIP 02', 'Phòng VIP 03'];
    $this->assertEquals($expectedNames, $tables->pluck('name')->toArray());
  }

  public function test_tables_numbering_continues_when_area_already_has_tables()
  {
    // Tạo area với tables có tên cụ thể
    $area = Area::factory()->create(['branch_id' => $this->branch->id]);
    TableAndRoom::create([
      'name' => 'Bàn 01',
      'area_id' => $area->id,
      'capacity' => 4,
      'status' => \App\Enums\TableAndRoomStatus::AVAILABLE,
    ]);
    TableAndRoom::create([
      'name' => 'Bàn 02',
      'area_id' => $area->id,
      'capacity' => 4,
      'status' => \App\Enums\TableAndRoomStatus::AVAILABLE,
    ]);

    $response = $this->withHeaders($this->getHeaders())
      ->putJson("/api/admin/areas/{$area->id}", [
        'name' => $area->name,
        'table_prefix' => 'Bàn',
        'table_count' => 3,
      ]);

    $response->assertStatus(200);

    // Kiểm tra có tổng cộng 5 bàn (2 cũ + 3 mới)
    $this->assertEquals(5, TableAndRoom::where('area_id', $area->id)->count());

    // Kiểm tra các bàn mới được tạo với tên đúng
    $allTableNames = TableAndRoom::where('area_id', $area->id)
      ->pluck('name')
      ->sort()
      ->values()
      ->toArray();

    $expected = ['Bàn 01', 'Bàn 02', 'Bàn 03', 'Bàn 04', 'Bàn 05'];
    $this->assertEquals($expected, $allTableNames);
  }

  public function test_can_create_area_with_custom_table_capacity()
  {
    $response = $this->withHeaders($this->getHeaders())
      ->postJson('/api/admin/areas', [
        'name' => 'Khu vực nhỏ',
        'table_prefix' => 'Bàn',
        'table_count' => 3,
        'table_capacity' => 2,
      ]);

    $response->assertStatus(201);

    $areaId = $response->json('data.id');
    $tables = TableAndRoom::where('area_id', $areaId)->get();

    // Kiểm tra tất cả bàn có capacity = 2
    $this->assertTrue($tables->every(fn($table) => $table->capacity === 2));
  }

  public function test_validation_fails_when_table_count_exceeds_limit()
  {
    $response = $this->withHeaders($this->getHeaders())
      ->postJson('/api/admin/areas', [
        'name' => 'Large Area',
        'table_count' => 150, // Vượt quá giới hạn 100
      ]);

    $response->assertStatus(422);
    $response->assertJsonStructure([
      'success',
      'message',
      'messages' => [
        'table_count'
      ]
    ]);
  }

  public function test_validation_fails_when_table_capacity_exceeds_limit()
  {
    $response = $this->withHeaders($this->getHeaders())
      ->postJson('/api/admin/areas', [
        'name' => 'Test Area',
        'table_count' => 5,
        'table_capacity' => 25, // Vượt quá giới hạn 20
      ]);

    $response->assertStatus(422);
    $response->assertJsonStructure([
      'success',
      'message',
      'messages' => [
        'table_capacity'
      ]
    ]);
  }

  public function test_can_get_area_with_tables_information()
  {
    // Tạo area với tables
    $area = Area::factory()->create(['branch_id' => $this->branch->id]);
    TableAndRoom::factory()->count(3)->create(['area_id' => $area->id]);

    $response = $this->withHeaders($this->getHeaders())
      ->getJson("/api/admin/areas/{$area->id}");

    $response->assertStatus(200);
    $response->assertJsonStructure([
      'data' => [
        'id',
        'name',
        'tables_count',
        'tables_and_rooms' => [
          '*' => [
            'id',
            'name',
            'capacity',
            'status',
          ]
        ]
      ]
    ]);

    $this->assertEquals(3, $response->json('data.tables_count'));
    $this->assertCount(3, $response->json('data.tables_and_rooms'));
  }
}
