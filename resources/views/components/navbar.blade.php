<header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-stone-warm">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Brand Logo -->
            <div class="flex items-center">
                <a href="{{ url('/') }}" class="inline-flex items-center transition-transform hover:scale-[1.02] active:scale-95 duration-150">
                    <img
                        src="{{ asset('assets/logo-tofunime.png') }}"
                        alt="TOFUNIME"
                        class="h-11 w-auto object-contain"
                    />
                </a>
            </div>

            <!-- Desktop Navigation Menu (No icons per Anti-AI Slop Rule 5) -->
            <nav class="hidden md:flex items-center space-x-8">
                <a href="{{ url('/') }}" class="text-sm font-medium {{ request()->is('/') ? 'text-mana font-semibold' : 'text-ink-muted hover:text-mana' }} transition-colors">
                    Beranda
                </a>
                <a href="{{ url('/katalog') }}" class="text-sm font-medium {{ request()->is('katalog*') || request()->is('anime*') ? 'text-mana font-semibold' : 'text-ink-muted hover:text-mana' }} transition-colors">
                    Katalog Anime
                </a>
                <a href="{{ url('/ongoing') }}" class="text-sm font-medium {{ request()->is('ongoing*') ? 'text-mana font-semibold' : 'text-ink-muted hover:text-mana' }} transition-colors">
                    Ongoing
                </a>
                <a href="{{ url('/jadwal-rilis') }}" class="text-sm font-medium {{ request()->is('jadwal*') ? 'text-mana font-semibold' : 'text-ink-muted hover:text-mana' }} transition-colors">
                    Jadwal Rilis
                </a>
                <a href="{{ url('/genre') }}" class="text-sm font-medium {{ request()->is('genre*') || request()->is('tag*') ? 'text-mana font-semibold' : 'text-ink-muted hover:text-mana' }} transition-colors">
                    Genre & Tag
                </a>
            </nav>

            <!-- Right Controls: Quick Search & Masuk Button -->
            <div class="flex items-center space-x-4">
                <!-- Livewire Global Search Trigger & Modal -->
                <livewire:public.global-search />

                <!-- Masuk / Admin Studio Button -->
                @auth
                    <a
                        href="{{ url('/admin/dashboard') }}"
                        class="hidden sm:inline-flex items-center justify-center text-sm font-semibold text-white bg-mana hover:bg-mana-hover px-5 py-2.5 rounded-full transition-colors"
                    >
                        Admin Studio
                    </a>
                @else
                    <a
                        href="{{ url('/login') }}"
                        class="hidden sm:inline-flex items-center justify-center text-sm font-semibold text-white bg-mana hover:bg-mana-hover px-5 py-2.5 rounded-full transition-colors"
                    >
                        Masuk
                    </a>
                @endauth

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    @click="mobileOpen = !mobileOpen"
                    class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-ink-muted hover:text-ink hover:bg-alabaster transition-colors"
                    aria-label="Buka menu navigasi"
                >
                    <x-lucide-menu x-show="!mobileOpen" class="w-6 h-6 shrink-0" />
                    <x-lucide-x x-show="mobileOpen" x-cloak class="w-6 h-6 shrink-0" />
                </button>
            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div
            x-show="mobileOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden py-4 border-t border-stone-warm bg-surface"
        >
            <nav class="flex flex-col space-y-2">
                <a href="{{ url('/') }}" class="px-4 py-2 text-sm font-medium {{ request()->is('/') ? 'text-mana font-semibold bg-alabaster' : 'text-ink-muted hover:text-mana hover:bg-alabaster' }} rounded-md transition-colors">
                    Beranda
                </a>
                <a href="{{ url('/katalog') }}" class="px-4 py-2 text-sm font-medium {{ request()->is('katalog*') || request()->is('anime*') ? 'text-mana font-semibold bg-alabaster' : 'text-ink-muted hover:text-mana hover:bg-alabaster' }} rounded-md transition-colors">
                    Katalog Anime
                </a>
                <a href="{{ url('/ongoing') }}" class="px-4 py-2 text-sm font-medium {{ request()->is('ongoing*') ? 'text-mana font-semibold bg-alabaster' : 'text-ink-muted hover:text-mana hover:bg-alabaster' }} rounded-md transition-colors">
                    Ongoing
                </a>
                <a href="{{ url('/jadwal-rilis') }}" class="px-4 py-2 text-sm font-medium {{ request()->is('jadwal*') ? 'text-mana font-semibold bg-alabaster' : 'text-ink-muted hover:text-mana hover:bg-alabaster' }} rounded-md transition-colors">
                    Jadwal Rilis
                </a>
                <a href="{{ url('/genre') }}" class="px-4 py-2 text-sm font-medium {{ request()->is('genre*') || request()->is('tag*') ? 'text-mana font-semibold bg-alabaster' : 'text-ink-muted hover:text-mana hover:bg-alabaster' }} rounded-md transition-colors">
                    Genre & Tag
                </a>
                <div class="pt-3 border-t border-stone-warm px-4">
                    @auth
                        <a href="{{ url('/admin/dashboard') }}" class="block text-center text-sm font-semibold text-white bg-mana hover:bg-mana-hover px-5 py-2.5 rounded-full transition-colors">
                            Admin Studio
                        </a>
                    @else
                        <a href="{{ url('/login') }}" class="block text-center text-sm font-semibold text-white bg-mana hover:bg-mana-hover px-5 py-2.5 rounded-full transition-colors">
                            Masuk ke Akun
                        </a>
                    @endauth
                </div>
            </nav>
        </div>
    </div>
</header>
