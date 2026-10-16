<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">

    <title>{{ $title ?? 'CMS Admin Studio - Tofunime' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-tofunime.png') }}">

    <!-- Google Fonts: Cinzel & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-parchment text-ink font-sans antialiased selection:bg-mana/20 selection:text-ink">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        <!-- Mobile Sidebar Backdrop -->
        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-ink/30 backdrop-blur-xs md:hidden"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <!-- Left Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-surface border-r border-stone-warm flex flex-col transition-transform duration-200 ease-in-out md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <!-- Brand Header -->
            <div class="h-20 flex items-center justify-between px-6 border-b border-stone-warm bg-surface shrink-0">
                <a href="{{ url('/admin/dashboard') }}" class="flex items-center space-x-2.5 transition-transform hover:scale-[1.02] active:scale-95 duration-150">
                    <img
                        src="{{ asset('assets/logo-tofunime.png') }}"
                        alt="TOFUNIME"
                        class="h-9 w-auto object-contain"
                    />
                    <span class="text-sm font-sans font-medium text-mana uppercase tracking-wider bg-alabaster border border-stone-warm px-2 py-0.5 rounded-md">
                        CMS
                    </span>
                </a>

                <!-- Mobile Close Button -->
                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="md:hidden p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-alabaster transition-colors"
                    aria-label="Tutup Menu"
                >
                    <x-lucide-x class="w-5 h-5 shrink-0" />
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <div class="px-3 pb-2 text-sm font-semibold text-ink-muted uppercase tracking-wider">
                    Menu Utama
                </div>

                <!-- Dashboard -->
                <a
                    href="{{ url('/admin/dashboard') }}"
                    class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm leading-5 font-medium transition-all {{ request()->is('admin/dashboard') || request()->is('admin') ? 'bg-mana text-white font-semibold shadow-xs' : 'text-ink-muted hover:text-ink hover:bg-alabaster' }}"
                >
                    <x-lucide-layout-dashboard class="w-5 h-5 shrink-0" />
                    <span>Dashboard</span>
                </a>

                <!-- Hybrid Grabber -->
                <a
                    href="{{ url('/admin/scraper') }}"
                    class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm leading-5 font-medium transition-all {{ request()->is('admin/scraper*') ? 'bg-mana text-white font-semibold shadow-xs' : 'text-ink-muted hover:text-ink hover:bg-alabaster' }}"
                >
                    <x-lucide-cloud-download class="w-5 h-5 shrink-0" />
                    <span>Hybrid Grabber</span>
                </a>

                <!-- Katalog Anime -->
                <a
                    href="{{ url('/admin/animes') }}"
                    class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm leading-5 font-medium transition-all {{ request()->is('admin/animes*') || request()->is('admin/episodes*') ? 'bg-mana text-white font-semibold shadow-xs' : 'text-ink-muted hover:text-ink hover:bg-alabaster' }}"
                >
                    <x-lucide-film class="w-5 h-5 shrink-0" />
                    <span>Katalog Anime</span>
                </a>

                <!-- Laporan Rusak -->
                <a
                    href="{{ url('/admin/reports') }}"
                    class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm leading-5 font-medium transition-all {{ request()->is('admin/reports*') ? 'bg-mana text-white font-semibold shadow-xs' : 'text-ink-muted hover:text-ink hover:bg-alabaster' }}"
                >
                    <x-lucide-alert-triangle class="w-5 h-5 shrink-0" />
                    <span>Laporan Rusak</span>
                </a>

                <!-- Pengaturan -->
                <a
                    href="{{ url('/admin/settings') }}"
                    class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm leading-5 font-medium transition-all {{ request()->is('admin/settings*') ? 'bg-mana text-white font-semibold shadow-xs' : 'text-ink-muted hover:text-ink hover:bg-alabaster' }}"
                >
                    <x-lucide-settings class="w-5 h-5 shrink-0" />
                    <span>Pengaturan</span>
                </a>

                <div class="pt-6 px-3 pb-2 text-sm font-semibold text-ink-muted uppercase tracking-wider">
                    Pintasan
                </div>

                <!-- Buka Website Publik -->
                <a
                    href="{{ url('/') }}"
                    target="_blank"
                    class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm leading-5 font-medium text-ink-muted hover:text-mana hover:bg-alabaster transition-all"
                >
                    <x-lucide-external-link class="w-5 h-5 shrink-0" />
                    <span>Buka Website</span>
                    <x-lucide-arrow-up-right class="w-4 h-4 shrink-0 ml-auto text-ink-muted" />
                </a>
            </nav>

            <!-- Sidebar User & Logout Footer -->
            <div class="p-4 border-t border-stone-warm bg-alabaster/60 shrink-0">
                @auth
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-mana text-white font-serif font-semibold text-sm flex items-center justify-center shrink-0 shadow-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 truncate">
                                <p class="text-sm font-semibold text-ink truncate leading-tight">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-ink-muted truncate">{{ auth()->user()->email }}</p>
                            </div>
                        </div>


                        <form action="{{ route('logout') }}" method="POST" class="inline shrink-0">
                            @csrf
                            <button
                                type="submit"
                                title="Keluar dari Admin Studio"
                                class="p-2 text-ink-muted hover:text-red-600 hover:bg-stone-warm/50 rounded-lg transition-colors"
                            >
                                <x-lucide-log-out class="w-5 h-5 shrink-0" />
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </aside>

        <!-- Right Main Content Area -->
        <div class="flex-1 flex flex-col md:pl-64 min-w-0">
            <!-- Top Mobile & Breadcrumb Header -->
            <header class="sticky top-0 z-30 h-16 bg-surface/90 backdrop-blur-md border-b border-stone-warm flex items-center justify-between px-6 lg:px-8 shrink-0">
                <div class="flex items-center space-x-4">
                    <!-- Mobile Sidebar Toggle -->
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="md:hidden p-2 rounded-lg text-ink-muted hover:text-ink hover:bg-alabaster transition-colors"
                        aria-label="Buka Menu Sidebar"
                    >
                        <x-lucide-menu class="w-6 h-6 shrink-0" />
                    </button>

                    <!-- Breadcrumb Info -->
                    <div class="flex items-center space-x-2 text-sm text-ink-muted">
                        <span class="font-medium text-ink">Admin Studio</span>
                        <span class="text-stone-warm">/</span>
                        <span class="text-mana font-semibold capitalize">
                            @if(request()->is('admin/dashboard') || request()->is('admin'))
                                Dashboard
                            @elseif(request()->is('admin/scraper*'))
                                Hybrid Grabber
                            @elseif(request()->is('admin/animes*'))
                                Katalog Anime
                            @elseif(request()->is('admin/episodes*'))
                                Editor Episode
                            @elseif(request()->is('admin/reports*'))
                                Laporan Rusak
                            @elseif(request()->is('admin/settings*'))
                                Pengaturan
                            @else
                                {{ request()->segment(2) ?? 'Dashboard' }}
                            @endif
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <a
                        href="{{ url('/') }}"
                        target="_blank"
                        class="hidden sm:inline-flex items-center space-x-1.5 text-sm font-medium text-ink-muted hover:text-mana bg-alabaster hover:bg-stone-warm/50 border border-stone-warm px-3.5 py-1.5 rounded-full transition-colors"
                    >
                        <span>Lihat Website</span>
                        <x-lucide-external-link class="w-4 h-4 shrink-0" />
                    </a>
                </div>
            </header>

            <!-- Main Page Content -->
            <main class="flex-1 py-8 px-6 lg:px-8 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
