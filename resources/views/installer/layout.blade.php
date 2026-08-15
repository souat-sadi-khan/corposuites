<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="{{ asset('assets/system/images/favicon.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title : 'Installer' }} - Admin Project</title>

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/system/css/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/system/fonts/remixicon.css') }}">

    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('assets/system/fonts/fonts.css') }}">

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('assets/system/css/auth.css') }}">
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
    <script src="{{ asset('assets/system/js/auth.js') }}"></script>

    @stack('scripts')
</body>
</html>
