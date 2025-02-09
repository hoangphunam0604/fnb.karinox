<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Attribute;
use App\Models\ProductBranch;
use App\Models\ProductAttribute;
use App\Models\ProductFormula;
use App\Models\ProductTopping;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductServiceTest extends TestCase
{
  use RefreshDatabase;

  protected $productService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->productService = new ProductService();
  }

  /**
   * Test tạo sản phẩm mới với chi nhánh, thuộc tính, thành phần và topping
   */
  public function test_create_product_with_relations()
  {
    $category = Category::factory()->create();
    $branch = Branch::factory()->create();
    $attribute = Attribute::factory()->create();
    $topping = Product::factory()->create();
    $ingredient = Product::factory()->create();

    $productData = [
      'code' => 'P001',
      'name' => 'Cà phê sữa',
      'category_id' => $category->id,
      'product_type' => 'processed',
      'branches' => [
        ['branch_id' => $branch->id, 'stock_quantity' => 50]
      ],
      'attributes' => [
        ['attribute_id' => $attribute->id, 'value' => 'Lạnh']
      ],
      'formulas' => [
        ['ingredient_id' => $ingredient->id, 'quantity' => 2]
      ],
      'toppings' => [
        ['topping_id' => $topping->id, 'quantity' => 1]
      ],
    ];

    $product = $this->productService->saveProduct($productData);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Cà phê sữa']);
    $this->assertDatabaseHas('product_branches', ['product_id' => $product->id, 'branch_id' => $branch->id, 'stock_quantity' => 50]);
    $this->assertDatabaseHas('product_attributes', ['product_id' => $product->id, 'attribute_id' => $attribute->id, 'value' => 'Lạnh']);
    $this->assertDatabaseHas('product_formulas', ['product_id' => $product->id, 'ingredient_id' => $ingredient->id, 'quantity' => 2]);
    $this->assertDatabaseHas('product_toppings', ['product_id' => $product->id, 'topping_id' => $topping->id, 'quantity' => 1]);
  }

  /**
   * Test cập nhật sản phẩm
   */
  public function test_update_product()
  {
    $product = Product::factory()->create();
    $branch = Branch::factory()->create();
    $attribute = Attribute::factory()->create();

    $updatedData = [
      'name' => 'Cà phê đen đá',
      'branches' => [
        ['branch_id' => $branch->id, 'stock_quantity' => 60]
      ],
      'attributes' => [
        ['attribute_id' => $attribute->id, 'value' => 'Nóng']
      ],
    ];

    $updatedProduct = $this->productService->saveProduct($updatedData, $product->id);

    $this->assertEquals('Cà phê đen đá', $updatedProduct->name);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Cà phê đen đá']);
    $this->assertDatabaseHas('product_branches', ['product_id' => $product->id, 'branch_id' => $branch->id, 'stock_quantity' => 60]);
    $this->assertDatabaseHas('product_attributes', ['product_id' => $product->id, 'attribute_id' => $attribute->id, 'value' => 'Nóng']);
  }

  /**
   * Test tìm kiếm sản phẩm theo mã code
   */
  public function test_find_product_by_code()
  {
    $product = Product::factory()->create(['code' => 'P002']);

    $foundProduct = $this->productService->findProduct('P002');

    $this->assertNotNull($foundProduct);
    $this->assertEquals('P002', $foundProduct->code);
  }

  /**
   * Test xóa sản phẩm
   */
  public function test_delete_product()
  {
    $product = Product::factory()->create();

    $this->productService->deleteProduct($product->id);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
  }
}
