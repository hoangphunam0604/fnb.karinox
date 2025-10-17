<?php

namespace App\Services;

use App\Models\Category;
use App\Services\ProductCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategoryService extends BaseService
{
  protected array $with = ['parent'];
  protected ProductCodeService $codeService;

  public function __construct(ProductCodeService $codeService)
  {
    $this->codeService = $codeService;
  }

  protected function model(): Model
  {
    return new Category();
  }

  /**
   * Tạo category mới với auto-generate prefix
   */
  public function create(array $data): Category
  {
    return DB::transaction(function () use ($data) {
      // Auto-generate code_prefix nếu chưa có
      if (empty($data['code_prefix']) && !empty($data['name'])) {
        $data['code_prefix'] = $this->codeService->generatePrefixFromName($data['name']);

        // Ensure unique prefix
        $data['code_prefix'] = $this->ensureUniquePrefix($data['code_prefix']);
      }

      return Category::create($data);
    });
  }

  /**
   * Cập nhật category
   */
  public function update($id, array $data): Category
  {
    return DB::transaction(function () use ($id, $data) {
      $category = Category::findOrFail($id);

      // Nếu đổi tên mà chưa có prefix hoặc muốn regenerate prefix
      if (!empty($data['name']) && empty($data['code_prefix'])) {
        $data['code_prefix'] = $this->codeService->generatePrefixFromName($data['name']);
        $data['code_prefix'] = $this->ensureUniquePrefix($data['code_prefix'], $id);
      }

      $category->update($data);
      return $category;
    });
  }

  /**
   * Đảm bảo prefix là unique
   */
  private function ensureUniquePrefix(string $prefix, ?int $excludeId = null): string
  {
    $originalPrefix = $prefix;
    $counter = 1;

    while ($this->isPrefixExists($prefix, $excludeId)) {
      $prefix = $originalPrefix . $counter;
      $counter++;
    }

    return $prefix;
  }

  /**
   * Check xem prefix đã tồn tại chưa
   */
  private function isPrefixExists(string $prefix, ?int $excludeId = null): bool
  {
    $query = Category::where('code_prefix', $prefix);

    if ($excludeId) {
      $query->where('id', '!=', $excludeId);
    }

    return $query->exists();
  }

  /**
   * Suggest prefix dựa trên tên category
   */
  public function suggestPrefix(string $categoryName): array
  {
    $suggested = $this->codeService->generatePrefixFromName($categoryName);
    $unique = $this->ensureUniquePrefix($suggested);

    return [
      'suggested' => $suggested,
      'unique' => $unique,
      'is_available' => $suggested === $unique
    ];
  }
}
