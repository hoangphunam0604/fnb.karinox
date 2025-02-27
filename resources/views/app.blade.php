@php
// Xác định module nào đang được sử dụng
$prefix = request()->segment(1);

// Map module với file frontend
$viteFiles = [
'admin' => 'resources/js/admin.ts',
'pos' => 'resources/js/pos.ts',
'kitchen' => 'resources/js/kitchen.ts',
'manager' => 'resources/js/manager.ts',
];

// Nếu không khớp module nào, dùng mặc định là admin
$viteEntry = $viteFiles[$prefix] ?? 'resources/js/admin.ts';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title inertia>{{ config('app.name', 'Laravel') }}</title>

  @vite([$viteEntry])
  @inertiaHead
</head>

<body>
  @inertia
</body>

</html>