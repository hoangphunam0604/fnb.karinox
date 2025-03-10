<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{

  // Đăng nhập và lấy Access Token
  public function login(Request $request)
  {
    $request->validate([
      'username' => 'required',
      'password' => 'required',
    ]);

    $credentials = $request->only('username', 'password');


    $user = User::where('username', $request->username)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Tạo access token với Laravel Passport
    $moduleName = 'api';
    $tokenName = "$moduleName Access Token for {$user->name} (ID: {$user->id})";
    $token = $user->createToken($tokenName)->accessToken;

    return response()->json([
      'message' => 'Đăng nhập thành công!',
      'user' => new UserResource($user),
      'access_token' => $token,
      'token_type' => 'Bearer',
    ]);
  }

  // Đăng xuất và hủy Access Token
  public function logout(Request $request)
  {
    $request->user()->token()->revoke();

    return response()->json([
      'message' => 'Successfully logged out'
    ]);
  }

  // Lấy thông tin người dùng hiện tại
  public function me(Request $request)
  {
    return response()->json($request->user());
  }
}
