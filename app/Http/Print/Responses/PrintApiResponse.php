<?php

namespace App\Http\Print\Responses;

use Illuminate\Http\JsonResponse;

class PrintApiResponse
{
  /**
   * Success response
   */
  public static function success(string $message, $data = null, int $status = 200): JsonResponse
  {
    $response = [
      'success' => true,
      'message' => $message
    ];

    if ($data !== null) {
      $response['data'] = $data;
    }

    return response()->json($response, $status);
  }

  /**
   * Error response
   */
  public static function error(string $message, int $status = 500, $errors = null): JsonResponse
  {
    $response = [
      'success' => false,
      'message' => $message
    ];

    if ($errors !== null) {
      $response['errors'] = $errors;
    }

    return response()->json($response, $status);
  }

  /**
   * Validation error response
   */
  public static function validationError(string $message = 'Dữ liệu không hợp lệ', $errors = null): JsonResponse
  {
    return self::error($message, 422, $errors);
  }

  /**
   * Not found response
   */
  public static function notFound(string $message = 'Không tìm thấy tài nguyên'): JsonResponse
  {
    return self::error($message, 404);
  }
}
