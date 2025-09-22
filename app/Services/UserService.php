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


  public function create(array $data): Model
  {
    return DB::transaction(function () use ($data) {
      if (!empty($data['password'])) {
        $data['password'] = bcrypt($data['password']);
      } else {
        unset($data['password']);
      }

      $role = null;
      if (array_key_exists('role', $data)) {
        $role = $data['role'];
        unset($data['role']);
      }
      // extract branches if provided
      $branches = null;
      if (array_key_exists('branches', $data)) {
        $branches = $data['branches'];
        unset($data['branches']);
      }
      $user = $this->model()->create($data);
      $this->syncRole($user, $role);

      $this->syncBranchesForUser($user, $branches, $role);

      return $user->fresh()->load($this->with);
    });
  }
  public function update($id, array $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $user = $this->model()->findOrFail($id);
      if (array_key_exists('password', $data)) {
        if (!empty($data['password'])) {
          $data['password'] = bcrypt($data['password']);
        } else {
          unset($data['password']);
        }
      }
      $role = null;
      if (array_key_exists('role', $data)) {
        $role = $data['role'];
        unset($data['role']);
      }

      // extract branches if provided for syncing
      $branches = null;
      if (array_key_exists('branches', $data)) {
        $branches = $data['branches'];
        unset($data['branches']);
      }

      $user->update($data);
      $this->syncRole($user, $role);

      $this->syncBranchesForUser($user, $branches, $role);

      return $user->fresh()->load($this->with);
    });
  }

  protected function syncRole(User $user, $roles)
  {
    if ($roles !== null) {
      $roleNames = $this->resolveRoleNames($roles);
      $user->syncRoles($roleNames);
    }
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

  /**
   * Sync branch assignments for a user according to provided branches and role.
   * If role resolves to admin, branches will be detached. If branches is null and
   * role is admin on update, branches will be detached as well.
   *
   * @param User $user
   * @param array|string|null $branches
   * @param array|string|null $role
   * @return void
   */
  protected function syncBranchesForUser(User $user, array|string|null $branches, array|string|null $role = null): void
  {
    $resolvedRoleNames = $role !== null ? $this->resolveRoleNames($role) : $user->getRoleNames()->toArray();
    $isAdmin = in_array('admin', array_map('strtolower', $resolvedRoleNames), true);

    if ($branches !== null) {
      $branchIds = $this->normalizeIds($branches);
      if ($isAdmin) {
        $user->branches()->sync([]);
      } else {
        $user->branches()->sync($branchIds);
      }
      return;
    }

    // No branches provided: if role is admin, ensure branches are detached (useful on role change)
    if ($isAdmin) {
      $user->branches()->sync([]);
    }
  }

  /**
   * Normalize a branches input (array or CSV string) into an array of integer ids.
   *
   * @param array|string|null $input
   * @return array<int>
   */
  protected function normalizeIds(array|string|null $input): array
  {
    if ($input === null) {
      return [];
    }

    if (is_string($input)) {
      $input = array_filter(array_map('trim', explode(',', $input)), fn($v) => $v !== '');
    }

    $arr = is_array($input) ? $input : [];
    $ids = array_values(array_filter(array_map(fn($v) => is_numeric($v) ? (int) $v : null, $arr)));
    return $ids;
  }
}
