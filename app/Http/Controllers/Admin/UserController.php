<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
  /**
   * Display a paginated list of users.
   */
  public function index(Request $request)
  {
    $query = User::query();

    if ($q = $request->input('q')) {
      $query->where(function ($qb) use ($q) {
        $qb->where('fullname', 'like', "%{$q}%")
          ->orWhere('username', 'like', "%{$q}%");
      });
    }

    $users = $query->with('currentBranch')->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

    return UserResource::collection($users);
  }

  /**
   * Store a newly created user.
   */
  public function store(UserRequest $request)
  {
    $data = $request->validated();

    if (!empty($data['password'])) {
      $data['password'] = bcrypt($data['password']);
    } else {
      unset($data['password']);
    }

    $user = User::create($data);

    return new UserResource($user->load('currentBranch'));
  }

  /**
   * Display the specified user.
   */
  public function show(User $user)
  {
    return new UserResource($user->load('currentBranch'));
  }

  /**
   * Update the specified user.
   */
  public function update(UserRequest $request, User $user)
  {
    $data = $request->validated();

    if (isset($data['password']) && $data['password'] !== null) {
      $data['password'] = bcrypt($data['password']);
    } else {
      unset($data['password']);
    }

    $user->update($data);

    return new UserResource($user->fresh()->load('currentBranch'));
  }

  /**
   * Remove the specified user.
   */
  public function destroy(User $user)
  {
    $user->delete();

    return response()->noContent();
  }
}
