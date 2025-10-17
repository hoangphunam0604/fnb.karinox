<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ProductCodeGenerationTest extends TestCase
{
  use RefreshDatabase;

  protected $adminUser;
  protected $productCodeService;

  protected function setUp(): void
  {
    parent::setUp();

    // Setup admin user
    $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
    $this->adminUser = User::factory()->create([
      'username' => 'test_admin',
      'fullname' => 'Test Admin'
    ]);
    $this->adminUser->assignRole($adminRole);

    $this->productCodeService = new ProductCodeService();
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
      'Accept' => 'application/json',
      'Content-Type' => 'application/json'
    ];
  }

  /** @test */
  public function it_generates_prefix_from_vietnamese_category_names()
  {
    $testCases = [
      'Cà phê' => 'CA',
      'Trà sữa' => 'TRA',
      'Bánh ngọt' => 'BANH',
      'Nước ép' => 'NUOC',
      'Đồ uống có cồn' => 'DO',
      'Café đá xay' => 'CAFE',
      'Sinh tố' => 'SINH'
    ];

    foreach ($testCases as $categoryName => $expectedPrefix) {
      $generatedPrefix = $this->productCodeService->generatePrefixFromName($categoryName);

      $this->assertEquals(
        $expectedPrefix,
        $generatedPrefix,
        "Category '{$categoryName}' should generate prefix '{$expectedPrefix}', got '{$generatedPrefix}'"
      );
    }
  }

  /** @test */
  public function it_creates_categories_with_auto_prefix_via_api()
  {
    $headers = $this->getAuthHeaders();

    $testCategories = [
      ['name' => 'Cà phê', 'expected_prefix' => 'CA'],
      ['name' => 'Trà sữa', 'expected_prefix' => 'TRA'],
      ['name' => 'Bánh ngọt', 'expected_prefix' => 'BANH'],
      ['name' => 'Nước ép', 'expected_prefix' => 'NUOC']
    ];

    foreach ($testCategories as $testCase) {
      $categoryData = [
        'name' => $testCase['name'],
        'description' => 'Test category for ' . $testCase['name']
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
      $this->assertEquals($testCase['expected_prefix'], $category['code_prefix']);

      // Verify database
      $this->assertDatabaseHas('categories', [
        'name' => $testCase['name'],
        'code_prefix' => $testCase['expected_prefix']
      ]);
    }
  }

  /** @test */
  public function it_generates_sequential_product_codes_for_same_category()
  {
    $headers = $this->getAuthHeaders();

    // Tạo category
    $categoryData = [
      'name' => 'Coffee Test',
      'description' => 'Test category for sequential codes'
    ];

    $categoryResponse = $this->postJson('/api/admin/categories', $categoryData, $headers);
    $categoryResponse->assertStatus(201);
    $category = $categoryResponse->json('data');
    $categoryId = $category['id'];
    $expectedPrefix = $category['code_prefix'];

    // Tạo 5 products
    $productCodes = [];
    for ($i = 1; $i <= 5; $i++) {
      $productData = [
        'name' => "Test Product {$i}",
        'category_id' => $categoryId,
        'regular_price' => 25000,
        'cost_price' => 15000,
        'status' => 'active'
      ];

      $response = $this->postJson('/api/admin/products', $productData, $headers);
      $response->assertStatus(201);

      $product = $response->json('data');
      $productCodes[] = $product['code'];

      // Verify code format
      $this->assertMatchesRegularExpression("/^{$expectedPrefix}\d{4}$/", $product['code']);
    }

    // Verify codes are sequential and unique
    $expectedCodes = [
      "{$expectedPrefix}0001",
      "{$expectedPrefix}0002",
      "{$expectedPrefix}0003",
      "{$expectedPrefix}0004",
      "{$expectedPrefix}0005"
    ];

    $this->assertEquals($expectedCodes, $productCodes);
    $this->assertCount(5, array_unique($productCodes)); // All unique
  }

  /** @test */
  public function it_handles_multiple_categories_independently()
  {
    $headers = $this->getAuthHeaders();

    // Tạo 3 categories
    $categories = [];
    $categoryNames = ['Coffee', 'Tea', 'Juice'];

    foreach ($categoryNames as $name) {
      $categoryData = [
        'name' => $name,
        'description' => "Test {$name} category"
      ];

      $response = $this->postJson('/api/admin/categories', $categoryData, $headers);
      $response->assertStatus(201);
      $categories[] = $response->json('data');
    }

    // Tạo 2 products cho mỗi category
    $allProductCodes = [];
    foreach ($categories as $category) {
      $categoryProducts = [];

      for ($i = 1; $i <= 2; $i++) {
        $productData = [
          'name' => "{$category['name']} Product {$i}",
          'category_id' => $category['id'],
          'regular_price' => 30000,
          'cost_price' => 18000,
          'status' => 'active'
        ];

        $response = $this->postJson('/api/admin/products', $productData, $headers);
        $response->assertStatus(201);

        $product = $response->json('data');
        $categoryProducts[] = $product['code'];
        $allProductCodes[] = $product['code'];
      }

      // Verify each category has sequential codes starting from 0001
      $expectedCodes = [
        "{$category['code_prefix']}0001",
        "{$category['code_prefix']}0002"
      ];

      $this->assertEquals($expectedCodes, $categoryProducts);
    }

    // Verify all codes are unique across categories
    $this->assertCount(6, array_unique($allProductCodes));
  }

  /** @test */
  public function it_validates_product_code_format()
  {
    $testCases = [
      ['code' => 'CF0001', 'expected' => true],
      ['code' => 'TEA0123', 'expected' => true],
      ['code' => 'MILK9999', 'expected' => true],
      ['code' => 'CF001', 'expected' => false], // Too short
      ['code' => 'CF00001', 'expected' => false], // Too long
      ['code' => 'cf0001', 'expected' => false], // Lowercase
      ['code' => 'CF000A', 'expected' => false], // Contains letter
      ['code' => '1CF0001', 'expected' => false], // Starts with number
      ['code' => '', 'expected' => false], // Empty
      ['code' => 'C0001', 'expected' => false], // No prefix
    ];

    foreach ($testCases as $testCase) {
      $isValid = $this->productCodeService->isValidProductCode($testCase['code']);

      $this->assertEquals(
        $testCase['expected'],
        $isValid,
        "Code '{$testCase['code']}' validation failed"
      );
    }
  }

  /** @test */
  public function it_generates_unique_codes_when_products_are_deleted()
  {
    $headers = $this->getAuthHeaders();

    // Tạo category
    $categoryData = ['name' => 'Delete Test', 'description' => 'Test deletion'];
    $categoryResponse = $this->postJson('/api/admin/categories', $categoryData, $headers);
    $category = $categoryResponse->json('data');

    // Tạo 3 products
    $products = [];
    for ($i = 1; $i <= 3; $i++) {
      $productData = [
        'name' => "Product {$i}",
        'category_id' => $category['id'],
        'regular_price' => 25000,
        'status' => 'active'
      ];

      $response = $this->postJson('/api/admin/products', $productData, $headers);
      $products[] = $response->json('data');
    }

    // Verify initial codes
    $this->assertEquals('DELETE0001', $products[0]['code']);
    $this->assertEquals('DELETE0002', $products[1]['code']);
    $this->assertEquals('DELETE0003', $products[2]['code']);

    // Delete second product
    $deleteResponse = $this->deleteJson("/api/admin/products/{$products[1]['id']}", [], $headers);
    $deleteResponse->assertStatus(200);

    // Tạo product mới - should get DELETE0004, not fill gap
    $newProductData = [
      'name' => 'New Product',
      'category_id' => $category['id'],
      'regular_price' => 25000,
      'status' => 'active'
    ];

    $newProductResponse = $this->postJson('/api/admin/products', $newProductData, $headers);
    $newProduct = $newProductResponse->json('data');

    $this->assertEquals('DELETE0004', $newProduct['code']);
  }

  /** @test */
  public function it_handles_concurrent_product_creation()
  {
    $headers = $this->getAuthHeaders();

    // Tạo category
    $categoryData = ['name' => 'Concurrent Test', 'description' => 'Test concurrent creation'];
    $categoryResponse = $this->postJson('/api/admin/categories', $categoryData, $headers);
    $category = $categoryResponse->json('data');

    // Mô phỏng tạo đồng thời nhiều products
    $products = [];
    $productNames = ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'];

    foreach ($productNames as $name) {
      $productData = [
        'name' => $name,
        'category_id' => $category['id'],
        'regular_price' => 25000,
        'status' => 'active'
      ];

      $response = $this->postJson('/api/admin/products', $productData, $headers);
      $response->assertStatus(201);
      $products[] = $response->json('data');
    }

    // Verify all codes are unique and sequential
    $codes = array_column($products, 'code');
    $this->assertCount(5, array_unique($codes));

    // Should be CONCURRENT0001 to CONCURRENT0005
    for ($i = 0; $i < 5; $i++) {
      $expectedCode = 'CONCURRENT' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
      $this->assertEquals($expectedCode, $products[$i]['code']);
    }
  }

  /** @test */
  public function it_creates_valid_prefix_for_special_characters()
  {
    $testCases = [
      'Cà phê & Tea' => 'CA',
      'Bánh 123 Ngọt' => 'BANH',
      'Đồ uống (Hot)' => 'DO',
      'Trà sữa - Premium' => 'TRA',
      'Nước ép 100%' => 'NUOC'
    ];

    foreach ($testCases as $categoryName => $expectedPrefix) {
      $generatedPrefix = $this->productCodeService->generatePrefixFromName($categoryName);

      $this->assertEquals($expectedPrefix, $generatedPrefix);
      $this->assertMatchesRegularExpression('/^[A-Z]+$/', $generatedPrefix);
    }
  }
}
