<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Branch;
use App\Models\Attribute;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
  use RefreshDatabase;

  protected ProductService $productService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->productService = new ProductService();
  }

  /** @test */
  public function test_create_product()
  {
    $branch = Branch::factory()->create();
    $attribute = Attribute::factory()->create(); // Tạo attribute hợp lệ
    $formulaProduct = Product::factory()->create(['product_type' => 'goods']);
    $toppingProduct = Product::factory()->create(['is_topping' => true, 'product_type' => 'goods']);

    $data = [
      'code' => 'PROD123',
      'name' => 'Cà phê sữa',
      'category_id' => 1,
      'product_type' => 'goods',
      'branches' => [
        ['branch_id' => $branch->id, 'stock_quantity' => 10],
      ],
      'attributes' => [
        ['attribute_id' => $attribute->id, 'value' => '500ml'], // Sử dụng ID hợp lệ
      ],
      'formulas' => [
        ['ingredient_id' => $formulaProduct->id, 'quantity' => 2],
      ],
      'toppings' => [$toppingProduct->id],
    ];

    $product = $this->productService->createProduct($data);

    $this->assertDatabaseHas('products', [
      'id' => $product->id,
      'name' => 'Cà phê sữa',
      'allows_sale' => 1, // Kiểm tra giá trị mặc định
      'is_reward_point' => 0, // Kiểm tra giá trị mặc định
      'is_topping' => 0, // Kiểm tra giá trị mặc định
      'product_group' => 1, // Giá trị mặc định trong DB
    ]);
    $this->assertDatabaseHas('product_branches', ['product_id' => $product->id, 'branch_id' => $branch->id]);
    $this->assertDatabaseHas('product_attributes', ['product_id' => $product->id, 'attribute_id' => $attribute->id]);
    $this->assertDatabaseHas('product_formulas', ['product_id' => $product->id, 'ingredient_id' => $formulaProduct->id]);
    $this->assertDatabaseHas('product_toppings', ['product_id' => $product->id, 'topping_id' => $toppingProduct->id]);
  }

  /** @test */
  public function test_update_product()
  {
    $branch = Branch::factory()->create();
    $attribute = Attribute::factory()->create(); // Tạo attribute hợp lệ
    $formulaProduct = Product::factory()->create(['product_type' => 'goods']);
    $toppingProduct = Product::factory()->create(['is_topping' => true, 'product_type' => 'goods']);

    $product = Product::factory()->create(['code' => 'PROD001', 'name' => 'Cà phê đen', 'product_type' => 'processed']);

    $updateData = [
      'code' => 'PROD001',
      'name' => 'Cà phê đen đá',
      'product_type' => 'processed',
      'allows_sale' => 0,
      'is_reward_point' => 1,
      'is_topping' => 1,
      'product_group' => 2,
      'branches' => [
        ['branch_id' => $branch->id, 'stock_quantity' => 15],
      ],
      'attributes' => [
        ['attribute_id' => $attribute->id, 'value' => '500ml'], // Đảm bảo attribute_id hợp lệ
      ],
      'formulas' => [
        ['ingredient_id' => $formulaProduct->id, 'quantity' => 3],
      ],
      'toppings' => [$toppingProduct->id],
    ];

    $updatedProduct = $this->productService->updateProduct($product->id, $updateData);

    $this->assertDatabaseHas('products', [
      'id' => $product->id,
      'code' => 'PROD001',
      'name' => 'Cà phê đen đá',
      'product_type' => 'processed',
      'allows_sale' => 0,
      'is_reward_point' => 1,
      'is_topping' => 1,
      'product_group' => 2,
    ]);

    $this->assertEquals('Cà phê đen đá', $updatedProduct->name);
    $this->assertDatabaseHas('product_branches', ['product_id' => $product->id, 'branch_id' => $branch->id, 'stock_quantity' => 15]);
    $this->assertDatabaseHas('product_attributes', ['product_id' => $product->id, 'attribute_id' => $attribute->id]);
    $this->assertDatabaseHas('product_formulas', ['product_id' => $product->id, 'ingredient_id' => $formulaProduct->id]);
    $this->assertDatabaseHas('product_toppings', ['product_id' => $product->id, 'topping_id' => $toppingProduct->id]);
  }

  /** @test */
  public function test_only_valid_toppings_are_added()
  {
    $product = Product::factory()->create(['is_topping' => false, 'product_type' => 'goods']);
    $validTopping = Product::factory()->create(['is_topping' => true, 'product_type' => 'goods']);
    $invalidTopping = Product::factory()->create(['is_topping' => false, 'product_type' => 'goods']);

    $updateData = [
      'toppings' => [$validTopping->id, $invalidTopping->id],
    ];

    $this->productService->updateProduct($product->id, $updateData);

    $this->assertDatabaseHas('product_toppings', [
      'product_id' => $product->id,
      'topping_id' => $validTopping->id,
    ]);

    $this->assertDatabaseMissing('product_toppings', [
      'product_id' => $product->id,
      'topping_id' => $invalidTopping->id,
    ]);
  }

  /** @test */
  public function test_delete_product()
  {
    $product = Product::factory()->create(['code' => 'PROD002', 'product_type' => 'service']);

    $this->productService->deleteProduct($product->id);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
  }

  /** @test */
  public function test_find_product()
  {
    Product::factory()->create(['code' => 'FIND123', 'name' => 'Trà sữa', 'product_type' => 'combo']);

    $foundByCode = $this->productService->findProduct('FIND123');
    $this->assertNotNull($foundByCode);
    $this->assertEquals('Trà sữa', $foundByCode->name);

    $foundByName = $this->productService->findProduct('Trà sữa');
    $this->assertNotNull($foundByName);
    $this->assertEquals('FIND123', $foundByName->code);
  }

  /** @test */
  public function test_get_products()
  {
    Product::factory()->count(15)->create();

    $products = $this->productService->getProducts(10);

    $this->assertEquals(10, $products->count());
  }
}
