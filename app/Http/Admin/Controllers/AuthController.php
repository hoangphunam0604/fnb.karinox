<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Auth\Resources\UserResource;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
  // Đăng nhập
  public function login(Request $request)
  {
    $credentials = $request->only('username', 'password');
    if (!$token = JWTAuth::attempt($credentials)) {
      return response()->json(['error' => 'Tài khoản hoặc mật khẩu không chính xác'], 404);
    }

    return $this->respondWithToken($token);
  }

  // Lấy thông tin người dùng
  public function me()
  {
    return response()->json(auth('api')->user());
  }

  // Đăng xuất
  public function logout()
  {
    JWTAuth::logout();
    return response()->json(['message' => 'Logged out successfully']);
  }

  // Làm mới token
  public function refresh()
  {
    return $this->respondWithToken(JWTAuth::refresh());
  }

  protected function respondWithToken($token)
  {
    $user = JWTAuth::user();
    return response()->json([
      'user' => new UserResource($user),
      'access_token' => $token,
      'token_type' => 'Bearer'
    ]);
  }
}
