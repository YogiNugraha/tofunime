<div class="space-y-8">
    <!-- Header -->
    <div class="pb-6 border-b border-stone-warm">
        <h1 class="text-2xl font-serif font-semibold text-ink">Manajemen Katalog Anime</h1>
        <p class="text-sm text-ink-muted mt-1">Daftar master seluruh anime di Tofunime. Metadata dan episode terintegrasi via Scraper.</p>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <x-lucide-check-circle-2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                <x-lucide-x class="w-4 h-4 shrink-0" />
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <x-lucide-alert-circle class="w-5 h-5 text-rose-600 shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800">
                <x-lucide-x class="w-4 h-4 shrink-0" />
            </button>
        </div>
    @endif

    <!-- Clean Filter & Search Bar -->
    <div class="bg-surface border border-stone-warm p-5 rounded-lg space-y-4">
        <!-- Search Row -->
        <div class="flex items-center justify-between gap-4">
            <div class="relative flex-1">
                <x-lucide-search class="w-4 h-4 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Cari judul anime (Romaji, Inggris, atau Jepang)..."
                    class="w-full bg-parchment border border-stone-warm rounded-lg pl-10 pr-4 py-2 text-sm text-ink placeholder:text-ink-muted/70 focus:outline-none focus:border-mana transition-colors" />
                @if (!empty($search))
                    <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-muted hover:text-ink">
                        <x-lucide-x class="w-4 h-4 shrink-0" />
                    </button>
                @endif
            </div>

            @if ($hasActiveFilters)
                <button wire:click="resetFilters" type="button"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg bg-alabaster border border-stone-warm hover:bg-stone-warm text-ink text-sm font-medium transition-colors shrink-0">
                    <x-lucide-rotate-ccw class="w-4 h-4 shrink-0 text-ink-muted" />
                    <span>Reset Filter</span>
                </button>
            @endif
        </div>

        <!-- Filter Selects Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-3 border-t border-stone-warm/60">
            <div>
                <label class="block text-sm font-medium text-ink-muted mb-1.5">Status Tayang</label>
                <select wire:model.live="statusFilter"
                    class="w-full bg-parchment border border-stone-warm rounded-lg px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors">
                    <option value="">Semua Status</option>
                    @foreach ($availableStatuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-muted mb-1.5">Tipe Format</label>
                <select wire:model.live="typeFilter"
                    class="w-full bg-parchment border border-stone-warm rounded-lg px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors">
                    <option value="">Semua Tipe</option>
                    @foreach ($availableTypes as $typeOption)
                        <option value="{{ $typeOption->value }}">{{ $typeOption->value }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-muted mb-1.5">Genre</label>
                <select wire:model.live="genreFilter"
                    class="w-full bg-parchment border border-stone-warm rounded-lg px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors">
                    <option value="">Semua Genre</option>
                    @foreach ($availableGenres as $genreOption)
                        <option value="{{ $genreOption }}">{{ $genreOption }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-muted mb-1.5">Urutkan Berdasarkan</label>
                <select wire:model.live="sortBy"
                    class="w-full bg-parchment border border-stone-warm rounded-lg px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana transition-colors">
                    <option value="latest">Terbaru Ditambahkan</option>
                    <option value="rating">Rating Tertinggi</option>
                    <option value="popular">Terpopuler (Views)</option>
                    <option value="title_asc">Judul (A - Z)</option>
                    <option value="episodes_desc">Episode Terbanyak</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Clean Anime Table -->
    <div class="bg-surface border border-stone-warm rounded-lg overflow-hidden">
        <div class="overflow-x-auto min-h-[420px] pb-12">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-warm text-ink-muted bg-alabaster/50">
                        <th class="py-3.5 px-5 font-semibold text-ink">Anime</th>
                        <th class="py-3.5 px-5 font-semibold text-ink">Studio</th>
                        <th class="py-3.5 px-5 font-semibold text-ink">Skor</th>
                        <th class="py-3.5 px-5 font-semibold text-ink">Episode</th>
                        <th class="py-3.5 px-5 font-semibold text-ink text-center">Featured</th>
                        <th class="py-3.5 px-5 font-semibold text-ink text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-warm/60">
                    @forelse ($animes as $anime)
                        <tr class="hover:bg-alabaster/30 transition-colors">
                            <!-- Anime Title & Poster -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center space-x-3.5">
                                    <img src="{{ $anime->poster_url }}" alt="{{ $anime->title }}" referrerpolicy="no-referrer"
                                        class="w-10 h-14 object-cover rounded-md border border-stone-warm bg-alabaster shrink-0"
                                        onerror="this.onerror=null; this.src='https://placehold.co/80x112/efece6/64748b?text=Poster';" />
                                    <div class="min-w-0 max-w-sm">
                                        <a href="{{ route('admin.animes.edit', $anime) }}"
                                            class="font-semibold text-ink hover:text-mana transition-colors line-clamp-1">
                                            {{ $anime->title }}
                                        </a>
                                        <div class="text-sm text-ink-muted mt-0.5">
                                            {{ $anime->type?->value ?? 'TV' }} &middot; {{ $anime->status?->label() ?? 'Ongoing' }}
                                            @if ($anime->release_year)
                                                &middot; {{ $anime->release_year }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Studio -->
                            <td class="py-3.5 px-5 text-ink-muted">
                                {{ $anime->studio?->name ?? '-' }}
                            </td>

                            <!-- Skor -->
                            <td class="py-3.5 px-5 font-medium text-ink">
                                ★ {{ number_format((float) $anime->rating_score, 2) }}
                            </td>

                            <!-- Total Episodes -->
                            <td class="py-3.5 px-5 text-ink-muted">
                                {{ $anime->episodes_count }} Ep
                            </td>

                            <!-- Featured Hero Toggle -->
                            <td class="py-3.5 px-5 text-center">
                                <button wire:click="toggleFeatured({{ $anime->id }})" type="button"
                                    class="px-2.5 py-1 rounded-md text-sm font-medium transition-colors {{ $anime->is_featured ? 'text-mana bg-mana/10 font-semibold' : 'text-ink-muted hover:text-ink' }}">
                                    {{ $anime->is_featured ? 'Featured' : 'Biasa' }}
                                </button>
                            </td>

                            <!-- Action Dropdown -->
                            <td class="py-3.5 px-5 text-right">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button
                                        @click="open = !open"
                                        type="button"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-ink-muted hover:text-ink hover:bg-alabaster transition-colors focus:outline-none"
                                        title="Pilihan Aksi"
                                        aria-label="Menu Aksi"
                                    >
                                        <x-lucide-more-vertical class="w-5 h-5 shrink-0" />
                                    </button>

                                    <div
                                        x-show="open"
                                        x-cloak
                                        @click.outside="open = false"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute right-0 z-30 mt-1 w-52 origin-top-right rounded-xl bg-surface border border-stone-warm shadow-lg py-1.5 focus:outline-none text-left"
                                    >
                                        <a
                                            href="{{ route('admin.animes.edit', $anime) }}"
                                            class="flex items-center space-x-2.5 px-3.5 py-2 text-sm text-ink hover:bg-alabaster hover:text-mana transition-colors"
                                        >
                                            <x-lucide-pencil class="w-4 h-4 shrink-0 text-ink-muted" />
                                            <span>Edit Metadata Anime</span>
                                        </a>

                                        <button
                                            wire:click="quickSyncMal({{ $anime->id }})"
                                            @click="open = false"
                                            type="button"
                                            class="w-full flex items-center space-x-2.5 px-3.5 py-2 text-sm text-ink hover:bg-alabaster hover:text-mana transition-colors text-left"
                                            title="Sinkronisasi metadata otomatis dari MyAnimeList / AniList"
                                        >
                                            <x-lucide-refresh-cw class="w-4 h-4 shrink-0 text-ink-muted" />
                                            <span>Tarik Ulang Data MAL</span>
                                        </button>

                                        <a
                                            href="{{ url('/anime/' . $anime->slug) }}"
                                            target="_blank"
                                            class="flex items-center space-x-2.5 px-3.5 py-2 text-sm text-ink hover:bg-alabaster hover:text-mana transition-colors"
                                        >
                                            <x-lucide-external-link class="w-4 h-4 shrink-0 text-ink-muted" />
                                            <span>Lihat di Website</span>
                                        </a>

                                        @if ($anime->episodes_count > 0)
                                            <a
                                                href="{{ url('/admin/episodes/' . $anime->episodes()->first()->id) }}"
                                                class="flex items-center space-x-2.5 px-3.5 py-2 text-sm text-ink hover:bg-alabaster hover:text-mana transition-colors"
                                            >
                                                <x-lucide-film class="w-4 h-4 shrink-0 text-ink-muted" />
                                                <span>Kelola Episode & Mirror</span>
                                            </a>
                                        @endif

                                        <button
                                            wire:click="toggleFeatured({{ $anime->id }})"
                                            @click="open = false"
                                            type="button"
                                            class="w-full flex items-center space-x-2.5 px-3.5 py-2 text-sm text-ink hover:bg-alabaster hover:text-mana transition-colors text-left"
                                        >
                                            <x-lucide-sparkles class="w-4 h-4 shrink-0 text-ink-muted" />
                                            <span>{{ $anime->is_featured ? 'Batal Featured' : 'Set Featured Hero' }}</span>
                                        </button>

                                        <div class="my-1 border-t border-stone-warm"></div>

                                        <button
                                            wire:confirm="Yakin ingin menghapus anime ini dari katalog?"
                                            wire:click="deleteAnime({{ $anime->id }})"
                                            @click="open = false"
                                            type="button"
                                            class="w-full flex items-center space-x-2.5 px-3.5 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors text-left"
                                        >
                                            <x-lucide-trash-2 class="w-4 h-4 shrink-0 text-red-500" />
                                            <span>Hapus Anime</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-ink-muted">
                                Tidak ada anime yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-stone-warm bg-surface">
            {{ $animes->links() }}
        </div>
    </div>
</div>