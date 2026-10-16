<div class="space-y-14 lg:space-y-16">
    <!-- 1. Hero Showcase (Diagonal Split / Millishion Light Mode) -->
    @if ($featuredAnime)
        <section class="relative bg-parchment border-b border-stone-warm overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12 lg:py-16">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    <!-- Left Hero Details -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="flex items-center space-x-3 text-sm font-medium text-gold">
                            <span>{{ $featuredAnime->season?->name ?? 'Musim Pilihan' }}</span>
                            <span>&middot;</span>
                            <span>{{ $featuredAnime->studio?->name ?? 'Studio Pilihan' }}</span>
                        </div>

                        <h1 class="text-3xl lg:text-4xl font-serif font-semibold text-ink tracking-tight leading-tight">
                            {{ $featuredAnime->title }}
                        </h1>

                        <!-- Genre & Tag Pills (Anti-AI Slop: Warna Seragam Alabaster) -->
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($featuredAnime->genres->take(3) as $genre)
                                <a href="{{ url('/genre/' . $genre->slug) }}" class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3.5 py-1 rounded-full text-sm font-medium transition-colors">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                            @foreach ($featuredAnime->themes->take(1) as $theme)
                                <a href="{{ url('/tag/' . $theme->slug) }}" class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3.5 py-1 rounded-full text-sm font-medium transition-colors">
                                    {{ $theme->name }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Sinopsis -->
                        <p class="text-base text-ink-muted leading-relaxed line-clamp-3 max-w-2xl">
                            {{ $featuredAnime->synopsis ?? 'Ikuti alur cerita anime berkualitas tinggi dengan takarir bahasa Indonesia terbaik.' }}
                        </p>

                        <!-- CTA & Skor -->
                        <div class="pt-2 flex flex-wrap items-center gap-4">
                            <a
                                href="{{ url('/anime/' . $featuredAnime->slug) }}"
                                class="inline-flex items-center space-x-2.5 bg-mana hover:bg-mana-hover text-white px-7 py-3 rounded-full text-sm font-semibold transition-colors"
                            >
                                <x-lucide-play class="w-5 h-5 shrink-0 fill-current" />
                                <span>Tonton Sekarang</span>
                            </a>

                            <div class="flex items-center space-x-2 text-sm text-ink-muted font-medium bg-surface border border-stone-warm px-4 py-2.5 rounded-full">
                                <x-lucide-star class="w-5 h-5 shrink-0 text-gold fill-current" />
                                <span class="text-ink font-semibold">{{ number_format((float) $featuredAnime->rating_score, 2) }}</span>
                                <span>skor resmi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Artwork Banner -->
                    <div class="lg:col-span-5 flex justify-center lg:justify-end">
                        <div class="w-full max-w-sm rounded-lg overflow-hidden border border-stone-warm bg-surface p-2 shadow-xs">
                            <img
                                src="{{ $featuredAnime->poster_url }}"
                                alt="{{ $featuredAnime->title }}"
                                class="w-full aspect-[2/3] object-cover rounded-md"
                                loading="eager"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Ad Slot: home_below_hero -->
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <x-ad-slot key="home_below_hero" />
    </div>

    <!-- 2. Section "Update Episode Terbaru" (Grid 4 Kolom Besar) -->
    <section class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-stone-warm mb-8">
            <div>
                <h2 class="text-2xl font-serif font-semibold text-ink">Update Episode Terbaru</h2>
                <p class="text-sm text-ink-muted mt-1">Rilis episode mingguan Subtitle Indonesia dengan server multi-resolusi.</p>
            </div>

            <!-- Reactive Filter Pills -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-1 sm:pb-0">
                <button
                    wire:click="setFilter('all')"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $typeFilter === 'all' ? 'bg-mana text-white' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}"
                >
                    Semua
                </button>
                <button
                    wire:click="setFilter('TV')"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $typeFilter === 'TV' ? 'bg-mana text-white' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}"
                >
                    TV Series
                </button>
                <button
                    wire:click="setFilter('Movie')"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $typeFilter === 'Movie' ? 'bg-mana text-white' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}"
                >
                    Movie
                </button>
                <button
                    wire:click="setFilter('OVA')"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $typeFilter === 'OVA' ? 'bg-mana text-white' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}"
                >
                    OVA
                </button>
            </div>
        </div>

        <!-- Grid 4 Kolom Besar (Aspect 2/3, 1 Clean Badge) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 lg:gap-8">
            @forelse ($recentUpdates as $episode)
                <article class="group flex flex-col space-y-3">
                    <a href="{{ url('/nonton/' . $episode->slug) }}" class="relative aspect-[2/3] rounded-lg overflow-hidden border border-stone-warm bg-surface block">
                        <img
                            src="{{ $episode->anime->poster_url }}"
                            alt="{{ $episode->anime->title }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                        <!-- Maksimal 1 Indikator Bersih di Sudut Kanan Atas -->
                        <div class="absolute top-2.5 right-2.5 bg-surface/95 text-ink border border-stone-warm text-sm font-medium px-2.5 py-0.5 rounded shadow-xs">
                            Ep {{ $episode->episode_number }}
                        </div>
                    </a>

                    <div class="space-y-1.5">
                        <h3 class="text-base font-sans font-semibold text-ink line-clamp-1 group-hover:text-mana transition-colors">
                            <a href="{{ url('/nonton/' . $episode->slug) }}">
                                {{ $episode->anime->title }}
                            </a>
                        </h3>

                        <div class="flex items-center justify-between text-sm text-ink-muted">
                            <span>{{ $episode->anime->release_year ?? date('Y') }}</span>
                            <span class="font-medium text-ink flex items-center space-x-1">
                                <x-lucide-star class="w-5 h-5 shrink-0 text-gold fill-current" />
                                <span>{{ number_format((float) $episode->anime->rating_score, 1) }}</span>
                            </span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-12 text-center text-sm text-ink-muted bg-surface rounded-lg border border-stone-warm">
                    Belum ada episode anime yang sesuai dengan filter ini.
                </div>
            @endforelse
        </div>
    </section>

    <!-- 3. Section "Studio & Direktori Pilihan" -->
    <section class="max-w-7xl mx-auto px-6 lg:px-8 space-y-10">
        <div class="pb-6 border-b border-stone-warm">
            <h2 class="text-2xl font-serif font-semibold text-ink">Studio & Direktori Pilihan</h2>
            <p class="text-sm text-ink-muted mt-1">Koleksi anime terbaik berdasarkan rumah produksi terkemuka industri Jepang.</p>
        </div>

        <!-- 1 Baris Grid 6 Kolom Kompak -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
            @foreach ($compactAnimesRow1 as $anime)
                <article class="group flex flex-col space-y-2">
                    <a href="{{ url('/anime/' . $anime->slug) }}" class="relative aspect-[2/3] rounded-lg overflow-hidden border border-stone-warm bg-surface block">
                        <img
                            src="{{ $anime->poster_url }}"
                            alt="{{ $anime->title }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                    </a>
                    <h3 class="text-sm font-sans font-semibold text-ink line-clamp-1 group-hover:text-mana transition-colors">
                        <a href="{{ url('/anime/' . $anime->slug) }}">{{ $anime->title }}</a>
                    </h3>
                    <div class="flex items-center justify-between text-sm text-ink-muted">
                        <span>{{ $anime->release_year ?? '2026' }}</span>
                        <span class="font-medium text-ink flex items-center space-x-1">
                            <x-lucide-star class="w-5 h-5 shrink-0 text-gold fill-current" />
                            <span>{{ number_format((float) $anime->rating_score, 1) }}</span>
                        </span>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- 4 Kartu Banner Studio Ikonik -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach ($iconicStudios as $studio)
                <a
                    href="{{ url('/studio/' . $studio->slug) }}"
                    class="bg-surface border border-stone-warm py-8 rounded-lg text-center hover:border-mana transition-colors block group"
                >
                    <span class="font-serif font-semibold text-lg text-ink group-hover:text-mana transition-colors block">
                        {{ $studio->name }}
                    </span>
                    <span class="text-sm text-ink-muted mt-1 block">
                        Lihat Karya Studio &rarr;
                    </span>
                </a>
            @endforeach
        </div>

        <!-- 1 Baris Grid 6 Kolom Kompak Lanjutan -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
            @foreach ($compactAnimesRow2 as $anime)
                <article class="group flex flex-col space-y-2">
                    <a href="{{ url('/anime/' . $anime->slug) }}" class="relative aspect-[2/3] rounded-lg overflow-hidden border border-stone-warm bg-surface block">
                        <img
                            src="{{ $anime->poster_url }}"
                            alt="{{ $anime->title }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                    </a>
                    <h3 class="text-sm font-sans font-semibold text-ink line-clamp-1 group-hover:text-mana transition-colors">
                        <a href="{{ url('/anime/' . $anime->slug) }}">{{ $anime->title }}</a>
                    </h3>
                    <div class="flex items-center justify-between text-sm text-ink-muted">
                        <span>{{ $anime->release_year ?? '2026' }}</span>
                        <span class="font-medium text-ink flex items-center space-x-1">
                            <x-lucide-star class="w-5 h-5 shrink-0 text-gold fill-current" />
                            <span>{{ number_format((float) $anime->rating_score, 1) }}</span>
                        </span>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pt-6 border-t border-stone-warm flex items-center justify-center">
            <a href="{{ url('/anime') }}" class="inline-flex items-center space-x-2 bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-6 py-2.5 rounded-full text-sm font-semibold transition-colors">
                <span>Jelajahi Seluruh Katalog Anime</span>
                <span>&rarr;</span>
            </a>
        </div>
    </section>
</div>
