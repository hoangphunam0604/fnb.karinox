<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductBranch;
use Illuminate\Support\Facades\Auth;

class RealPOSProductAPITest extends TestCase
{
  use RefreshDatabase;

  public function test_pos_products_api_returns_real_data()
  {
    // Tạo user test
    $user = User::factory()->create([
      'username' => 'testuser',
      'fullname' => 'POS Test User',
      'is_active' => true
    ]);

    // Tạo branch test
    $branch = Branch::create([
      'name' => 'Chi nhánh test',
      'code' => 'BR001',
      'address' => 'Test address',
      'phone' => '0123456789',
      'status' => 'active'
    ]);

    // Tạo category
    $category = Category::create([
      'name' => 'Đồ uống test',
      'code_prefix' => 'CF'
    ]);

    // Tạo product
    $product = Product::create([
      'code' => 'CF001TEST',
      'name' => 'Cà phê test',
      'category_id' => $category->id,
      'regular_price' => 30000,
      'sale_price' => 25000,
      'cost_price' => 15000,
      'allows_sale' => true,
      'product_type' => 'processed',
      'status' => 'active'
    ]);

    // Tạo ProductBranch
    ProductBranch::create([
      'product_id' => $product->id,
      'branch_id' => $branch->id,
      'is_selling' => true,
      'stock_quantity' => 100
    ]);

    // Authenticate user
    $this->actingAs($user, 'api');

    // Call API
    $response = $this->withHeaders([
      'karinox-app-id' => 'karinox-app-pos',
      'Karinox-Branch-Id' => $branch->id,
      'Accept' => 'application/json',
    ])->getJson('/api/pos/products');

    // Debug response
    dump('Status:', $response->status());
    dump('Response:', $response->json());

    $response->assertStatus(200);
    $response->assertJsonStructure([
      'success',
      'data' => [
        '*' => [
          'category',
          'products' => [
            '*' => [
              'id',
              'code',
              'name',
              'price',
              'thumbnail'
            ]
          ]
        ]
      ]
    ]);

    $data = $response->json();
    $this->assertTrue($data['success']);
    $this->assertNotEmpty($data['data']);

    // Kiểm tra sản phẩm có đúng giá
    $firstProduct = $data['data'][0]['products'][0];
    $this->assertEquals($product->sale_price, $firstProduct['price']);
    $this->assertEquals($product->name, $firstProduct['name']);
  }
}
