<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->encryptCookies(except: ['appearance']);

    $middleware->web(append: [
      HandleAppearance::class,
      HandleInertiaRequests::class,
      AddLinkHeadersForPreloadedAssets::class,
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
            'error' => $message,
            'messages' => $e->errors(),
            'code' => 422
          ], 422);
        }

        // Danh sách mã lỗi và thông điệp tương ứng
        $errorResponses = [
          \Illuminate\Auth\AuthenticationException::class => ['Unauthorized', 401],
          \Illuminate\Auth\Access\AuthorizationException::class => ['Forbidden', 403],
          \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class => ['Method Not Allowed', 405],
          \Illuminate\Validation\ValidationException::class => ['Validation failed', 422],
          \Illuminate\Http\Exceptions\ThrottleRequestsException::class => ['Too Many Requests', 429]
        ];
        foreach ($errorResponses as $exceptionClass => [$errorMessage, $code]) {
          if ($e instanceof $exceptionClass) {
            return response()->json([
              'success' => false,
              'error' => $errorMessage,
              'code' => $code
            ], $code);
          }
        }

        return response()->json([
          'success' => false,
          'error' => $message,
          'code' => $statusCode
        ], $statusCode);
      }
    });
  })->create();
