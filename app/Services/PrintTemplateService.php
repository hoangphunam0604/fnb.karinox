<?php

namespace App\Services;

use App\Models\PrintTemplate;

class PrintTemplateService
{
  public static function getDefaultTemplate(string $type): ?PrintTemplate
  {
    return PrintTemplate::where('type', $type)
      ->where('is_active', true)
      ->where('is_default', true)
      ->first();
  }

  public static function getById(int $id): ?PrintTemplate
  {
    return PrintTemplate::where('is_active', true)->find($id);
  }

  public static function getUsedTemplateInBranch($branchId)
  {
    return PrintTemplate::whereBranchId($branchId)->whereIsDefault(true)->get();
  }

  public static function getAll(array $filters = [], int $perPage = 20)
  {
    $query = PrintTemplate::query();

    if (isset($filters['type'])) {
      $query->where('type', $filters['type']);
    }

    if (isset($filters['is_active'])) {
      $query->where('is_active', $filters['is_active']);
    }

    if (isset($filters['is_default'])) {
      $query->where('is_default', $filters['is_default']);
    }

    return $perPage === 0 ? $query->get() : $query->paginate($perPage);
  }

  public static function create(array $data): PrintTemplate
  {
    if (!empty($data['is_default']) && $data['type']) {
      // Hủy cờ default cũ
      PrintTemplate::where('type', $data['type'])->update(['is_default' => false]);
    }

    return PrintTemplate::create($data);
  }

  public static function update(PrintTemplate $template, array $data): PrintTemplate
  {
    if (!empty($data['is_default']) && $data['type']) {
      PrintTemplate::where('type', $data['type'])->update(['is_default' => false]);
    }

    $template->update($data);
    return $template;
  }

  public static function delete(PrintTemplate $template): void
  {
    $template->delete();
  }
}
