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
  <!--**********************************
        Main wrapper start
    ***********************************-->
  <div id="main-wrapper">

    <!--**********************************
        Nav header start
    ***********************************-->
    <div class="nav-header">
      <a href="index.html" class="brand-logo">
        <svg class="logo-abbr" width="48" height="36" viewBox="0 0 48 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path class="svg-logo-path" d="M18.281 14.25C18.281 13.2145 19.1204 12.375 20.156 12.375H35.3438C36.3794 12.375 37.2188 13.2145 37.2188 14.25C37.2188 15.2856 36.3794 16.125 35.3438 16.125H20.156C19.1204 16.125 18.281 15.2856 18.281 14.25ZM44.25 14.25C44.25 15.2839 45.0911 16.125 46.125 16.125C47.1606 16.125 48 16.9645 48 18V26.2461C48 27.2817 47.1606 28.1211 46.125 28.1211H32.2766L25.3258 35.072C24.5935 35.8043 23.4063 35.8041 22.6742 35.072L15.7234 28.1211H1.875C0.839437 28.1211 0 27.2817 0 26.2461V18C0 16.9645 0.839437 16.125 1.875 16.125C2.90887 16.125 3.75 15.2839 3.75 14.25C3.75 13.2162 2.90887 12.375 1.875 12.375C0.839437 12.375 0 11.5356 0 10.5V2.25397C0 1.2184 0.839437 0.378967 1.875 0.378967H46.125C47.1606 0.378967 48 1.2184 48 2.25397V10.5C48 11.5356 47.1606 12.375 46.125 12.375C45.0911 12.375 44.25 13.2162 44.25 14.25ZM11.2498 4.12897H3.75V8.94631C5.93259 9.72022 7.5 11.8055 7.5 14.25C7.5 16.6946 5.93259 18.7798 3.75 19.5537V24.3711H11.2498V4.12897ZM44.25 4.12897H14.9998V24.3711H16.5C16.9972 24.3711 17.4743 24.5686 17.8258 24.9202L24 31.0945L30.1742 24.9203C30.5257 24.5687 31.0028 24.3712 31.5 24.3712H44.25V19.5538C42.0674 18.7799 40.5 16.6947 40.5 14.2501C40.5 11.8056 42.0674 9.72031 44.25 8.9464V4.12897Z" fill="#2130B8" />
        </svg>
        <svg class="brand-title" width="87" height="28" viewBox="0 0 87 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path class="svg-logo-title" d="M0.0880001 7.11H6.412V27H11.172V7.11H17.496V3.268H0.0880001V7.11ZM20.969 27H25.729V8.164H20.969V27ZM23.383 5.92C25.049 5.92 26.307 4.696 26.307 3.132C26.307 1.568 25.049 0.343999 23.383 0.343999C21.683 0.343999 20.459 1.568 20.459 3.132C20.459 4.696 21.683 5.92 23.383 5.92ZM40.8359 27H46.2079L39.9519 17.548L46.1399 8.164H41.0399L37.5719 13.978L33.7299 8.164H28.3579L34.5799 17.548L28.4259 27H33.5259L36.9599 21.152L40.8359 27ZM48.7933 27H53.5533V8.164H48.7933V27ZM51.2073 5.92C52.8733 5.92 54.1313 4.696 54.1313 3.132C54.1313 1.568 52.8733 0.343999 51.2073 0.343999C49.5073 0.343999 48.2833 1.568 48.2833 3.132C48.2833 4.696 49.5073 5.92 51.2073 5.92ZM57.0322 17.514C57.0322 23.396 60.8402 27.306 65.6002 27.306C68.5922 27.306 70.7342 25.878 71.8562 24.246V27H76.6502V8.164H71.8562V10.85C70.7342 9.286 68.6602 7.858 65.6342 7.858C60.8402 7.858 57.0322 11.632 57.0322 17.514ZM71.8562 17.582C71.8562 21.152 69.4762 23.124 66.8582 23.124C64.3082 23.124 61.8942 21.084 61.8942 17.514C61.8942 13.944 64.3082 12.04 66.8582 12.04C69.4762 12.04 71.8562 14.012 71.8562 17.582ZM86.2971 24.45C86.2971 22.886 85.0731 21.662 83.4071 21.662C81.6731 21.662 80.4491 22.886 80.4491 24.45C80.4491 26.014 81.6731 27.238 83.4071 27.238C85.0731 27.238 86.2971 26.014 86.2971 24.45Z" fill="#2130B8" />
        </svg>

      </a>

      <div class="nav-control">
        <div class="hamburger">
          <span class="line"></span><span class="line"></span><span class="line"></span>
        </div>
      </div>
    </div>
    <!--**********************************
        Nav header end
    ***********************************-->



    <!--**********************************
        Header start
    ***********************************-->
    <div class="header">
      <div class="header-content">
        <nav class="navbar navbar-expand">
          <div class="collapse navbar-collapse justify-content-between">
            <div class="header-left">
              <div class="dashboard_bar">
                Events
              </div>
            </div>
            <ul class="navbar-nav header-right">
              <li class="nav-item dropdown header-profile">
                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                  <img src="@/assets/images/profile/pic1.jpg" width="20" alt="" />
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                  <a href="./app-profile.html" class="dropdown-item ai-icon">
                    <svg id="icon-user1" xmlns="http://www.w3.org/2000/svg" class="text-primary" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="ml-2">Profile </span>
                  </a>
                  <a href="./email-inbox.html" class="dropdown-item ai-icon">
                    <svg id="icon-inbox" xmlns="http://www.w3.org/2000/svg" class="text-success" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                      <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span class="ml-2">Inbox </span>
                  </a>
                  <a href="./page-login.html" class="dropdown-item ai-icon">
                    <svg id="icon-logout" xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                      <polyline points="16 17 21 12 16 7"></polyline>
                      <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="ml-2">Logout </span>
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </nav>
      </div>
    </div>
    <!--**********************************
        Header end ti-comment-alt
    ***********************************-->

    <!--**********************************
        Sidebar start
    ***********************************-->
    <div class="deznav">
      <div class="deznav-scroll">
        <ul class="metismenu" id="menu">
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-networking"></i>
              <span class="nav-text">Dashboard</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="index.html">Dashboard</a></li>
              <li><a href="analytics.html">Analytics</a></li>
              <li><a href="events.html">Events</a></li>
              <li><a href="order-list.html">Order List</a></li>
              <li><a href="customer-list.html">Customer List</a></li>
              <li><a href="reviews.html">Reviews</a></li>
            </ul>
          </li>
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-television"></i>
              <span class="nav-text">Apps</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="./app-profile.html">Profile</a></li>
              <li><a class="has-arrow" href="javascript:void()" aria-expanded="false">Email</a>
                <ul aria-expanded="false">
                  <li><a href="./email-compose.html">Compose</a></li>
                  <li><a href="./email-inbox.html">Inbox</a></li>
                  <li><a href="./email-read.html">Read</a></li>
                </ul>
              </li>
              <li><a href="./app-calender.html">Calendar</a></li>
              <li><a class="has-arrow" href="javascript:void()" aria-expanded="false">Shop</a>
                <ul aria-expanded="false">
                  <li><a href="./ecom-product-grid.html">Product Grid</a></li>
                  <li><a href="./ecom-product-list.html">Product List</a></li>
                  <li><a href="./ecom-product-detail.html">Product Details</a></li>
                  <li><a href="./ecom-product-order.html">Order</a></li>
                  <li><a href="./ecom-checkout.html">Checkout</a></li>
                  <li><a href="./ecom-invoice.html">Invoice</a></li>
                  <li><a href="./ecom-customers.html">Customers</a></li>
                </ul>
              </li>
            </ul>
          </li>
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-controls-3"></i>
              <span class="nav-text">Charts</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="./chart-flot.html">Flot</a></li>
              <li><a href="./chart-morris.html">Morris</a></li>
              <li><a href="./chart-chartjs.html">Chartjs</a></li>
              <li><a href="./chart-chartist.html">Chartist</a></li>
              <li><a href="./chart-sparkline.html">Sparkline</a></li>
              <li><a href="./chart-peity.html">Peity</a></li>
            </ul>
          </li>
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-internet"></i>
              <span class="nav-text">Bootstrap</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="./ui-accordion.html">Accordion</a></li>
              <li><a href="./ui-alert.html">Alert</a></li>
              <li><a href="./ui-badge.html">Badge</a></li>
              <li><a href="./ui-button.html">Button</a></li>
              <li><a href="./ui-modal.html">Modal</a></li>
              <li><a href="./ui-button-group.html">Button Group</a></li>
              <li><a href="./ui-list-group.html">List Group</a></li>
              <li><a href="./ui-media-object.html">Media Object</a></li>
              <li><a href="./ui-card.html">Cards</a></li>
              <li><a href="./ui-carousel.html">Carousel</a></li>
              <li><a href="./ui-dropdown.html">Dropdown</a></li>
              <li><a href="./ui-popover.html">Popover</a></li>
              <li><a href="./ui-progressbar.html">Progressbar</a></li>
              <li><a href="./ui-tab.html">Tab</a></li>
              <li><a href="./ui-typography.html">Typography</a></li>
              <li><a href="./ui-pagination.html">Pagination</a></li>
              <li><a href="./ui-grid.html">Grid</a></li>

            </ul>
          </li>
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-heart"></i>
              <span class="nav-text">Plugins</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="./uc-select2.html">Select 2</a></li>
              <li><a href="./uc-nestable.html">Nestedable</a></li>
              <li><a href="./uc-noui-slider.html">Noui Slider</a></li>
              <li><a href="./uc-sweetalert.html">Sweet Alert</a></li>
              <li><a href="./uc-toastr.html">Toastr</a></li>
              <li><a href="./map-jqvmap.html">Jqv Map</a></li>
              <li><a href="./uc-lightgallery.html">Light Gallery</a></li>
            </ul>
          </li>
          <li><a href="widget-basic.html" class="ai-icon" aria-expanded="false">
              <i class="flaticon-381-settings-2"></i>
              <span class="nav-text">Widget</span>
            </a>
          </li>
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-notepad"></i>
              <span class="nav-text">Forms</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="./form-element.html">Form Elements</a></li>
              <li><a href="./form-wizard.html">Wizard</a></li>
              <li><a href="./form-editor-summernote.html">Summernote</a></li>
              <li><a href="form-pickers.html">Pickers</a></li>
              <li><a href="form-validation-jquery.html">Jquery Validate</a></li>
            </ul>
          </li>
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-network"></i>
              <span class="nav-text">Table</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="table-bootstrap-basic.html">Bootstrap</a></li>
              <li><a href="table-datatable-basic.html">Datatable</a></li>
            </ul>
          </li>
          <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
              <i class="flaticon-381-layer-1"></i>
              <span class="nav-text">Pages</span>
            </a>
            <ul aria-expanded="false">
              <li><a href="./page-register.html">Register</a></li>
              <li><a href="./page-login.html">Login</a></li>
              <li><a class="has-arrow" href="javascript:void()" aria-expanded="false">Error</a>
                <ul aria-expanded="false">
                  <li><a href="./page-error-400.html">Error 400</a></li>
                  <li><a href="./page-error-403.html">Error 403</a></li>
                  <li><a href="./page-error-404.html">Error 404</a></li>
                  <li><a href="./page-error-500.html">Error 500</a></li>
                  <li><a href="./page-error-503.html">Error 503</a></li>
                </ul>
              </li>
              <li><a href="./page-lock-screen.html">Lock Screen</a></li>
            </ul>
          </li>
        </ul>
        <div class="copyright">
          <p>Tixia Ticketing Admin Dashboard <br />© 2021 All Rights Reserved</p>
        </div>
      </div>
    </div>
    <!--**********************************
        Sidebar end
    ***********************************-->


    <!--**********************************
            Content body start
        ***********************************-->
    <div class="content-body">
      @inertia
    </div>
    <!--**********************************
            Content body end
        ***********************************-->

    <!--**********************************
            Footer start
        ***********************************-->
    <div class="footer">
      <div class="copyright">
        <p>Copyright © Designed &amp; Developed by <a href="http://dexignzone.com/" target="_blank">DexignZone</a> 2021</p>
      </div>
    </div>
    <!--**********************************
            Footer end
        ***********************************-->

    <!--**********************************
           Support ticket button start
        ***********************************-->

    <!--**********************************
           Support ticket button end
        ***********************************-->


  </div>
  <!--**********************************
        Main wrapper end
    ***********************************-->

  <script src="{{ asset('templates/admin/vendor/global/global.min.js') }}"></script>
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