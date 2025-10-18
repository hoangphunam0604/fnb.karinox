<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Branch;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

class POSProductAPITest extends TestCase
{
  use RefreshDatabase;

  public function test_pos_products_api_returns_data()
  {
    // Tạo test data
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $token = JWTAuth::fromUser($user);

    // Tạo category và product với ProductBranch
    $category = \App\Models\Category::create([
      'name' => 'Đồ uống',
      'code_prefix' => 'DU',
    ]);

    $product = \App\Models\Product::create([
      'name' => 'Cà phê đen',
      'code' => 'CF001',
      'regular_price' => 25000,
      'category_id' => $category->id,
      'allows_sale' => true,
      'product_type' => \App\Enums\ProductType::PROCESSED,
    ]);

    // Tạo ProductBranch record
    \App\Models\ProductBranch::create([
      'product_id' => $product->id,
      'branch_id' => $branch->id,
      'price_adjustment' => 0,
    ]);

    // Call API
    $response = $this->withHeaders([
      'Authorization' => 'Bearer ' . $token,
      'Accept' => 'application/json',
      'karinox-app-id' => 'karinox-app-pos',
      'Karinox-Branch-Id' => $branch->id,
    ])->getJson('/api/pos/products');

    // Debug response
    dump('Status:', $response->status());
    dump('Response:', $response->json());

    $response->assertStatus(200);
    $response->assertJsonStructure([
      'success',
      'data'
    ]);

    // Kiểm tra có data
    $data = $response->json('data');
    $this->assertNotEmpty($data);
    $this->assertEquals('Đồ uống', $data[0]['category']);
    $this->assertEquals('Cà phê đen', $data[0]['products'][0]['name']);
  }
}
