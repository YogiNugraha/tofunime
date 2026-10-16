<div class="space-y-14 lg:space-y-16">
    <!-- 1. Hero Showcase (Multi-Featured Split Hero Carousel / Millishion Light Mode) -->
    @if ($featuredAnimes->isNotEmpty())
        <section
            x-data="{
                current: 0,
                total: {{ $featuredAnimes->count() }},
                progress: 0,
                duration: 6000,
                interval: null,
                paused: false,
                init() {
                    if (this.total <= 1) return;
                    this.startTimer();
                },
                startTimer() {
                    clearInterval(this.interval);
                    this.progress = 0;
                    const step = 50;
                    this.interval = setInterval(() => {
                        if (!this.paused) {
                            this.progress += (step / this.duration) * 100;
                            if (this.progress >= 100) {
                                this.next();
                            }
                        }
                    }, step);
                },
                next() {
                    this.current = (this.current + 1) % this.total;
                    this.progress = 0;
                    this.paused = false;
                },
                prev() {
                    this.current = (this.current - 1 + this.total) % this.total;
                    this.progress = 0;
                    this.paused = false;
                },
                goTo(index) {
                    this.current = index;
                    this.progress = 0;
                    this.paused = false;
                }
            }"
            class="relative bg-parchment overflow-hidden min-h-[520px] lg:min-h-[580px] flex items-center"
        >
            <!-- Split Hero Background: 100% Solid Parchment (Left) & Feathered Blended Artwork (Right) -->
            <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden select-none" aria-hidden="true">
                @foreach ($featuredAnimes as $index => $anime)
                    <div
                        x-show="current === {{ $index }}"
                        x-transition:enter="transition-opacity duration-700 ease-out"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition-opacity duration-500 ease-in"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @if ($index > 0) x-cloak @endif
                        class="absolute inset-y-0 right-0 w-full lg:w-[52%] xl:w-[50%]"
                        style="-webkit-mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,0.5) 50%, black 100%);
                               mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,0.5) 50%, black 100%);"
                    >
                        <img
                            src="{{ $anime->banner_url ?: $anime->poster_url }}"
                            alt=""
                            class="w-full h-full object-cover"
                            style="object-position: center 36%;"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            fetchpriority="{{ $index === 0 ? 'high' : 'low' }}"
                        />
                    </div>
                @endforeach
            </div>

            <!-- Foreground Content: Positioned on the Solid Left Side -->
            <div class="relative z-10 w-full max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
                <div class="max-w-xl lg:max-w-2xl space-y-6">
                    <!-- Carousel Slide Stack: CSS Grid col-start-1 row-start-1 untuk transisi in-place tanpa jeglug -->
                    <div class="grid grid-cols-1 grid-rows-1 items-start min-h-[380px] lg:min-h-[360px]">
                        @foreach ($featuredAnimes as $index => $anime)
                            <div
                                x-show="current === {{ $index }}"
                                x-transition:enter="transition-opacity duration-400 ease-out"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition-opacity duration-250 ease-in"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @if ($index > 0) x-cloak @endif
                                class="col-start-1 row-start-1 space-y-6 w-full"
                                :class="current === {{ $index }} ? 'relative z-10' : 'pointer-events-none select-none z-0'"
                            >
                                <!-- Eyebrow: Season, Studio, Type & Status (Warna Seragam Millishion: Gold & Ink, Bebas Warna Pelangi) -->
                                <div class="flex flex-wrap items-center gap-3 text-sm font-medium text-gold">
                                    <span>{{ $anime->season?->name ?? ($anime->release_year ?? 'Musim Pilihan') }}</span>
                                    <span>&middot;</span>
                                    @if ($anime->studio)
                                        <a href="{{ url('/studio/' . $anime->studio->slug) }}" class="text-ink hover:text-mana transition-colors font-medium">
                                            {{ $anime->studio->name }}
                                        </a>
                                    @else
                                        <span class="text-ink font-medium">Studio Pilihan</span>
                                    @endif
                                    <span>&middot;</span>
                                    <span class="text-ink-muted">{{ $anime->type?->value ?? 'TV' }}</span>
                                    @if ($anime->status)
                                        <span>&middot;</span>
                                        <span class="text-ink-muted">{{ $anime->status->label() }}</span>
                                    @endif
                                </div>

                                <!-- Title & Alternative Title (Hover Pause) -->
                                <div
                                    @mouseenter="paused = true"
                                    @mouseleave="paused = false"
                                    class="inline-block"
                                >
                                    <a href="{{ url('/anime/' . $anime->slug) }}" class="group block">
                                        <h1 class="text-3xl lg:text-4xl font-serif font-semibold text-ink group-hover:text-mana transition-colors tracking-tight leading-tight">
                                            {{ $anime->title }}
                                        </h1>
                                    </a>
                                    @if ($anime->japanese_title || ($anime->english_title && $anime->english_title !== $anime->title))
                                        <p class="text-sm text-ink-muted font-sans mt-1.5">
                                            {{ $anime->japanese_title }}
                                            @if ($anime->japanese_title && $anime->english_title && $anime->english_title !== $anime->title)
                                                &middot;
                                            @endif
                                            @if ($anime->english_title !== $anime->title)
                                                {{ $anime->english_title }}
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                <!-- Quick Metadata Specs Bar (Strict text-sm, 1px border-stone-warm, warna seragam) -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-muted font-medium border-y border-stone-warm py-3">
                                    <div>
                                        <span class="text-ink font-medium">Total:</span>
                                        <span>{{ $anime->total_episodes ? $anime->total_episodes . ' Episode' : 'Ongoing' }}</span>
                                    </div>

                                    <span>&middot;</span>

                                    <div>
                                        <span class="text-ink font-medium">Durasi:</span>
                                        <span>{{ $anime->duration ?? '24 Menit' }}</span>
                                    </div>

                                    @if ($anime->release_day && $anime->status === \App\Enums\AnimeStatus::Ongoing)
                                        <span>&middot;</span>
                                        <div class="flex items-center space-x-1.5">
                                            <x-lucide-calendar class="w-4 h-4 text-mana shrink-0" />
                                            <span class="text-ink font-medium">Tiap {{ $anime->release_day->label() }}</span>
                                        </div>
                                    @endif

                                    @if ($anime->source_material)
                                        <span>&middot;</span>
                                        <div>
                                            <span class="text-ink-muted">Sumber:</span>
                                            <span class="text-ink font-medium">{{ $anime->source_material }}</span>
                                        </div>
                                    @endif

                                    @if ($anime->age_rating)
                                        <span>&middot;</span>
                                        <span class="bg-alabaster border border-stone-warm text-ink px-2 py-0.5 rounded text-sm font-medium">
                                            {{ $anime->age_rating }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Genre, Theme & Demographic Pills (Strict Rule 4: bg-alabaster, border-stone-warm, text-ink, text-sm) -->
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach ($anime->genres->take(3) as $genre)
                                        <a href="{{ url('/genre/' . $genre->slug) }}" class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3.5 py-1 rounded-full text-sm font-medium transition-colors">
                                            {{ $genre->name }}
                                        </a>
                                    @endforeach
                                    @foreach ($anime->themes->take(1) as $theme)
                                        <a href="{{ url('/tag/' . $theme->slug) }}" class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3.5 py-1 rounded-full text-sm font-medium transition-colors">
                                            #{{ $theme->name }}
                                        </a>
                                    @endforeach
                                    @foreach ($anime->demographics->take(1) as $demographic)
                                        <a href="{{ url('/demographic/' . $demographic->slug) }}" class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3.5 py-1 rounded-full text-sm font-medium transition-colors">
                                            {{ $demographic->name }}
                                        </a>
                                    @endforeach
                                </div>

                                <!-- Sinopsis -->
                                <p class="text-base text-ink-muted leading-relaxed line-clamp-3 max-w-2xl font-normal">
                                    {{ $anime->synopsis ?? 'Ikuti alur cerita anime berkualitas tinggi dengan takarir bahasa Indonesia terbaik.' }}
                                </p>

                                <!-- CTA & Skor Row -->
                                <div class="pt-2 flex flex-wrap items-center gap-4">
                                    <!-- Tonton Sekarang Button (Hover Pause) -->
                                    <a
                                        href="{{ url('/anime/' . $anime->slug) }}"
                                        @mouseenter="paused = true"
                                        @mouseleave="paused = false"
                                        class="inline-flex items-center space-x-2.5 bg-mana hover:bg-mana-hover text-white px-7 py-3 rounded-full text-sm font-semibold transition-colors"
                                    >
                                        <x-lucide-play class="w-5 h-5 shrink-0 fill-current" />
                                        <span>Tonton Sekarang</span>
                                    </a>

                                    @if ($anime->trailer_youtube_id)
                                        <a
                                            href="https://www.youtube.com/watch?v={{ $anime->trailer_youtube_id }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center space-x-2 bg-surface hover:bg-stone-warm/40 text-ink border border-stone-warm px-5 py-3 rounded-full text-sm font-medium transition-colors"
                                        >
                                            <x-lucide-film class="w-5 h-5 shrink-0 text-ink-muted" />
                                            <span>Trailer</span>
                                        </a>
                                    @endif

                                    <div class="flex items-center space-x-2 text-sm text-ink-muted font-medium bg-surface border border-stone-warm px-4 py-2.5 rounded-full">
                                        <x-lucide-star class="w-5 h-5 shrink-0 text-gold fill-current" />
                                        <span class="text-ink font-semibold">{{ number_format((float) $anime->rating_score, 2) }}</span>
                                        <span>skor resmi</span>
                                        @if ($anime->scored_by)
                                            <span>({{ number_format($anime->scored_by) }} user)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Lucu Multi-Slide Progress Loader & Navigation Capsule (Strict Millishion Theme) -->
                    @if ($featuredAnimes->count() > 1)
                        <div class="pt-2 flex items-center space-x-3">
                            <!-- Prev Arrow Button -->
                            <button
                                type="button"
                                @click="prev()"
                                aria-label="Slide Sebelumnya"
                                class="w-9 h-9 rounded-full bg-surface border border-stone-warm flex items-center justify-center text-ink hover:text-mana hover:border-mana/60 hover:bg-alabaster transition-colors shrink-0 cursor-pointer"
                            >
                                <x-lucide-arrow-left class="w-4 h-4 shrink-0" />
                            </button>

                            <!-- Capsule Segment Track -->
                            <div class="bg-alabaster border border-stone-warm rounded-full px-2.5 py-1.5 flex items-center space-x-1.5">
                                @foreach ($featuredAnimes as $i => $item)
                                    <button
                                        type="button"
                                        @click="goTo({{ $i }})"
                                        class="relative h-2 rounded-full overflow-hidden transition-all duration-300 focus:outline-hidden block cursor-pointer"
                                        :class="current === {{ $i }} ? 'w-9 bg-surface border border-stone-warm/80' : 'w-6 bg-surface/70 hover:bg-surface border border-stone-warm/40'"
                                        aria-label="Pindah ke slide {{ $i + 1 }}: {{ $item->title }}"
                                    >
                                        <div
                                            class="h-full bg-mana rounded-full transition-[width] duration-75 ease-linear"
                                            :style="current === {{ $i }} ? `width: ${progress}%` : 'width: 0%'"
                                        ></div>
                                    </button>
                                @endforeach
                            </div>

                            <!-- Next Arrow Button -->
                            <button
                                type="button"
                                @click="next()"
                                aria-label="Slide Berikutnya"
                                class="w-9 h-9 rounded-full bg-surface border border-stone-warm flex items-center justify-center text-ink hover:text-mana hover:border-mana/60 hover:bg-alabaster transition-colors shrink-0 cursor-pointer"
                            >
                                <x-lucide-arrow-right class="w-4 h-4 shrink-0" />
                            </button>
                        </div>
                    @endif
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
