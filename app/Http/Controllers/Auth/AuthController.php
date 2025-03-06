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

    if (Auth::attempt($credentials, false)) {
      /** @var User|null $user */
      $user = Auth::user();

      return redirect()->to($user->login_redirect);
      return response()->json([
        'status' => 'success',
        'user' => new UserResource($user),
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
