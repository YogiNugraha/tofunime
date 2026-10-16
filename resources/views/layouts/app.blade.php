<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">

    <title>{{ $title ?? 'Tofunime - Nonton & Download Anime Sub Indo Kualitas HD' }}</title>
    <meta name="description" content="{{ $description ?? 'Platform streaming dan download anime Subtitle Indonesia terlengkap dengan antarmuka elegan Millishion Light Mode, bebas iklan mengganggu.' }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-tofunime.png') }}">

    <!-- Google Fonts: Cinzel (Serif) & Plus Jakarta Sans (Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-full flex flex-col bg-parchment text-ink font-sans antialiased selection:bg-mana/20 selection:text-ink">
    <x-navbar />

    <main class="flex-1">
        @if (isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    <x-footer />

    @livewireScripts
    @stack('scripts')
</body>
</html>
