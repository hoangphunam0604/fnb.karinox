<?php

namespace Database\Factories;

use App\Models\ProductAttribute;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAttributeFactory extends Factory
{
  protected $model = ProductAttribute::class;

  public function definition()
  {
    return [
      'product_id' => Product::factory(), // Tạo sản phẩm giả lập
      'attribute_id' => Attribute::factory(), // Tạo thuộc tính giả lập
      'value' => $this->faker->randomElement(['Nhỏ', 'Vừa', 'Lớn', 'Mát lạnh', 'Nóng']), // Giá trị thuộc tính giả lập
    ];
  }
}
