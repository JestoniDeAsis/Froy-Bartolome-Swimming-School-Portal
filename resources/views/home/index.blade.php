<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FBSS Portal')</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/images/icon_fbss.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('assets/css/layout.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/our_theme.css') }}">
</head>
<body>

@include('includes.topbar')

<div class="app-body">

    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    @include('includes.sidebar')

    <main id="displayContainer" class="display-container">
        @include($pageView)
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/layout.js') }}"></script>
</body>
</html>