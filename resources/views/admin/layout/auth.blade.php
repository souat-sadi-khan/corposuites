<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="{{ get_settings('system_favicon') ? asset(get_settings('system_favicon')) : asset('assets/system/images/favicon.png') }}">
    <meta name="system-logo" content="{{ get_settings('system_logo') ? asset(get_settings('system_logo')) : asset('assets/system/images/logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title : 'Installer' }} - {{ get_settings('system_name') ?? 'Admin Template' }}</title>

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/system/css/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/system/fonts/remixicon.css') }}">

    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('assets/system/fonts/fonts.css') }}">

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('assets/system/css/auth.css') }}">

    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('nx-theme') || 'light');
    </script>

</head>

<body>

    <!-- ── Background layer ── -->
    <div class="page-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    @yield('content')

    <script src="{{ asset('assets/system/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/alert.js') }}"></script>
	<script src="{{ asset('assets/system/js/main.js') }}"></script>

    @stack('scripts')
</body>
</html>
