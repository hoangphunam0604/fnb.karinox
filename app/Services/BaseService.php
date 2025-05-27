<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
  abstract protected function model(): Model;

  public function getList(array $params = [])
  {
    $query = $this->model()->newQuery();

    if (!empty($params['keyword'])) {
      $query->where('name', 'LIKE', '%' . $params['keyword'] . '%');
    }

    $perPage = $params['per_page'] ?? 10;
    return $query->orderBy('created_at', 'desc')->paginate($perPage);
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
    return $this->model()->findOrFail($id);
  }
}
