<div
    x-data="{
        open: @entangle('isOpen'),
        init() {
            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.open = true;
                    $nextTick(() => $refs.searchInput.focus());
                }
                if (e.key === 'Escape' && this.open) {
                    this.open = false;
                }
            });
        }
    }"
    class="relative"
>
    <!-- Search Button Trigger -->
    <button
        type="button"
        @click="open = true; $nextTick(() => $refs.searchInput.focus())"
        class="flex items-center space-x-2 text-ink-muted hover:text-mana bg-alabaster hover:bg-stone-warm/50 border border-stone-warm px-3.5 py-2 rounded-full transition-colors cursor-pointer"
        title="Cari anime... (Ctrl + K)"
    >
        <x-lucide-search class="w-5 h-5 text-ink-muted shrink-0" />
        <span class="hidden sm:inline text-sm font-medium text-ink-muted">Cari anime...</span>
        <kbd class="hidden sm:inline-block border border-stone-warm bg-surface px-1.5 py-0.5 rounded text-sm text-ink-muted">Ctrl K</kbd>
    </button>

    <!-- Search Modal Backdrop -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-ink/40 backdrop-blur-xs flex items-start justify-center pt-20 px-4"
        @click.self="open = false"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-surface border border-stone-warm rounded-xl shadow-xl w-full max-w-2xl overflow-hidden"
        >
            <!-- Input Bar -->
            <div class="relative border-b border-stone-warm px-4 py-3 flex items-center space-x-3 bg-parchment">
                <x-lucide-search class="w-5 h-5 text-ink-muted shrink-0" />
                <input
                    x-ref="searchInput"
                    wire:model.live.debounce.300ms="query"
                    type="text"
                    placeholder="Ketik judul anime, studio, atau keyword..."
                    class="w-full bg-transparent border-0 text-base text-ink placeholder:text-ink-muted focus:outline-none"
                />
                <button
                    @click="open = false"
                    type="button"
                    class="text-sm text-ink-muted hover:text-ink px-2 py-1 rounded border border-stone-warm bg-surface"
                >
                    ESC
                </button>
            </div>

            <!-- Results Dropdown -->
            <div class="max-h-96 overflow-y-auto divide-y divide-stone-warm p-2">
                @if (strlen(trim($query)) >= 2)
                    @forelse ($results as $item)
                        <a
                            href="{{ url('/anime/' . $item->slug) }}"
                            @click="open = false"
                            class="flex items-center space-x-4 p-3 rounded-lg hover:bg-alabaster transition-colors group block"
                        >
                            <img
                                src="{{ $item->poster_url }}"
                                alt="{{ $item->title }}"
                                class="w-12 h-16 object-cover rounded border border-stone-warm shrink-0"
                            />
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-ink group-hover:text-mana transition-colors truncate">
                                    {{ $item->title }}
                                </h4>
                                <div class="flex items-center space-x-2 text-sm text-ink-muted mt-0.5">
                                    <span>{{ $item->release_year ?? '2026' }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $item->studio?->name ?? 'TV' }}</span>
                                    <span>&middot;</span>
                                    <span class="text-ink font-medium">★ {{ number_format((float) $item->rating_score, 1) }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="py-8 text-center text-sm text-ink-muted">
                            Tidak ditemukan anime dengan kata kunci "{{ $query }}".
                        </div>
                    @endforelse
                @else
                    <div class="py-6 text-center text-sm text-ink-muted">
                        Ketik minimal 2 karakter untuk mencari anime secara instan.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
