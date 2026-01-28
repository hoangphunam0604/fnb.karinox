<?php

use App\Http\Common\Middleware\HandleAppearance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    channels: __DIR__ . '/../routes/channels.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->encryptCookies(except: ['appearance']);

    $middleware->web(append: [
      HandleAppearance::class,
      AddLinkHeadersForPreloadedAssets::class,
    ]);
    $middleware->api(append: [
      \Illuminate\Http\Middleware\HandleCors::class,
    ]);
    $middleware->alias([
      'is_karinox_app' => \App\Http\Common\Middleware\IsKarinoxAppMiddleware::class,
      'set_karinox_branch_id' => \App\Http\Common\Middleware\SetKarinoxBranchIdMiddleware::class,
      // Spatie permission middleware aliases
      'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
      'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
      'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {

    // Xử lý lỗi API chung
    $exceptions->render(function (\Throwable $e, $request) {
      if ($request->is('api/*')) {
        $statusCode = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
          ? $e->getStatusCode()
          : 500;

        $message = $e->getMessage();
        if ($e instanceof \Illuminate\Validation\ValidationException) {
          return response()->json([
            'success' => false,
            'message' => $message,
            'messages' => $e->errors(),
            'code' => 422
          ], 422);
        }

        // Danh sách mã lỗi và thông điệp tương ứng
        /* $errorResponses = [
          \Illuminate\Auth\AuthenticationException::class => ['Bạn không có quyền truy cập.', 401],
          \Illuminate\Auth\Access\AuthorizationException::class => ['Bạn không có quyền thực hiện hành động này.', 403],
          \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class => ['Không tìm thấy dữ liệu.', 404],
          \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class => ['Phương thức không được phép.', 405],
          \Illuminate\Validation\ValidationException::class => [' Dữ liệu không hợp lệ.', 422],
          \Illuminate\Http\Exceptions\ThrottleRequestsException::class => ['Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.', 429]
        ];
        foreach ($errorResponses as $exceptionClass => [$errorMessage, $code]) {
          if ($e instanceof $exceptionClass) {
            return response()->json([
              'success' => false,
              'message' => $errorMessage,
              'code' => $code
            ], $code);
          }
        } */

        return response()->json([
          'success' => false,
          'message' => $message,
          'code' => $statusCode
        ], $statusCode);
      }
    });
  })->create();
