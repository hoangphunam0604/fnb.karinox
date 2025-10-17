<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductCodeService
{
  /**
   * Generate product code tự động theo format: {PREFIX}{0000}
   * VD: CF0001, TEA0002, MILK0003
   */
  public function generateProductCode(?int $categoryId): string
  {
    if (!$categoryId) {
      return $this->generateGenericCode();
    }

    $category = Category::find($categoryId);
    if (!$category || !$category->code_prefix) {
      return $this->generateGenericCode();
    }

    $prefix = strtoupper($category->code_prefix);
    $nextNumber = $this->getNextSequentialNumber($categoryId, $prefix);

    return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
  }

  /**
   * Lấy số sequence tiếp theo cho category
   */
  private function getNextSequentialNumber(int $categoryId, string $prefix): int
  {
    // Lấy sản phẩm cuối cùng của category này (theo ID, không theo code)
    // Vì có thể có sản phẩm được tạo thủ công với code không theo thứ tự
    $lastProduct = Product::where('category_id', $categoryId)
      ->where('code', 'LIKE', $prefix . '%')
      ->orderBy('id', 'desc')
      ->first();

    if (!$lastProduct) {
      return 1; // Sản phẩm đầu tiên
    }

    // Extract số từ code (VD: CF0023 -> 23)
    $lastNumber = $this->extractNumberFromCode($lastProduct->code, $prefix);

    return $lastNumber + 1;
  }

  /**
   * Extract số từ product code
   * VD: CF0023 -> 23, TEA0001 -> 1
   */
  private function extractNumberFromCode(string $code, string $prefix): int
  {
    // Remove prefix và parse số
    $numberPart = str_replace($prefix, '', $code);
    return (int) $numberPart;
  }

  /**
   * Generate code chung khi không có category hoặc category không có prefix
   */
  private function generateGenericCode(): string
  {
    $prefix = 'PRD';
    $lastProduct = Product::where('code', 'LIKE', $prefix . '%')
      ->orderBy('id', 'desc')
      ->first();

    $nextNumber = 1;
    if ($lastProduct) {
      $nextNumber = $this->extractNumberFromCode($lastProduct->code, $prefix) + 1;
    }

    return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
  }

  /**
   * Tự động tạo prefix từ tên category
   * VD: "Cà phê" -> "CF", "Trà xanh" -> "TEA", "Sữa tươi" -> "MILK"
   */
  public function generatePrefixFromName(string $categoryName): string
  {
    // Mapping thủ công cho tiếng Việt
    $mappings = [
      'cà phê' => 'CF',
      'coffee' => 'CF',
      'trà' => 'TEA',
      'tea' => 'TEA',
      'sữa' => 'MILK',
      'milk' => 'MILK',
      'topping' => 'TOP',
      'bánh' => 'CAKE',
      'cake' => 'CAKE',
      'nước' => 'DRINK',
      'drink' => 'DRINK',
      'đá' => 'ICE',
      'ice' => 'ICE',
    ];

    $name = mb_strtolower($categoryName);

    // Tìm mapping đã định nghĩa
    foreach ($mappings as $keyword => $prefix) {
      if (str_contains($name, $keyword)) {
        return $prefix;
      }
    }

    // Fallback: Lấy 2-3 ký tự đầu của từ đầu tiên
    $words = explode(' ', $name);
    $firstWord = $words[0];

    // Remove diacritics (bỏ dấu tiếng Việt)
    $firstWord = $this->removeDiacritics($firstWord);

    if (strlen($firstWord) >= 3) {
      return strtoupper(substr($firstWord, 0, 3));
    } else {
      return strtoupper($firstWord);
    }
  }

  /**
   * Remove Vietnamese diacritics
   */
  private function removeDiacritics(string $str): string
  {
    $diacritics = [
      'à' => 'a',
      'á' => 'a',
      'ả' => 'a',
      'ã' => 'a',
      'ạ' => 'a',
      'ă' => 'a',
      'ằ' => 'a',
      'ắ' => 'a',
      'ẳ' => 'a',
      'ẵ' => 'a',
      'ặ' => 'a',
      'â' => 'a',
      'ầ' => 'a',
      'ấ' => 'a',
      'ẩ' => 'a',
      'ẫ' => 'a',
      'ậ' => 'a',
      'è' => 'e',
      'é' => 'e',
      'ẻ' => 'e',
      'ẽ' => 'e',
      'ẹ' => 'e',
      'ê' => 'e',
      'ề' => 'e',
      'ế' => 'e',
      'ể' => 'e',
      'ễ' => 'e',
      'ệ' => 'e',
      'ì' => 'i',
      'í' => 'i',
      'ỉ' => 'i',
      'ĩ' => 'i',
      'ị' => 'i',
      'ò' => 'o',
      'ó' => 'o',
      'ỏ' => 'o',
      'õ' => 'o',
      'ọ' => 'o',
      'ô' => 'o',
      'ồ' => 'o',
      'ố' => 'o',
      'ổ' => 'o',
      'ỗ' => 'o',
      'ộ' => 'o',
      'ơ' => 'o',
      'ờ' => 'o',
      'ớ' => 'o',
      'ở' => 'o',
      'ỡ' => 'o',
      'ợ' => 'o',
      'ù' => 'u',
      'ú' => 'u',
      'ủ' => 'u',
      'ũ' => 'u',
      'ụ' => 'u',
      'ư' => 'u',
      'ừ' => 'u',
      'ứ' => 'u',
      'ử' => 'u',
      'ữ' => 'u',
      'ự' => 'u',
      'ỳ' => 'y',
      'ý' => 'y',
      'ỷ' => 'y',
      'ỹ' => 'y',
      'ỵ' => 'y',
      'đ' => 'd',
    ];

    return strtr($str, $diacritics);
  }

  /**
   * Validate xem code có hợp lệ không
   */
  public function isValidProductCode(string $code): bool
  {
    // Format: PREFIX + 4 digits
    return preg_match('/^[A-Z]{2,10}\d{4}$/', $code);
  }

  /**
   * Check xem code đã tồn tại chưa
   */
  public function isCodeExists(string $code, ?int $excludeProductId = null): bool
  {
    $query = Product::where('code', $code);

    if ($excludeProductId) {
      $query->where('id', '!=', $excludeProductId);
    }

    return $query->exists();
  }
}
