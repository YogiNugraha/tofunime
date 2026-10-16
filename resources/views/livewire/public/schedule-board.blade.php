<div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 lg:py-14 space-y-8">
    <!-- Header -->
    <div class="pb-6 border-b border-stone-warm">
        <h1 class="text-3xl font-serif font-semibold text-ink tracking-tight">Jadwal Rilis Anime Mingguan</h1>
        <p class="text-sm text-ink-muted mt-1">Estimasi jadwal penayangan episode baru anime Ongoing Subtitle Indonesia setiap harinya.</p>
    </div>

    <!-- Day Selector Tabs (Anti-AI Slop: Warna Seragam Alabaster) -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-2">
        <button
            wire:click="setDay('all')"
            class="px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap {{ $activeDay === 'all' ? 'bg-mana text-white font-semibold' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}"
        >
            Semua Hari
        </button>
        @foreach ($daysMap as $dayKey => $dayLabel)
            <button
                wire:click="setDay('{{ $dayKey }}')"
                class="px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap {{ $activeDay === $dayKey ? 'bg-mana text-white font-semibold' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}"
            >
                {{ $dayLabel }}
            </button>
        @endforeach
    </div>

    <!-- Grouped Schedule Cards -->
    <div class="space-y-10">
        @foreach ($daysMap as $dayKey => $dayLabel)
            @if ($activeDay === 'all' || $activeDay === $dayKey)
                @php
                    $animesInDay = $schedule[$dayKey] ?? collect();
                @endphp

                <section class="space-y-4">
                    <div class="flex items-center space-x-3 pb-2 border-b border-stone-warm">
                        <h2 class="font-serif font-semibold text-xl text-ink">{{ $dayLabel }}</h2>
                        <span class="text-sm text-ink-muted font-medium">({{ $animesInDay->count() }} Judul)</span>
                    </div>

                    @if ($animesInDay->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
                            @foreach ($animesInDay as $anime)
                                <article class="group flex flex-col space-y-2">
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
                                        <p class="text-sm text-ink-muted line-clamp-1">
                                            {{ $anime->studio?->name ?? 'Ongoing' }}
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-ink-muted py-4">Belum ada anime Ongoing yang dijadwalkan pada hari {{ strtolower($dayLabel) }}.</p>
                    @endif
                </section>
            @endif
        @endforeach
    </div>
</div>
