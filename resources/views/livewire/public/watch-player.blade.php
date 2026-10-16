<div class="py-8 lg:py-12 space-y-10 max-w-7xl mx-auto px-6 lg:px-8">
    <!-- Push SEO JSON-LD to head -->
    @push('scripts')
        {!! $seoJsonLd !!}
    @endpush

    <!-- Breadcrumb & Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-stone-warm">
        <div class="space-y-1">
            <div class="flex items-center space-x-2 text-sm text-ink-muted">
                <a href="{{ url('/') }}" class="hover:text-mana transition-colors">Beranda</a>
                <span>/</span>
                <a href="{{ url('/anime/' . $anime->slug) }}"
                    class="hover:text-mana transition-colors line-clamp-1">{{ $anime->title }}</a>
                <span>/</span>
                <span class="text-ink font-medium">Episode {{ $episode->episode_number }}</span>
            </div>
            <h1 class="text-2xl lg:text-3xl font-serif font-semibold text-ink tracking-tight">
                Nonton {{ $anime->title }} Episode {{ $episode->episode_number }} Sub Indo
            </h1>
        </div>

        <!-- Next / Prev Controls -->
        <div class="flex items-center space-x-3">
            @if ($prevEpisode)
                <a href="{{ url('/nonton/' . $prevEpisode->slug) }}"
                    class="inline-flex items-center space-x-1.5 bg-surface hover:bg-alabaster text-ink border border-stone-warm px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <x-lucide-arrow-left class="w-4 h-4 shrink-0 text-ink-muted" />
                    <span>Ep {{ $prevEpisode->episode_number }}</span>
                </a>
            @endif

            @if ($nextEpisode)
                <a href="{{ url('/nonton/' . $nextEpisode->slug) }}"
                    class="inline-flex items-center space-x-1.5 bg-mana hover:bg-mana-hover text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                    <span>Ep {{ $nextEpisode->episode_number }}</span>
                    <x-lucide-arrow-right class="w-4 h-4 shrink-0" />
                </a>
            @endif
        </div>
    </div>

    <!-- Video Player Frame with Tofunime Secure Header -->
    <div class="relative bg-black rounded-lg overflow-hidden border border-stone-warm shadow-md">
        <!-- Player Brand & Action Header -->
        <div class="bg-ink px-4 py-2.5 flex items-center justify-between border-b border-stone-warm/20 text-sm">
            <span class="inline-flex items-center space-x-2 text-sm font-semibold tracking-wide text-white">
                <span class="w-2 h-2 rounded-full bg-mana"></span>
                <span>Tofunime Player &bull; {{ $activeMirror ? $activeMirror->display_name : 'Streaming' }}</span>
            </span>

            @if (!empty($currentEmbedUrl))
                <a href="{{ route('stream.player', $activeMirrorId) }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center space-x-1.5 text-sm text-stone-warm hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1 rounded transition-colors"
                    title="Buka player di tab baru">
                    <x-lucide-external-link class="w-4 h-4 shrink-0" />
                    <span>Buka di Tab Baru</span>
                </a>
            @endif
        </div>

        <div class="aspect-video w-full relative">
            @if (!empty($currentEmbedUrl))
                <iframe wire:key="player-frame-{{ $activeMirrorId }}" src="{{ route('stream.player', $activeMirrorId) }}"
                    class="w-full h-full border-0" allowfullscreen referrerpolicy="no-referrer"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe>
            @else
                <div
                    class="w-full h-full flex flex-col items-center justify-center bg-alabaster text-ink p-8 text-center space-y-4">
                    <p class="text-base font-semibold">Server video streaming belum tersedia untuk episode ini.</p>
                    <p class="text-sm text-ink-muted max-w-md">Silakan klik tombol di bawah untuk melaporkan atau
                        mengantrekan proses ekstraksi server otomatis.</p>
                    <button wire:click="reportBrokenAndFallback" type="button"
                        class="bg-mana hover:bg-mana-hover text-white px-5 py-2.5 rounded-full text-sm font-semibold transition-colors">
                        Ambil Ulang Server Otomatis
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Notification Alert for Auto-Fallback -->
    @if ($reportedBroken)
        <div class="bg-surface border border-mana text-ink px-4 py-3 rounded-lg text-sm flex items-center justify-between">
            <span>{{ $reportMessage }}</span>
            <button type="button" wire:click="$set('reportedBroken', false)"
                class="text-ink-muted hover:text-ink transition-colors ml-4 p-1 rounded-md" aria-label="Tutup">
                <x-lucide-x class="w-5 h-5 shrink-0" />
            </button>
        </div>
    @endif

    <!-- Controls Row: Resolution Tabs, Servers, Auto-Fallback & Theater Mode -->
    <div class="bg-surface border border-stone-warm rounded-lg p-5 space-y-5">
        <!-- Server Status Bar -->
        @if ($activeMirror)
            <div class="flex items-center pb-3 border-b border-stone-warm text-sm">
                <span
                    class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-alabaster text-ink border border-stone-warm font-medium">
                    <span class="w-2 h-2 rounded-full bg-mana"></span>
                    <span>Server Siap Putar: <strong class="font-semibold">{{ $activeMirror->display_name }}</strong>
                        ({{ $activeMirror->quality->value }})</span>
                </span>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <!-- Resolution Selector Tabs -->
            <div class="space-y-2">
                <span class="block text-sm font-medium text-ink-muted">Pilih Resolusi:</span>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($availableQualities as $quality)
                        <button wire:click="setQuality('{{ $quality }}')" type="button"
                            class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $selectedQuality === $quality ? 'bg-mana text-white' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}">
                            {{ $quality }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Server Selector Buttons with Health Status -->
            <div class="space-y-2">
                <span class="block text-sm font-medium text-ink-muted">Pilih Server Mirror:</span>
                <div class="flex flex-wrap items-center gap-2">
                    @forelse ($mirrorsForQuality as $mirror)
                        @php
                            $isOnline = $mirrorHealthMap[$mirror->id] ?? null;
                        @endphp
                        <button wire:click="setMirror({{ $mirror->id }})" type="button"
                            class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $activeMirrorId === $mirror->id ? 'bg-mana text-white' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm' }}"
                            title="{{ $isOnline === true ? 'Server Terverifikasi Aktif' : ($isOnline === false ? 'Server Gangguan / Offline' : 'Server Belum Diuji') }}">
                            @if ($isOnline === true)
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                            @elseif ($isOnline === false)
                                <span class="w-1.5 h-1.5 rounded-full bg-stone-warm"></span>
                            @else
                                <span class="w-1.5 h-1.5 rounded-full bg-stone-warm"></span>
                            @endif
                            <span>{{ $mirror->display_name }}</span>
                        </button>
                    @empty
                        <span class="text-sm text-ink-muted">Tidak ada server untuk {{ $selectedQuality }}</span>
                    @endforelse
                </div>
            </div>

            <!-- Action Controls: Ganti Server -->
            <div class="flex items-center space-x-3 pt-2 lg:pt-0">
                <button wire:click="reportBrokenAndFallback" type="button"
                    class="inline-flex items-center space-x-2 bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    title="Klik jika video tidak bisa diputar untuk berganti server secara otomatis">
                    <x-lucide-refresh-cw class="w-4 h-4 shrink-0 text-ink-muted" />
                    <span>Ganti Server</span>
                </button>
            </div>
        </div>
    </div>


    <!-- Ad Slot: player_below_controls -->
    <x-ad-slot key="player_below_controls" />

    <!-- Episode Selector Grid -->
    <div class="space-y-4 pt-6 border-t border-stone-warm">
        <div class="flex items-center justify-between">
            <h3 class="font-serif font-semibold text-xl text-ink">Pilih Episode Lain</h3>
            <span class="text-sm text-ink-muted font-medium">Total: {{ $allEpisodes->count() }} Episode</span>
        </div>

        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-12 gap-2.5">
            @foreach ($allEpisodes as $ep)
                <a href="{{ url('/nonton/' . $ep->slug) }}"
                    class="p-2.5 rounded text-center text-sm font-semibold transition-colors border {{ $ep->id === $episode->id ? 'bg-mana text-white border-mana' : 'bg-surface hover:bg-alabaster text-ink border-stone-warm' }}">
                    {{ $ep->episode_number }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Download Links Section -->
    @if ($episode->downloadLinks->isNotEmpty())
        <div class="space-y-4 pt-6 border-t border-stone-warm">
            <h3 class="font-serif font-semibold text-xl text-ink">Link Download Episode {{ $episode->episode_number }}</h3>
            <div class="bg-surface border border-stone-warm rounded-lg p-5 divide-y divide-stone-warm">
                @foreach ($episode->downloadLinks->groupBy('quality.value') as $quality => $links)
                    <div
                        class="py-3 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                        <div class="space-x-2">
                            <span class="font-semibold text-ink">{{ $quality }} ({{ $links->first()->format->value }})</span>
                            <span class="text-ink-muted">{{ $links->first()->file_size ?? 'Normal Size' }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($links as $link)
                                <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer nofollow"
                                    class="bg-alabaster hover:bg-stone-warm/50 text-ink border border-stone-warm px-3 py-1 rounded text-sm font-medium transition-colors">
                                    {{ $link->host_name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>