<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-stone-warm">
        <div>
            <h1 class="text-2xl font-serif font-semibold text-ink">Dashboard CMS Tofunime</h1>
            <p class="text-sm text-ink-muted mt-1">Ringkasan status agregator anime, bot scraper, dan pelaporan sistem.</p>
        </div>

        <a
            href="{{ url('/admin/scraper') }}"
            class="inline-flex items-center space-x-2 bg-mana hover:bg-mana-hover text-white px-5 py-2.5 rounded-full text-sm font-semibold transition-colors self-start sm:self-auto"
        >
            <span>Buka Hybrid Grabber</span>
            <span>&rarr;</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-5">
        <div class="bg-surface border border-stone-warm p-5 rounded-lg">
            <span class="text-sm font-medium text-ink-muted block">Total Anime</span>
            <span class="text-2xl font-serif font-semibold text-ink mt-2 block">{{ number_format($stats['total_animes']) }}</span>
        </div>
        <div class="bg-surface border border-stone-warm p-5 rounded-lg">
            <span class="text-sm font-medium text-ink-muted block">Anime Ongoing</span>
            <span class="text-2xl font-serif font-semibold text-ink mt-2 block">{{ number_format($stats['ongoing_animes']) }}</span>
        </div>
        <div class="bg-surface border border-stone-warm p-5 rounded-lg">
            <span class="text-sm font-medium text-ink-muted block">Total Episode</span>
            <span class="text-2xl font-serif font-semibold text-ink mt-2 block">{{ number_format($stats['total_episodes']) }}</span>
        </div>
        <div class="bg-surface border border-stone-warm p-5 rounded-lg">
            <span class="text-sm font-medium text-ink-muted block">Server Mirror Aktif</span>
            <span class="text-2xl font-serif font-semibold text-ink mt-2 block">{{ number_format($stats['total_mirrors']) }}</span>
        </div>
        <div class="bg-surface border border-stone-warm p-5 rounded-lg">
            <span class="text-sm font-medium text-ink-muted block">Laporan Rusak</span>
            <span class="text-2xl font-serif font-semibold text-ink mt-2 block">{{ number_format($stats['pending_reports']) }}</span>
        </div>
    </div>

    <!-- Recent Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Anime Baru Ditambahkan -->
        <div class="bg-surface border border-stone-warm rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-stone-warm">
                <h2 class="font-serif font-semibold text-lg text-ink">Anime Baru Ditambahkan</h2>
                <a href="{{ url('/admin/animes') }}" class="text-sm font-medium text-mana hover:underline">Lihat Semua</a>
            </div>

            <div class="divide-y divide-stone-warm">
                @forelse ($recentAnimes as $anime)
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <img src="{{ $anime->poster_url }}" alt="" referrerpolicy="no-referrer" class="w-10 h-14 object-cover rounded border border-stone-warm shrink-0 bg-alabaster" onerror="this.onerror=null; this.src='https://placehold.co/80x112/efece6/64748b?text=Poster';" />
                            <div>
                                <h4 class="text-sm font-semibold text-ink">{{ $anime->title }}</h4>
                                <span class="text-sm text-ink-muted">{{ $anime->studio?->name ?? '-' }} &middot; {{ $anime->status->label() }}</span>
                            </div>
                        </div>
                        <a href="{{ url('/anime/' . $anime->slug) }}" target="_blank" class="text-sm text-mana font-medium hover:underline">
                            Kunjungi
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted py-4">Belum ada anime di database.</p>
                @endforelse
            </div>
        </div>

        <!-- Scraper Logs Terakhir -->
        <div class="bg-surface border border-stone-warm rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-stone-warm">
                <h2 class="font-serif font-semibold text-lg text-ink">Riwayat Bot Scraper</h2>
                <a href="{{ url('/admin/scraper') }}" class="text-sm font-medium text-mana hover:underline">Buka Grabber</a>
            </div>

            <div class="divide-y divide-stone-warm">
                @forelse ($recentLogs as $log)
                    <div class="py-3 flex items-center justify-between text-sm">
                        <div class="space-y-0.5 max-w-xs sm:max-w-md truncate">
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-ink uppercase">{{ $log->provider_name }}</span>
                                <span class="text-ink-muted">&middot; {{ $log->action }}</span>
                            </div>
                            <span class="text-ink-muted truncate block">{{ $log->target_url }}</span>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-sm font-medium {{ $log->status === 'success' ? 'bg-alabaster text-ink border border-stone-warm' : 'bg-stone-warm text-ink' }}">
                                {{ $log->status }}
                            </span>
                            <span class="block text-sm text-ink-muted mt-0.5">{{ $log->response_time_ms ? $log->response_time_ms . 'ms' : '' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted py-4">Belum ada log scraping yang tercatat.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
