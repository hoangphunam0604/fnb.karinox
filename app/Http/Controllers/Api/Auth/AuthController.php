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
      'email' => 'required|email',
      'password' => 'required',
    ]);

    $credentials = $request->only('email', 'password');

    if (!Auth::attempt($credentials)) {
      return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();

    // Tạo access token với Laravel Passport
    $token = $user->createToken('Personal Access Token')->accessToken;

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
