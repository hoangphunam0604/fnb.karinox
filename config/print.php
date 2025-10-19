<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Print System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the print system including client authentication,
    | queue processing, and device management.
    |
    */

  /*
    | Print Client API Key
    | Used for authenticating print client applications
    */
  'client_api_key' => env('PRINT_CLIENT_API_KEY', 'karinox_print_client_2025'),

  /*
    | Print Queue Configuration
    */
  'queue_enabled' => env('PRINT_QUEUE_ENABLED', true),
  'auto_process' => env('PRINT_AUTO_PROCESS', true),
  'retry_max' => env('PRINT_RETRY_MAX', 3),
  'poll_interval' => env('PRINT_POLL_INTERVAL', 5000), // milliseconds

  /*
    | Device Configuration
    */
  'device_timeout' => env('PRINT_DEVICE_TIMEOUT', 300), // seconds
  'max_jobs_per_request' => env('PRINT_MAX_JOBS_PER_REQUEST', 10),

  /*
    | Template Configuration
    */
  'default_template_branch' => null, // null = global templates
  'template_cache_ttl' => 3600, // seconds

  /*
    | Security
    */
  'rate_limit' => [
    'max_attempts' => 60,
    'decay_minutes' => 1
  ],

  /*
    | Logging
    */
  'log_print_jobs' => env('PRINT_LOG_JOBS', true),
  'log_device_activity' => env('PRINT_LOG_DEVICE_ACTIVITY', true),
];
