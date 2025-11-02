<?php

namespace App\Services;

use App\Models\PrintTemplate;

class PrintTemplateService
{
  public function setDefault($id)
  {
    $template = PrintTemplate::findOrFail($id);

    // Hủy cờ default cũ
    PrintTemplate::where('type', $template->type)->update(['is_default' => false]);
    $template->is_default = true;
    $template->save();
    return $template;
  }

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

  public static function getUsedTemplateInBranch($branchId, $type = null)
  {
    $query = PrintTemplate::where('is_default', true)
      ->where('branch_id', $branchId);

    if ($type) {
      $query->where('type', $type);
    }

    return $query->orderBy('is_default', 'desc')
      ->orderBy('name')
      ->get();
  }

  public static function getAll(array $filters = [], int $perPage = 20)
  {
    $query = PrintTemplate::query();
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $filters['branch_id'];
    if ($branchId) {
      $query->where('branch_id', intval($branchId));
    }
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
    $data['is_default'] = false;
    $data['branch_id'] = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $data['branch_id'] ?? null;

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
