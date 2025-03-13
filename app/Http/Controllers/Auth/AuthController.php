<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{

  public function loginView()
  {
    return Inertia::render('Auth/Login');
  }
  public function login(Request $request)
  {
    $credentials = $request->only('username', 'password');

    if (Auth::guard('web')->attempt($credentials, false)) {
      $request->session()->regenerate(); // 🔥 Quan trọng: Đảm bảo session được giữ

      /** @var User|null $user */
      $user = Auth::user();

      return redirect()->intended('admin/branches')->with([
        'success' => 'Đăng nhập thành công!',
        'user' => $user,
      ]);
    }
    throw ValidationException::withMessages([
      'username' => __('Thông tin đăng nhập không chính xác.'),
    ]);
  }

  public function logout(Request $request)
  {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
  }
}
