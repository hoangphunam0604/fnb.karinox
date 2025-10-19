<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class PrintClientAuth
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Kiểm tra print client authentication
    $apiKey = $request->header('X-Print-Client-Key');
    $deviceId = $request->header('X-Device-ID') ?: $request->get('device_id');

    // Validation
    if (!$apiKey) {
      return response()->json([
        'success' => false,
        'message' => 'Missing X-Print-Client-Key header'
      ], 401);
    }

    if (!$deviceId) {
      return response()->json([
        'success' => false,
        'message' => 'Missing device_id parameter or X-Device-ID header'
      ], 401);
    }

    // Kiểm tra API key
    $validApiKey = config('print.client_api_key', 'karinox_print_client_2025');

    if ($apiKey !== $validApiKey) {
      Log::warning('Invalid print client API key', [
        'provided_key' => $apiKey,
        'device_id' => $deviceId,
        'ip' => $request->ip()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Invalid API key'
      ], 401);
    }

    // Validation device ID format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $deviceId)) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid device ID format'
      ], 400);
    }

    // Set device context cho request
    $request->merge(['authenticated_device_id' => $deviceId]);

    Log::info('Print client authenticated', [
      'device_id' => $deviceId,
      'endpoint' => $request->path(),
      'ip' => $request->ip()
    ]);

    return $next($request);
  }
}
