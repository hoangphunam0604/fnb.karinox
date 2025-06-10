<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
  protected array $with = [];
  protected array $withCount = [];

  abstract protected function model(): Model;

  protected function applySearch($query, array $params)
  {
    if (!empty($params['keyword'])) {
      $query->where('name', 'LIKE', '%' . $params['keyword'] . '%');
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
    return $query->orderBy('created_at', 'desc')->paginate($perPage);
  }

  public function getAll()
  {
    return $this->model()->newQuery()->orderBy('name', 'asc')->get();
  }

  public function create(array $data): Model
  {
    return DB::transaction(fn() => $this->model()->create($data));
  }

  public function update($id, array $data): Model
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
