<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Requests\UserRequest;
use App\Http\Admin\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
  protected UserService $service;

  public function __construct(UserService $service)
  {
    $this->service = $service;
  }
  /**
   * Display a paginated list of users.
   */
  public function index(Request $request)
  {
    $params = $request->all();
    $users = $this->service->getList($params);
    return UserResource::collection($users);
  }

  /**
   * Store a newly created user.
   */
  public function store(UserRequest $request)
  {
    $user = $this->service->create($request->validated());
    return new UserResource($user);
  }

  /**
   * Display the specified user.
   */
  public function show(User $user)
  {
    return new UserResource($user->load(['roles']));
  }

  /**
   * Update the specified user.
   */
  public function update(UserRequest $request,  $id)
  {
    $user = $this->service->update($id, $request->validated());
    return new UserResource($user->load('roles'));
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
