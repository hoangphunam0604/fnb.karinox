@php
// Xác định module nào đang được sử dụng
$prefix = request()->segment(1);

// Map module với file frontend
$viteFiles = [
'app' => 'resources/js/app.js',
'admin' => 'resources/js/admin.js',
'pos' => 'resources/js/pos.js',
'kitchen' => 'resources/js/kitchen.js',
'manager' => 'resources/js/manager.js',
];

// Nếu không khớp module nào, dùng mặc định là admin
$viteEntry = $viteFiles[$prefix] ?? 'resources/js/app.js';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title inertia>{{ config('app.name', 'Laravel') }}</title>

  <link rel="stylesheet" href="{{ asset('templates/admin/vendor/chartist/css/chartist.min.css') }}">
  <link href="{{ asset('templates/admin/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
  <link href="{{ asset('templates/admin/vendor/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet">
  <link href="{{ asset('templates/admin/css/style.css') }}" rel="stylesheet">
  @vite([$viteEntry])
  @inertiaHead
</head>

<body>
  <div id="preloader">
    <div class="sk-three-bounce">
      <div class="sk-child sk-bounce1"></div>
      <div class="sk-child sk-bounce2"></div>
      <div class="sk-child sk-bounce3"></div>
    </div>
  </div>
  <div id="main-wrapper">
    @inertia
  </div>

  <script src="{{ asset('templates/admin/vendor/global/global.min.js') }}">
  </script>
  <script src="{{ asset('templates/admin/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>

  <!-- DatetimePicker -->
  <script src="{{ asset('templates/admin/vendor/bootstrap-datetimepicker/js/moment.js') }}"></script>
  <script src="{{ asset('templates/admin/vendor/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js') }}"></script>


  <!-- Chart piety plugin files -->
  <script src="{{ asset('templates/admin/vendor/peity/jquery.peity.min.js') }}"></script>

  <script src="{{ asset('templates/admin/js/custom.min.js') }}"></script>
  <script src="{{ asset('templates/admin/js/deznav-init.js') }}"></script>
</body>

</html>