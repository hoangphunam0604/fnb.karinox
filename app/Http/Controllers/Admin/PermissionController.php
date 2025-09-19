<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionRequest;
use App\Http\Resources\Admin\PermissionResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
  public function index(Request $request)
  {
    $query = Permission::query();

    if ($q = $request->input('q')) {
      $query->where('name', 'like', "%{$q}%");
    }

    $perms = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

    return PermissionResource::collection($perms);
  }

  public function store(PermissionRequest $request)
  {
    $data = $request->validated();
    $perm = Permission::create([
      'name' => $data['name'],
      'guard_name' => $data['guard_name'] ?? config('auth.defaults.guard'),
    ]);

    return new PermissionResource($perm);
  }

  public function show(Permission $permission)
  {
    return new PermissionResource($permission);
  }

  public function update(PermissionRequest $request, Permission $permission)
  {
    $data = $request->validated();
    $permission->update([
      'name' => $data['name'],
      'guard_name' => $data['guard_name'] ?? $permission->guard_name,
    ]);

    return new PermissionResource($permission->fresh());
  }

  public function destroy(Permission $permission)
  {
    $permission->delete();
    return response()->noContent();
  }
}
