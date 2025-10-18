<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\ProductBranch;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ProductStockCardTest extends TestCase
{
  use RefreshDatabase;

  protected $adminUser;
  protected $branch;
  protected $product;

  protected function setUp(): void
  {
    parent::setUp();
    $this->setupTestData();
  }

  private function setupTestData()
  {
    // Setup admin user
    $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
    $this->adminUser = User::factory()->create([
      'username' => 'stock_admin',
      'fullname' => 'Stock Admin'
    ]);
    $this->adminUser->assignRole($adminRole);

    // Setup branch
    $this->branch = Branch::create([
      'name' => 'Stock Card Test Branch',
      'code' => 'STOCK01',
      'address' => 'Test Stock Address',
      'phone' => '0123456789',
      'status' => 'active'
    ]);

    // Setup category and product
    $category = Category::create([
      'name' => 'Stock Card Category',
      'code_prefix' => 'STOCK'
    ]);

    $this->product = Product::create([
      'name' => 'Stock Card Test Product',
      'category_id' => $category->id,
      'regular_price' => 50000,
      'cost_price' => 30000,
      'status' => 'active',
      'manage_stock' => true
    ]);

    // Setup initial stock
    ProductBranch::create([
      'product_id' => $this->product->id,
      'branch_id' => $this->branch->id,
      'stock_quantity' => 100,
      'manage_stock' => true
    ]);
  }

  private function getAuthHeaders()
  {
    $response = $this->postJson('/api/auth/login', [
      'username' => $this->adminUser->username,
      'password' => 'password'
    ], [
      'karinox-app-id' => 'karinox-app-admin'
    ]);

    $token = $response->json('access_token');

    return [
      'Authorization' => "Bearer {$token}",
      'karinox-app-id' => 'karinox-app-admin',
      'X-Branch-Id' => $this->branch->id,
      'Accept' => 'application/json',
      'Content-Type' => 'application/json'
    ];
  }

  private function createInventoryTransaction($type, $quantity, $note = null)
  {
    $transaction = InventoryTransaction::create([
      'type' => $type,
      'reference_number' => 'TEST-' . strtoupper($type) . '-' . time(),
      'branch_id' => $this->branch->id,
      'user_id' => $this->adminUser->id,
      'note' => $note ?? "Test {$type} transaction",
      'status' => 'completed'
    ]);

    InventoryTransactionItem::create([
      'inventory_transaction_id' => $transaction->id,
      'product_id' => $this->product->id,
      'quantity' => $quantity,
      'unit_cost' => $this->product->cost_price,
      'quantity_before' => 100,
      'quantity_after' => 100 + ($type === 'import' ? $quantity : -$quantity)
    ]);

    return $transaction;
  }

  /** @test */
  public function it_can_get_product_stock_card()
  {
    $headers = $this->getAuthHeaders();

    // Tạo một vài giao dịch test
    $this->createInventoryTransaction('import', 50, 'Test import');
    $this->createInventoryTransaction('export', 20, 'Test export');
    $this->createInventoryTransaction('sale', 10, 'Test sale');

    $response = $this->getJson("/api/admin/inventory/product-card/{$this->product->id}?branch_id={$this->branch->id}", $headers);

    $response->assertStatus(200)
      ->assertJsonStructure([
        'data' => [
          '*' => [
            'transaction_id',
            'date',
            'type',
            'type_label',
            'reference_number',
            'quantity_before',
            'quantity_change',
            'quantity_after',
            'unit_cost',
            'total_cost',
            'note',
            'branch' => [
              'id',
              'name',
              'code'
            ],
            'user' => [
              'id',
              'fullname'
            ]
          ]
        ]
      ]);

    $data = $response->json('data');
    $this->assertCount(3, $data);

    // Kiểm tra type labels
    $types = array_column($data, 'type');
    $typeLabels = array_column($data, 'type_label');

    $this->assertContains('import', $types);
    $this->assertContains('export', $types);
    $this->assertContains('sale', $types);

    $this->assertContains('Nhập kho', $typeLabels);
    $this->assertContains('Xuất kho', $typeLabels);
    $this->assertContains('Bán hàng', $typeLabels);
  }

  /** @test */
  public function it_can_filter_stock_card_by_date()
  {
    $headers = $this->getAuthHeaders();

    // Tạo giao dịch hôm qua
    $yesterday = now()->subDay();
    $transaction1 = $this->createInventoryTransaction('import', 30);
    $transaction1->update(['created_at' => $yesterday]);

    // Tạo giao dịch hôm nay
    $this->createInventoryTransaction('export', 15);

    // Filter chỉ lấy giao dịch hôm nay
    $today = now()->format('Y-m-d');
    $response = $this->getJson("/api/admin/inventory/product-card/{$this->product->id}?branch_id={$this->branch->id}&from_date={$today}&to_date={$today}", $headers);

    $response->assertStatus(200);

    $data = $response->json('data');
    $this->assertCount(1, $data);
    $this->assertEquals('export', $data[0]['type']);
  }

  /** @test */
  public function it_can_filter_stock_card_by_type()
  {
    $headers = $this->getAuthHeaders();

    // Tạo nhiều loại giao dịch
    $this->createInventoryTransaction('import', 40);
    $this->createInventoryTransaction('export', 25);
    $this->createInventoryTransaction('sale', 15);
    $this->createInventoryTransaction('import', 30);

    // Filter chỉ lấy giao dịch import
    $response = $this->getJson("/api/admin/inventory/product-card/{$this->product->id}?branch_id={$this->branch->id}&type=import", $headers);

    $response->assertStatus(200);

    $data = $response->json('data');
    $this->assertCount(2, $data);

    foreach ($data as $item) {
      $this->assertEquals('import', $item['type']);
      $this->assertEquals('Nhập kho', $item['type_label']);
    }
  }

  /** @test */
  public function it_can_get_product_stock_summary()
  {
    $headers = $this->getAuthHeaders();

    // Tạo các giao dịch để test statistics
    $this->createInventoryTransaction('import', 50);
    $this->createInventoryTransaction('import', 30);
    $this->createInventoryTransaction('export', 20);
    $this->createInventoryTransaction('sale', 15);

    $response = $this->getJson("/api/admin/inventory/product-summary/{$this->product->id}?branch_id={$this->branch->id}", $headers);

    $response->assertStatus(200)
      ->assertJsonStructure([
        'data' => [
          'product' => [
            'id',
            'code',
            'name',
            'unit',
            'cost_price',
            'regular_price',
            'category' => [
              'id',
              'name',
              'code_prefix'
            ]
          ],
          'current_stock' => [
            'quantity',
            'value',
            'last_updated'
          ],
          'statistics' => [
            'total_imported',
            'total_exported',
            'total_sold',
            'total_adjusted',
            'transactions_count'
          ],
          'period_summary' => [
            'period',
            'opening_stock',
            'closing_stock',
            'net_change'
          ]
        ]
      ]);

    $data = $response->json('data');

    // Kiểm tra product info
    $this->assertEquals($this->product->id, $data['product']['id']);
    $this->assertEquals($this->product->name, $data['product']['name']);

    // Kiểm tra current stock
    $this->assertEquals(100, $data['current_stock']['quantity']);

    // Kiểm tra statistics
    $this->assertEquals(80, $data['statistics']['total_imported']); // 50 + 30
    $this->assertEquals(35, $data['statistics']['total_exported']); // 20 + 15
    $this->assertEquals(15, $data['statistics']['total_sold']); // sale only
    $this->assertEquals(4, $data['statistics']['transactions_count']);
  }

  /** @test */
  public function it_validates_product_existence()
  {
    $headers = $this->getAuthHeaders();

    $response = $this->getJson("/api/admin/inventory/product-card/999999?branch_id={$this->branch->id}", $headers);

    $response->assertStatus(404)
      ->assertJson(['error' => 'Sản phẩm không tồn tại']);
  }

  /** @test */
  public function it_requires_branch_id()
  {
    $headers = $this->getAuthHeaders();
    unset($headers['X-Branch-Id']); // Remove branch header

    $response = $this->getJson("/api/admin/inventory/product-card/{$this->product->id}", $headers);

    $response->assertStatus(400)
      ->assertJson(['error' => 'Vui lòng chọn chi nhánh']);
  }

  /** @test */
  public function it_validates_date_range()
  {
    $headers = $this->getAuthHeaders();

    // Test invalid date range (to_date before from_date)
    $response = $this->getJson("/api/admin/inventory/product-card/{$this->product->id}?branch_id={$this->branch->id}&from_date=2024-12-31&to_date=2024-01-01", $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['to_date']);
  }

  /** @test */
  public function it_validates_transaction_type_filter()
  {
    $headers = $this->getAuthHeaders();

    // Test invalid type
    $response = $this->getJson("/api/admin/inventory/product-card/{$this->product->id}?branch_id={$this->branch->id}&type=invalid_type", $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['type']);
  }

  /** @test */
  public function it_handles_pagination()
  {
    $headers = $this->getAuthHeaders();

    // Tạo nhiều giao dịch
    for ($i = 1; $i <= 25; $i++) {
      $this->createInventoryTransaction('import', $i);
    }

    // Test pagination với per_page = 10
    $response = $this->getJson("/api/admin/inventory/product-card/{$this->product->id}?branch_id={$this->branch->id}&per_page=10", $headers);

    $response->assertStatus(200)
      ->assertJsonStructure([
        'data',
        'links',
        'meta' => [
          'current_page',
          'per_page',
          'total'
        ]
      ]);

    $meta = $response->json('meta');
    $this->assertEquals(10, $meta['per_page']);
    $this->assertEquals(25, $meta['total']);
    $this->assertCount(10, $response->json('data'));
  }
}
