<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\ProductBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup admin user
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->adminUser = User::factory()->create([
            'username' => 'inventory_admin',
            'fullname' => 'Inventory Admin'
        ]);
        $this->adminUser->assignRole($adminRole);

        // Setup branch
        $this->branch = Branch::create([
            'name' => 'Inventory Test Branch',
            'code' => 'INV01',
            'address' => 'Test Inventory Address',
            'phone' => '0123456789',
            'status' => 'active'
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

    private function createTestProducts($count = 5)
    {
        $category = Category::create([
            'name' => 'Inventory Test Category',
            'code_prefix' => 'INV'
        ]);

        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $product = Product::create([
                'name' => "Inventory Test Product {$i}",
                'category_id' => $category->id,
                'regular_price' => 25000 + ($i * 5000),
                'cost_price' => 15000 + ($i * 3000),
                'status' => 'active',
                'allows_sale' => true,
                'manage_stock' => true
            ]);

            // Create stock với số lượng khác nhau
            ProductBranch::create([
                'product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'stock_quantity' => $i * 10, // 10, 20, 30, 40, 50
                'manage_stock' => true
            ]);

            $products[] = $product;
        }

        return $products;
    }

    /** @test */
    public function it_can_get_stock_report_for_branch()
    {
        $products = $this->createTestProducts(3);
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
                            'category' => [
                                'id',
                                'name',
                                'code_prefix'
                            ]
                        ]
                    ]
                ]);

        $stockData = $response->json('data');
        $this->assertCount(3, $stockData);

        // Kiểm tra stock quantities
        $stockQuantities = array_column($stockData, 'stock_quantity');
        sort($stockQuantities);
        $this->assertEquals([10, 20, 30], $stockQuantities);
    }

    /** @test */
    public function it_auto_detects_branch_from_header()
    {
        $products = $this->createTestProducts(2);
        $headers = $this->getAuthHeaders();

        // Test không cần branch_id trong query param
        $response = $this->getJson("/api/admin/inventory/stock-report", $headers);

        $response->assertStatus(200);
        
        $stockData = $response->json('data');
        $this->assertCount(2, $stockData);
    }

    /** @test */
    public function it_filters_only_managed_stock_products()
    {
        $category = Category::create([
            'name' => 'Mixed Stock Category',
            'code_prefix' => 'MIX'
        ]);

        // Tạo products - một có manage_stock, một không
        $managedProduct = Product::create([
            'name' => 'Managed Stock Product',
            'category_id' => $category->id,
            'regular_price' => 30000,
            'cost_price' => 18000,
            'status' => 'active',
            'manage_stock' => true
        ]);

        $unmanagedProduct = Product::create([
            'name' => 'Unmanaged Stock Product',
            'category_id' => $category->id,
            'regular_price' => 25000,
            'cost_price' => 15000,
            'status' => 'active',
            'manage_stock' => false
        ]);

        // Tạo ProductBranch records
        ProductBranch::create([
            'product_id' => $managedProduct->id,
            'branch_id' => $this->branch->id,
            'stock_quantity' => 100,
            'manage_stock' => true
        ]);

        ProductBranch::create([
            'product_id' => $unmanagedProduct->id,
            'branch_id' => $this->branch->id,
            'stock_quantity' => 200,
            'manage_stock' => false
        ]);

        $headers = $this->getAuthHeaders();
        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", $headers);

        $response->assertStatus(200);
        
        $stockData = $response->json('data');
        
        // Chỉ nên có 1 product (managed stock)
        $this->assertCount(1, $stockData);
        $this->assertEquals('Managed Stock Product', $stockData[0]['product_name']);
        $this->assertEquals(100, $stockData[0]['stock_quantity']);
    }

    /** @test */
    public function it_returns_empty_for_branch_without_stock()
    {
        // Tạo branch khác không có stock
        $emptyBranch = Branch::create([
            'name' => 'Empty Branch',
            'code' => 'EMPTY01',
            'address' => 'Empty Address',
            'phone' => '0999999999',
            'status' => 'active'
        ]);

        $this->createTestProducts(3); // Tạo stock cho branch chính
        
        $headers = $this->getAuthHeaders();
        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$emptyBranch->id}", $headers);

        $response->assertStatus(200);
        
        $stockData = $response->json('data');
        $this->assertCount(0, $stockData);
    }

    /** @test */
    public function it_includes_product_and_category_information()
    {
        $products = $this->createTestProducts(1);
        $headers = $this->getAuthHeaders();

        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", $headers);

        $response->assertStatus(200);
        
        $stockData = $response->json('data');
        $item = $stockData[0];

        // Kiểm tra structure
        $this->assertArrayHasKey('product_id', $item);
        $this->assertArrayHasKey('product_code', $item);
        $this->assertArrayHasKey('product_name', $item);
        $this->assertArrayHasKey('stock_quantity', $item);
        $this->assertArrayHasKey('category', $item);

        // Kiểm tra category structure
        $this->assertArrayHasKey('id', $item['category']);
        $this->assertArrayHasKey('name', $item['category']);
        $this->assertArrayHasKey('code_prefix', $item['category']);

        // Kiểm tra values
        $product = $products[0];
        $this->assertEquals($product->id, $item['product_id']);
        $this->assertEquals($product->code, $item['product_code']);
        $this->assertEquals($product->name, $item['product_name']);
        $this->assertEquals(10, $item['stock_quantity']); // First product has 10 stock
        $this->assertEquals('Inventory Test Category', $item['category']['name']);
        $this->assertEquals('INV', $item['category']['code_prefix']);
    }

    /** @test */
    public function it_sorts_stock_report_by_product_code()
    {
        // Tạo products với codes khác nhau
        $category = Category::create([
            'name' => 'Sort Test Category', 
            'code_prefix' => 'SORT'
        ]);

        $productNames = ['Z Product', 'A Product', 'M Product'];
        $expectedCodes = ['SORT0001', 'SORT0002', 'SORT0003'];

        foreach ($productNames as $index => $name) {
            $product = Product::create([
                'name' => $name,
                'category_id' => $category->id,
                'regular_price' => 30000,
                'status' => 'active',
                'manage_stock' => true
            ]);

            ProductBranch::create([
                'product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'stock_quantity' => ($index + 1) * 15,
                'manage_stock' => true
            ]);
        }

        $headers = $this->getAuthHeaders();
        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", $headers);

        $response->assertStatus(200);
        
        $stockData = $response->json('data');
        $this->assertCount(3, $stockData);

        // Kiểm tra order theo product_code
        $actualCodes = array_column($stockData, 'product_code');
        $this->assertEquals($expectedCodes, $actualCodes);
    }

    /** @test */
    public function it_handles_products_with_zero_stock()
    {
        $products = $this->createTestProducts(1);
        $product = $products[0];

        // Set stock về 0
        ProductBranch::where('product_id', $product->id)
                   ->where('branch_id', $this->branch->id)
                   ->update(['stock_quantity' => 0]);

        $headers = $this->getAuthHeaders();
        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", $headers);

        $response->assertStatus(200);
        
        $stockData = $response->json('data');
        $this->assertCount(1, $stockData);
        $this->assertEquals(0, $stockData[0]['stock_quantity']);
    }

    /** @test */
    public function it_requires_authentication_for_stock_report()
    {
        $this->createTestProducts(1);

        // Test không có token
        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_proper_headers_for_authentication()
    {
        $this->createTestProducts(1);

        $response = $this->postJson('/api/auth/login', [
            'username' => $this->adminUser->username,
            'password' => 'password'
        ], [
            'karinox-app-id' => 'karinox-app-admin'
        ]);

        $token = $response->json('access_token');

        // Test thiếu karinox-app-id header
        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(401);

        // Test với đầy đủ headers
        $response = $this->getJson("/api/admin/inventory/stock-report?branch_id={$this->branch->id}", [
            'Authorization' => "Bearer {$token}",
            'karinox-app-id' => 'karinox-app-admin',
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200);
    }
}