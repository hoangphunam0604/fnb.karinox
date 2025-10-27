<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\RoleRequest;
use App\Http\Admin\Resources\RoleResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
  public function index(Request $request)
  {
    $query = Role::query()->with('permissions');

    if ($q = $request->input('q')) {
      $query->where('name', 'like', "%{$q}%");
    }

    $roles = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

    return RoleResource::collection($roles);
  }

  public function store(RoleRequest $request)
  {
    $data = $request->validated();
    $role = Role::create(['name' => $data['name'], 'guard_name' => $data['guard_name'] ?? config('auth.defaults.guard')]);

    if (!empty($data['permissions'])) {
      $perms = Permission::whereIn('id', $data['permissions'])->get();
      $role->syncPermissions($perms);
    }

    return new RoleResource($role->load('permissions'));
  }

  public function show(Role $role)
  {
    return new RoleResource($role->load('permissions'));
  }

  public function update(RoleRequest $request, Role $role)
  {
    $data = $request->validated();
    $role->update(['name' => $data['name'], 'guard_name' => $data['guard_name'] ?? $role->guard_name]);

    if (isset($data['permissions'])) {
      $perms = Permission::whereIn('id', $data['permissions'])->get();
      $role->syncPermissions($perms);
    }

    return new RoleResource($role->fresh()->load('permissions'));
  }

  public function destroy(Role $role)
  {
    $role->delete();
    return response()->noContent();
  }
}
