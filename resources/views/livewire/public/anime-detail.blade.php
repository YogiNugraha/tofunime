<div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 lg:py-14 space-y-12">
    <!-- Push SEO JSON-LD to head -->
    @push('scripts')
        {!! $seoJsonLd !!}
    @endpush

    <!-- Back Button -->
    <div>
        <a href="{{ url()->previous(url('/anime')) }}" class="inline-flex items-center text-sm font-medium text-ink-muted hover:text-mana transition-colors">
            &larr; Kembali ke Katalog
        </a>
    </div>

    <!-- Header Detail Anime (Desktop-2 Layout adaptation) -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 items-start">
        <!-- Left: Poster (aspect-[2/3]) -->
        <div class="md:col-span-4 lg:col-span-3">
            <div class="aspect-[2/3] rounded-lg overflow-hidden border border-stone-warm bg-surface shadow-xs">
                <img
                    src="{{ $anime->poster_url }}"
                    alt="{{ $anime->title }}"
                    class="w-full h-full object-cover"
                />
            </div>
        </div>

        <!-- Right: Information & Metadata -->
        <div class="md:col-span-8 lg:col-span-9 space-y-5">
            <div class="flex items-center space-x-3 text-sm font-medium text-gold">
                <span>{{ $anime->status->label() }}</span>
                <span>&middot;</span>
                <span>Total: {{ $anime->total_episodes ? $anime->total_episodes . ' Episode' : 'Ongoing' }}</span>
                @if ($anime->release_day)
                    <span>&middot;</span>
                    <span>Tayang: {{ $anime->release_day->label() }}</span>
                @endif
            </div>

            <div>
                <h1 class="text-3xl lg:text-4xl font-serif font-semibold text-ink tracking-tight">
                    {{ $anime->title }}
                </h1>
                @if ($anime->japanese_title)
                    <p class="text-base text-ink-muted mt-1 font-sans">
                        {{ $anime->japanese_title }} {{ $anime->english_title ? '· ' . $anime->english_title : '' }}
                    </p>
                @endif
            </div>

            <!-- Metadata Row -->
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-muted border-y border-stone-warm py-3">
                @if ($anime->studio)
                    <div>
                        <span class="text-ink font-medium">Studio:</span>
                        <a href="{{ url('/studio/' . $anime->studio->slug) }}" class="text-mana hover:underline">{{ $anime->studio->name }}</a>
                    </div>
                    <span>&middot;</span>
                @endif

                @if ($anime->people->isNotEmpty())
                    <div>
                        <span class="text-ink font-medium">Creator:</span>
                        <a href="{{ url('/author/' . $anime->people->first()->slug) }}" class="text-mana hover:underline">{{ $anime->people->first()->name }}</a>
                    </div>
                    <span>&middot;</span>
                @endif

                <div>
                    <span class="text-ink font-medium">Musim:</span>
                    <span>{{ $anime->season?->name ?? ($anime->release_year ?? '-') }}</span>
                </div>
                <span>&middot;</span>

                <div>
                    <span class="text-ink font-medium">Durasi:</span>
                    <span>{{ $anime->duration ?? '24 Menit' }}</span>
                </div>
            </div>

            <!-- Score Box & Action Buttons -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2 bg-surface border border-stone-warm px-4 py-2.5 rounded-full">
                    <x-lucide-star class="w-6 h-6 shrink-0 text-gold fill-current" />
                    <span class="font-semibold text-ink text-base leading-6">{{ number_format((float) $anime->rating_score, 2) }}</span>
                    <span class="text-sm text-ink-muted">({{ number_format($anime->scored_by) }} user)</span>
                </div>

                @if ($firstEpisode)
                    <a
                        href="{{ url('/nonton/' . $firstEpisode->slug) }}"
                        class="inline-flex items-center space-x-2 bg-mana hover:bg-mana-hover text-white px-6 py-2.5 rounded-full text-sm font-semibold transition-colors"
                    >
                        <x-lucide-play class="w-5 h-5 shrink-0 fill-current" />
                        <span>Mulai Nonton Ep {{ $firstEpisode->episode_number }}</span>
                    </a>
                @endif

                @if ($latestEpisode && $latestEpisode->id !== $firstEpisode?->id)
                    <a
                        href="{{ url('/nonton/' . $latestEpisode->slug) }}"
                        class="inline-flex items-center space-x-2 bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-5 py-2.5 rounded-full text-sm font-semibold transition-colors"
                    >
                        <span>Episode Terbaru (Ep {{ $latestEpisode->episode_number }})</span>
                    </a>
                @endif
            </div>

            <!-- Sinopsis -->
            <div>
                <h3 class="font-serif font-semibold text-lg text-ink mb-2">Sinopsis</h3>
                <p class="text-base text-ink-muted leading-relaxed whitespace-pre-line font-normal">
                    {{ $anime->synopsis ?? 'Sinopsis untuk anime ini belum tersedia.' }}
                </p>
            </div>

            <!-- Taxonomies (Genres, Themes, Demographics) -->
            <div class="flex flex-wrap items-center gap-2 pt-2">
                @foreach ($anime->genres as $genre)
                    <a href="{{ url('/genre/' . $genre->slug) }}" class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3.5 py-1 rounded-full text-sm font-medium transition-colors">
                        {{ $genre->name }}
                    </a>
                @endforeach
                @foreach ($anime->themes as $theme)
                    <a href="{{ url('/tag/' . $theme->slug) }}" class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3.5 py-1 rounded-full text-sm font-medium transition-colors">
                        {{ $theme->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Episode List Section -->
    <section class="space-y-6 pt-8 border-t border-stone-warm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-serif font-semibold text-ink">Daftar Episode</h2>
                <p class="text-sm text-ink-muted mt-1">Pilih nomor episode untuk mulai memutar video.</p>
            </div>
            <div class="text-sm text-ink-muted font-medium">
                Tersedia {{ $anime->episodes->count() }} Episode
            </div>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
            @forelse ($anime->episodes as $ep)
                <a
                    href="{{ url('/nonton/' . $ep->slug) }}"
                    class="bg-surface hover:bg-alabaster border border-stone-warm hover:border-mana rounded-lg p-3 text-center transition-colors group block"
                >
                    <span class="block text-base font-semibold text-ink group-hover:text-mana transition-colors">
                        Ep {{ $ep->episode_number }}
                    </span>
                    <span class="block text-sm text-ink-muted mt-0.5">
                        Sub Indo
                    </span>
                </a>
            @empty
                <div class="col-span-full py-8 text-center text-sm text-ink-muted bg-surface rounded-lg border border-stone-warm">
                    Belum ada episode yang diimpor untuk anime ini.
                </div>
            @endforelse
        </div>
    </section>

    <!-- Batch Download Links (if available) -->
    @if ($anime->downloadLinks->isNotEmpty())
        <section class="space-y-4 pt-4 border-t border-stone-warm">
            <h2 class="text-xl font-serif font-semibold text-ink">Download Batch Lengkap</h2>
            <div class="bg-surface border border-stone-warm rounded-lg p-5 divide-y divide-stone-warm">
                @foreach ($anime->downloadLinks as $link)
                    <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between text-sm">
                        <div class="space-x-3">
                            <span class="font-semibold text-ink">Batch {{ $link->quality->value }} ({{ $link->format->value }})</span>
                            <span class="text-ink-muted">{{ $link->file_size ?? 'Full Size' }}</span>
                        </div>
                        <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer nofollow" class="text-mana hover:underline font-medium">
                            Unduh via {{ $link->host_name }} &rarr;
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Similar Recommendations -->
    @if ($recommendations->isNotEmpty())
        <section class="space-y-6 pt-8 border-t border-stone-warm">
            <div>
                <h2 class="text-2xl font-serif font-semibold text-ink">Rekomendasi Anime Serupa</h2>
                <p class="text-sm text-ink-muted mt-1">Judul lain yang mungkin menarik untuk ditonton selanjutnya.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
                @foreach ($recommendations as $rec)
                    <article class="group flex flex-col space-y-2">
                        <a href="{{ url('/anime/' . $rec->slug) }}" class="relative aspect-[2/3] rounded-lg overflow-hidden border border-stone-warm bg-surface block">
                            <img
                                src="{{ $rec->poster_url }}"
                                alt="{{ $rec->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                        </a>
                        <h3 class="text-sm font-sans font-semibold text-ink line-clamp-1 group-hover:text-mana transition-colors">
                            <a href="{{ url('/anime/' . $rec->slug) }}">{{ $rec->title }}</a>
                        </h3>
                        <div class="flex items-center justify-between text-sm text-ink-muted">
                            <span>{{ $rec->release_year ?? '2026' }}</span>
                            <span class="font-medium text-ink flex items-center space-x-1">
                                <x-lucide-star class="w-5 h-5 shrink-0 text-gold fill-current" />
                                <span>{{ number_format((float) $rec->rating_score, 1) }}</span>
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
