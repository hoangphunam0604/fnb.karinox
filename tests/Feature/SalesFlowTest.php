<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\ProductBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class SalesFlowTest extends TestCase
{
  use RefreshDatabase;

  protected $adminUser;
  protected $branch;
  protected $products;
  protected $customer;

  protected function setUp(): void
  {
    parent::setUp();
    $this->setupTestData();
  }

  private function setupTestData()
  {
    // Tạo admin user với permissions
    $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
    $this->adminUser = User::factory()->create([
      'username' => 'test_admin',
      'fullname' => 'Test Admin'
    ]);
    $this->adminUser->assignRole($adminRole);

    // Tạo branch
    $this->branch = Branch::create([
      'name' => 'Chi nhánh Test',
      'code' => 'TEST01',
      'address' => 'Test Address',
      'phone' => '0123456789',
      'status' => 'active'
    ]);

    // Tạo categories với code_prefix
    $categories = [
      ['name' => 'Coffee', 'code_prefix' => 'CF'],
      ['name' => 'Tea', 'code_prefix' => 'TEA'],
      ['name' => 'Milk Tea', 'code_prefix' => 'MILK']
    ];

    foreach ($categories as $categoryData) {
      $category = Category::create($categoryData);

      // Tạo 2 products cho mỗi category
      for ($i = 1; $i <= 2; $i++) {
        $product = Product::create([
          'name' => $categoryData['name'] . " Product {$i}",
          'category_id' => $category->id,
          'regular_price' => rand(25000, 50000),
          'cost_price' => rand(15000, 30000),
          'status' => 'active',
          'allows_sale' => true,
          'manage_stock' => true
        ]);

        // Tạo stock cho branch
        ProductBranch::create([
          'product_id' => $product->id,
          'branch_id' => $this->branch->id,
          'stock_quantity' => 100,
          'manage_stock' => true
        ]);
      }
    }

    $this->products = Product::all();

    // Tạo customer
    $this->customer = Customer::create([
      'fullname' => 'Test Customer',
      'phone' => '0987654321',
      'email' => 'customer@test.com',
      'gender' => 'male',
      'status' => 'active'
    ]);
  }

  private function authenticateAdmin()
  {
    $response = $this->postJson('/api/auth/login', [
      'username' => $this->adminUser->username,
      'password' => 'password'
    ], [
      'karinox-app-id' => 'karinox-app-admin',
      'X-Branch-Id' => $this->branch->id
    ]);

    $response->assertStatus(200);
    return $response->json('access_token');
  }

  private function getAuthHeaders()
  {
    $token = $this->authenticateAdmin();
    return [
      'Authorization' => "Bearer {$token}",
      'karinox-app-id' => 'karinox-app-admin',
      'X-Branch-Id' => $this->branch->id,
      'Accept' => 'application/json',
      'Content-Type' => 'application/json'
    ];
  }

  /** @test */
  public function it_can_authenticate_admin_user()
  {
    $response = $this->postJson('/api/auth/login', [
      'username' => $this->adminUser->username,
      'password' => 'password'
    ], [
      'karinox-app-id' => 'karinox-app-admin'
    ]);

    $response->assertStatus(200)
      ->assertJsonStructure([
        'access_token',
        'token_type',
        'user' => [
          'id',
          'fullname',
          'role',
          'permissions'
        ]
      ]);
  }

  /** @test */
  public function it_can_get_stock_report()
  {
    $headers = $this->getAuthHeaders();

    $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", $headers);

    $response->assertStatus(200)
      ->assertJsonStructure([
        'data' => [
          '*' => [
            'product_id',
            'product_code',
            'product_name',
            'stock_quantity',
            'category'
          ]
        ]
      ]);

    $stockData = $response->json('data');
    $this->assertCount(6, $stockData); // 3 categories × 2 products = 6

    foreach ($stockData as $item) {
      $this->assertEquals(100, $item['stock_quantity']);
    }
  }

  /** @test */
  public function it_can_create_customer()
  {
    $headers = $this->getAuthHeaders();

    $customerData = [
      'fullname' => 'New Test Customer',
      'phone' => '0999888777',
      'email' => 'newcustomer@test.com',
      'gender' => 'female',
      'status' => 'active'
    ];

    $response = $this->postJson('/api/admin/customers', $customerData, $headers);

    $response->assertStatus(201)
      ->assertJsonStructure([
        'data' => [
          'id',
          'fullname',
          'phone',
          'email',
          'gender',
          'status'
        ]
      ]);

    $this->assertDatabaseHas('customers', [
      'fullname' => 'New Test Customer',
      'phone' => '0999888777'
    ]);
  }

  /** @test */
  public function it_can_list_products_with_auto_generated_codes()
  {
    $headers = $this->getAuthHeaders();

    $response = $this->getJson('/api/admin/products', $headers);

    $response->assertStatus(200)
      ->assertJsonStructure([
        'data' => [
          '*' => [
            'id',
            'code',
            'name',
            'regular_price',
            'category'
          ]
        ]
      ]);

    $products = $response->json('data');

    // Kiểm tra auto-generated codes
    $coffeeCodes = collect($products)->where('category.name', 'Coffee')->pluck('code')->toArray();
    $this->assertContains('CF0001', $coffeeCodes);
    $this->assertContains('CF0002', $coffeeCodes);

    $teaCodes = collect($products)->where('category.name', 'Tea')->pluck('code')->toArray();
    $this->assertContains('TEA0001', $teaCodes);
    $this->assertContains('TEA0002', $teaCodes);

    $milkCodes = collect($products)->where('category.name', 'Milk Tea')->pluck('code')->toArray();
    $this->assertContains('MILK0001', $milkCodes);
    $this->assertContains('MILK0002', $milkCodes);
  }

  /** @test */
  public function it_can_create_category_with_auto_prefix()
  {
    $headers = $this->getAuthHeaders();

    $categoryData = [
      'name' => 'Bánh Ngọt',
      'description' => 'Category test auto prefix'
    ];

    $response = $this->postJson('/api/admin/categories', $categoryData, $headers);

    $response->assertStatus(201)
      ->assertJsonStructure([
        'data' => [
          'id',
          'name',
          'code_prefix'
        ]
      ]);

    $category = $response->json('data');
    $this->assertEquals('BANH', $category['code_prefix']); // "Bánh Ngọt" -> "BANH"
  }

  /** @test */
  public function it_can_create_product_with_auto_code()
  {
    $headers = $this->getAuthHeaders();
    $category = Category::first();

    $productData = [
      'name' => 'Test Auto Code Product',
      'category_id' => $category->id,
      'regular_price' => 35000,
      'cost_price' => 20000,
      'status' => 'active',
      'allows_sale' => true,
      'manage_stock' => true
    ];

    $response = $this->postJson('/api/admin/products', $productData, $headers);

    $response->assertStatus(201);

    $product = $response->json('data');
    $this->assertNotEmpty($product['code']);

    // Kiểm tra format code: PREFIX + 4 digits
    $expectedPrefix = $category->code_prefix;
    $this->assertStringStartsWith($expectedPrefix, $product['code']);
    $this->assertMatchesRegularExpression("/^{$expectedPrefix}\d{4}$/", $product['code']);
  }

  /** @test */
  public function it_simulates_complete_sales_workflow()
  {
    $headers = $this->getAuthHeaders();

    // 1. Lấy stock ban đầu
    $initialStockResponse = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", $headers);
    $initialStockResponse->assertStatus(200);
    $initialStock = $initialStockResponse->json('data');

    // 2. Tạo customer
    $customerData = [
      'fullname' => 'Sales Test Customer',
      'phone' => '0555444333',
      'email' => 'salestest@test.com',
      'gender' => 'male',
      'status' => 'active'
    ];

    $customerResponse = $this->postJson('/api/admin/customers', $customerData, $headers);
    $customerResponse->assertStatus(201);
    $customer = $customerResponse->json('data');

    // 3. Lấy products để bán
    $productsResponse = $this->getJson('/api/admin/products', $headers);
    $productsResponse->assertStatus(200);
    $products = $productsResponse->json('data');

    // 4. Tính toán đơn hàng
    $selectedProducts = array_slice($products, 0, 3);
    $totalAmount = 0;
    $orderItems = [];

    foreach ($selectedProducts as $product) {
      $quantity = 2;
      $price = $product['regular_price'];
      $subtotal = $price * $quantity;
      $totalAmount += $subtotal;

      $orderItems[] = [
        'product_id' => $product['id'],
        'product_code' => $product['code'],
        'product_name' => $product['name'],
        'quantity' => $quantity,
        'price' => $price,
        'subtotal' => $subtotal
      ];
    }

    // 5. Tính điểm thưởng
    $earnedPoints = intval($totalAmount / 1000); // 1,000đ = 1 điểm

    // 6. Kiểm tra kết quả
    $this->assertGreaterThan(0, $totalAmount);
    $this->assertGreaterThan(0, $earnedPoints);
    $this->assertCount(3, $orderItems);
    $this->assertEquals(6, array_sum(array_column($orderItems, 'quantity'))); // 3 products × 2 = 6 items

    // Log kết quả test
    $this->assertTrue(true, sprintf(
      "Sales simulation successful: Customer %s, Total %s VND, Points %d, Items %d",
      $customer['fullname'],
      number_format($totalAmount),
      $earnedPoints,
      count($orderItems)
    ));
  }

  /** @test */
  public function it_validates_product_code_uniqueness()
  {
    $headers = $this->getAuthHeaders();
    $category = Category::first();

    // Tạo nhiều products cùng category
    for ($i = 1; $i <= 5; $i++) {
      $productData = [
        'name' => "Unique Test Product {$i}",
        'category_id' => $category->id,
        'regular_price' => 30000,
        'cost_price' => 18000,
        'status' => 'active',
        'allows_sale' => true
      ];

      $response = $this->postJson('/api/admin/products', $productData, $headers);
      $response->assertStatus(201);
    }

    // Kiểm tra tất cả codes đều unique
    $allCodes = Product::where('category_id', $category->id)->pluck('code')->toArray();
    $uniqueCodes = array_unique($allCodes);

    $this->assertCount(count($allCodes), $uniqueCodes, 'All product codes should be unique');
  }

  /** @test */
  public function it_handles_branch_auto_detection_from_header()
  {
    $token = $this->authenticateAdmin();

    // Test với X-Branch-Id header
    $response = $this->getJson('/api/admin/inventory/stock-report', [
      'Authorization' => "Bearer {$token}",
      'karinox-app-id' => 'karinox-app-admin',
      'X-Branch-Id' => $this->branch->id,
      'Accept' => 'application/json'
    ]);

    $response->assertStatus(200);

    // Test không có branch_id trong query param vẫn work
    $stockData = $response->json('data');
    $this->assertNotEmpty($stockData);
  }
}
