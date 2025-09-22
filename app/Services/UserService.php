<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserService extends BaseService
{
  protected array $with = ['roles'];

  protected function model(): Model
  {
    return new User();
  }

  protected function applySearch($query, array $params)
  {
    // support keyword/q for fullname/username search
    $keyword = $params['q'] ?? $params['keyword'] ?? null;
    if (!empty($keyword)) {
      $query->where(function ($sub) use ($keyword) {
        $sub->where('fullname', 'LIKE', '%' . $keyword . '%')
          ->orWhere('username', 'LIKE', '%' . $keyword . '%');
      });
    }

    // delegate ids/excludes handling to BaseService
    return parent::applySearch($query, $params);
  }


  /**
   * Create a user and optionally sync roles.
   *
   * @param array $data
   * @param array|string|null $roles
   * @return User
   */
  /**
   * Compatibility wrapper for BaseService::create
   * Creates a user without syncing roles.
   *
   * @param array $data
   * @return Model
   */
  public function create(array $data): Model
  {
    // Keep behavior similar to BaseService but reuse createUser for full feature set
    return $this->createUser($data, null);
  }

  /**
   * Create a user and optionally sync roles.
   *
   * @param array $data
   * @param array|string|null $roles
   * @return User
   */
  public function createUser(array $data, string|null $role = null): User
  {
    return DB::transaction(function () use ($data, $role) {
      if (!empty($data['password'])) {
        $data['password'] = bcrypt($data['password']);
      } else {
        unset($data['password']);
      }

      /** @var User $user */
      $user = $this->model()->create($data);

      if ($role !== null) {
        $roleNames = $this->resolveRoleNames($role);
        if (!empty($roleNames)) {
          $name = $roleNames[0];
          $roleModel = Role::where('name', $name)->first();
          if (!$roleModel) {
            $roleModel = Role::create(['name' => $name, 'guard_name' => config('auth.defaults.guard')]);
          }
          $user->syncRoles([$roleModel->name]);
        }
      }

      return $user->fresh()->load($this->with);
    });
  }

  /**
   * Update a user and optionally sync roles. If $roles is null, roles are left unchanged.
   *
   * @param User $user
   * @param array $data
   * @param array|string|null $roles
   * @return User
   */
  /**
   * Compatibility wrapper for BaseService::update($id, array $data)
   * If the incoming data contains 'roles', it will be used to sync roles.
   *
   * @param mixed $id
   * @param array $data
   * @return Model
   */
  public function update($id, array $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $model = $this->model()->findOrFail($id);
      $roles = null;
      if (array_key_exists('role', $data)) {
        $roles = $data['role'];
        unset($data['role']);
      }

      return $this->updateUser($model, $data, $roles);
    });
  }

  /**
   * Update a user and optionally sync roles. If $roles is null, roles are left unchanged.
   *
   * @param User $user
   * @param array $data
   * @param array|string|null $roles
   * @return User
   */
  public function updateUser(User $user, array $data, string|null $role = null): User
  {
    return DB::transaction(function () use ($user, $data, $role) {
      if (array_key_exists('password', $data)) {
        if (!empty($data['password'])) {
          $data['password'] = bcrypt($data['password']);
        } else {
          unset($data['password']);
        }
      }

      $user->update($data);

      if ($role !== null) {
        $roleNames = $this->resolveRoleNames($role);
        if (!empty($roleNames)) {
          $name = $roleNames[0];
          $roleModel = Role::where('name', $name)->first();
          if (!$roleModel) {
            $roleModel = Role::create(['name' => $name, 'guard_name' => config('auth.defaults.guard')]);
          }
          $user->syncRoles([$roleModel->name]);
        } else {
          // if role provided but none resolved, remove all roles
          $user->syncRoles([]);
        }
      }

      return $user->fresh()->load($this->with);
    });
  }

  /**
   * Accepts array of ids or names, or comma separated string, and returns role names array.
   *
   * @param array|string $rolesInput
   * @return array
   */
  protected function resolveRoleNames(array|string $rolesInput): array
  {
    if (is_string($rolesInput)) {
      $rolesInput = array_filter(array_map('trim', explode(',', $rolesInput)));
    }

    $roles = is_array($rolesInput) ? $rolesInput : [];

    $ids = array_values(array_filter($roles, fn($r) => is_numeric($r)));
    $names = array_values(array_filter($roles, fn($r) => !is_numeric($r)));

    $resolved = [];

    if (!empty($ids)) {
      $resolved = Role::whereIn('id', $ids)->pluck('name')->toArray();
    }

    if (!empty($names)) {
      $resolved = array_values(array_unique(array_merge($resolved, $names)));
    }

    return $resolved;
  }
}
