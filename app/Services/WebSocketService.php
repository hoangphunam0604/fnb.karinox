<?php

namespace App\Services;


class WebSocketService
{
  public function getWebSocketConfig(string $channelName, string $eventName): array
  {
    return [
      'websocket_url' => $this->getWebSocketUrl(),
      'channel_name' => $channelName,
      'event_name' => $eventName
    ];
  }

  /**
   * Lấy WebSocket URL
   */
  private function getWebSocketUrl(): ?string
  {
    $appKey = config('broadcasting.connections.reverb.key');
    $host = config('broadcasting.connections.reverb.options.host', config('app.url'));
    $port = config('broadcasting.connections.reverb.options.port', 6001);
    $scheme = config('broadcasting.connections.reverb.options.scheme', 'ws');

    if (!$appKey) {
      return null;
    }

    // Xử lý host URL
    if (str_starts_with($host, 'http://') || str_starts_with($host, 'https://')) {
      $host = parse_url($host, PHP_URL_HOST);
    }

    // Determine WebSocket protocol
    $protocol = $scheme === 'https' ? 'wss' : 'ws';

    return "{$protocol}://{$host}:{$port}/app/{$appKey}";
  }
}
