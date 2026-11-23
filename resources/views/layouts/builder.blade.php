<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Visual Studio Builder' }} - Inamikro</title>

    <!-- Google Fonts Bunny CDN -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:400,500,600,700|roboto:400,500,700|plus-jakarta-sans:400,500,600,700|montserrat:400,600,700|playfair-display:400,600,700|outfit:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-900 text-slate-100 antialiased font-sans flex flex-col overflow-hidden">
    {{ $slot ?? '' }}
    @yield('content')

    @livewireScripts
</body>
</html>
