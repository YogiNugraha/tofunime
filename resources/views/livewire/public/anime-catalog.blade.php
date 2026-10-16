<div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 lg:py-14 space-y-8">
    <!-- Header -->
    <div class="pb-6 border-b border-stone-warm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-serif font-semibold text-ink tracking-tight">Katalog Anime Lengkap</h1>
            <p class="text-sm text-ink-muted mt-1">Eksplorasi koleksi serial anime, movie, dan OVA ber-subtitle Indonesia.</p>
        </div>

        @if ($genre || $status || $type || $sort !== 'latest')
            <button
                wire:click="resetFilters"
                type="button"
                class="self-start md:self-auto text-sm font-medium text-mana hover:underline"
            >
                Reset Semua Filter &times;
            </button>
        @endif
    </div>

    <!-- Filter Bar (bg-alabaster, border-stone-warm, No Rainbow UI) -->
    <div class="bg-alabaster border border-stone-warm p-5 rounded-lg grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Genre / Tag Filter -->
        <div>
            <label class="block text-sm font-medium text-ink mb-1.5">Genre / Tema</label>
            <select
                wire:model.live="genre"
                class="w-full bg-surface border border-stone-warm rounded-md px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors"
            >
                <option value="">Semua Genre & Tag</option>
                @foreach ($taxonomies as $tax)
                    <option value="{{ $tax->slug }}">{{ $tax->name }} ({{ $tax->type->label() }})</option>
                @endforeach
            </select>
        </div>

        <!-- Status Filter -->
        <div>
            <label class="block text-sm font-medium text-ink mb-1.5">Status Rilis</label>
            <select
                wire:model.live="status"
                class="w-full bg-surface border border-stone-warm rounded-md px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors"
            >
                <option value="">Semua Status</option>
                <option value="Ongoing">Ongoing (Sedang Tayang)</option>
                <option value="Completed">Tamat (Completed)</option>
                <option value="Upcoming">Segera Tayang (Upcoming)</option>
            </select>
        </div>

        <!-- Type Filter -->
        <div>
            <label class="block text-sm font-medium text-ink mb-1.5">Format Tipe</label>
            <select
                wire:model.live="type"
                class="w-full bg-surface border border-stone-warm rounded-md px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors"
            >
                <option value="">Semua Format</option>
                <option value="TV">TV Series</option>
                <option value="Movie">Movie</option>
                <option value="OVA">OVA</option>
                <option value="ONA">ONA</option>
                <option value="Special">Special</option>
            </select>
        </div>

        <!-- Sort Filter -->
        <div>
            <label class="block text-sm font-medium text-ink mb-1.5">Urutkan</label>
            <select
                wire:model.live="sort"
                class="w-full bg-surface border border-stone-warm rounded-md px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors"
            >
                <option value="latest">Episode Terbaru</option>
                <option value="rating">Skor Rating Tertinggi</option>
                <option value="popular">Paling Populer</option>
                <option value="title">Judul (A-Z)</option>
            </select>
        </div>
    </div>

    <!-- Anime Grid (Aspect 2/3, 1 row metadata) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
        @forelse ($animes as $anime)
            <article class="group flex flex-col space-y-2.5">
                <a href="{{ url('/anime/' . $anime->slug) }}" class="relative aspect-[2/3] rounded-lg overflow-hidden border border-stone-warm bg-surface block">
                    <img
                        src="{{ $anime->poster_url }}"
                        alt="{{ $anime->title }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        loading="lazy"
                    />
                    <div class="absolute top-2.5 right-2.5 bg-surface/95 text-ink border border-stone-warm text-sm font-medium px-2 py-0.5 rounded shadow-xs">
                        {{ $anime->type->value }}
                    </div>
                </a>

                <div class="space-y-1">
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
                </div>
            </article>
        @empty
            <div class="col-span-full py-16 text-center text-sm text-ink-muted bg-surface rounded-lg border border-stone-warm">
                Tidak ada anime yang cocok dengan kombinasi filter yang dipilih.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-6 border-t border-stone-warm">
        {{ $animes->links() }}
    </div>
</div>
