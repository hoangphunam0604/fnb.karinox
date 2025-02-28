<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
  protected $rootView = 'app';

  public function version(Request $request): ?string
  {
    return parent::version($request);
  }

  public function share(Request $request): array
  {
    $data = array_merge(parent::share($request), [
      'auth' => [
        'user' => $request->user() ? [
          'id' => $request->user()->id,
          'fullname' => $request->user()->fullname,
          'email' => $request->user()->email,
        ] : null,
      ],
      'ziggy' => (new Ziggy())->toArray() + ['location' => $request->url()], // ✅ Truyền giá trị trực tiếp

    ]);

    // 🚨 Debug dữ liệu ngay tại middleware
    logger()->info('Middleware HandleInertiaRequests - Shared Data:', $data);

    return $data;
  }
}
