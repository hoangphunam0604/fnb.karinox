<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
  protected array $with = [];
  protected array $withCount = [];
  protected $sortBy = 'created_at';
  protected $sortDir = 'desc';

  abstract protected function model(): Model;

  protected function applySearch($query, array $params)
  {
    // ids có thể là array hoặc chuỗi "1,2,3"
    $ids = $params['ids'] ?? [];
    if (is_string($ids)) {
      $ids = array_filter(array_map('intval', explode(',', $ids)));
    } elseif (is_array($ids)) {
      $ids = array_filter(array_map('intval', $ids));
    }

    if (!empty($ids)) {
      $query->whereIn('id', $ids);
    }

    // excludes có thể là array hoặc chuỗi "1,2,3"
    $excludes = $params['excludes'] ?? [];
    if (is_string($excludes)) {
      $excludes = array_filter(array_map('intval', explode(',', $excludes)));
    } elseif (is_array($excludes)) {
      $excludes = array_filter(array_map('intval', $excludes));
    }

    if (!empty($excludes)) {
      $query->whereNotIn('id', $excludes);
    }
    return $query;
  }
  protected function getQueryBuilder(): Builder
  {
    return $this->model()->newQuery()->with($this->with)->withCount($this->withCount);
  }
  public function getList(array $params = [])
  {
    $query = $this->getQueryBuilder();

    $query = $this->applySearch($query, $params);

    $perPage = $params['per_page'] ?? 10;
    $sortBy = $params['sort_by'] ?? $this->sortBy;
    $sortDir = $params['sort_direction'] ?? $this->sortDir;

    $query->orderBy($sortBy, $sortDir);
    return $query->paginate($perPage);
  }

  public function getAll()
  {
    return $this->model()->newQuery()->orderBy('name', 'asc')->get();
  }

  public function create(array $data): Model
  {
    return DB::transaction(fn() => $this->model()->create($data));
  }

  public function update($id, array $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $model = $this->model()->findOrFail($id);
      $model->update($data);
      return $model;
    });
  }

  public function delete($id): bool
  {
    return $this->model()->findOrFail($id)->delete();
  }

  public function find($id): Model
  {
    return $this->getQueryBuilder()->findOrFail($id);
  }
}
