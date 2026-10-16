<div class="space-y-10">
    <!-- Header -->
    <div class="pb-6 border-b border-stone-warm">
        <h1 class="text-2xl font-serif font-semibold text-ink">Hybrid Grabber Studio (Multi-Sumber &amp; Jikan API)</h1>
        <p class="text-sm text-ink-muted mt-1">
            Impor dan sinkronkan data anime secara hibrida dari Otakudesu (HTML Parser) atau Oploverz (SvelteKit JSON API) dengan REST API Jikan v4.
        </p>
    </div>

    <!-- Status Message Alert -->
    @if ($statusMessage)
        <div class="p-4 rounded-lg border text-sm {{ $statusType === 'success' ? 'bg-surface border-mana text-ink' : 'bg-surface border-stone-warm text-ink' }}">
            <div class="flex items-center justify-between">
                <span>{{ $statusMessage }}</span>
                <button
                    type="button"
                    wire:click="$set('statusMessage', '')"
                    class="text-ink-muted hover:text-ink transition-colors ml-4 p-1 rounded-md"
                    aria-label="Tutup"
                >
                    <x-lucide-x class="w-5 h-5 shrink-0" />
                </button>
            </div>
        </div>
    @endif

    <!-- PANEL 1: 1-Click Hybrid Importer -->
    <section
        x-data="{
            elapsed: '0.0',
            timerInterval: null,
            progressInterval: null,
            isRunning: false,
            hasLogs: {{ (! empty($executionLogs) || $isProcessing) ? 'true' : 'false' }},
            clientProgress: 5,
            clientStep: 'Menyiapkan proses ekstraksi...',
            copied: false,
            start() {
                this.isRunning = true;
                this.hasLogs = true;
                this.clientProgress = 10;
                this.clientStep = 'Menghubungi web provider sumber...';
                this.elapsed = '0.0';

                const streamEl = document.querySelector('[wire\\:stream=\'scraper-console-stream\']');
                if (streamEl) {
                    const now = new Date().toTimeString().split(' ')[0] + '.' + String(new Date().getMilliseconds()).padStart(3, '0');
                    streamEl.innerHTML = `
                        <div class='flex items-start gap-3 py-1.5 font-mono text-sm border-b border-stone-warm/30'>
                            <span class='text-ink-muted shrink-0'>${now}</span>
                            <span class='px-2 py-0.5 rounded text-mana bg-alabaster font-semibold shrink-0'>[INIT]</span>
                            <span class='text-ink break-all'>Memulai koneksi ke provider sumber dan menyiapkan engine scraping...</span>
                        </div>
                        <div class='flex items-center space-x-2 py-2 text-sm text-ink-muted font-mono' id='scraper-stream-placeholder'>
                            <svg class='w-4 h-4 animate-spin text-mana shrink-0' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'><circle class='opacity-25' cx='12' cy='12' r='10' stroke='currentColor' stroke-width='4'></circle><path class='opacity-75' fill='currentColor' d='M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z'></path></svg>
                            <span id='scraper-client-step-indicator'>Menghubungi web provider sumber...</span>
                        </div>
                    `;
                }

                const start = performance.now();
                clearInterval(this.timerInterval);
                this.timerInterval = setInterval(() => {
                    this.elapsed = ((performance.now() - start) / 1000).toFixed(1);
                }, 100);

                clearInterval(this.progressInterval);
                this.progressInterval = setInterval(() => {
                    if (this.isRunning && this.clientProgress < 92) {
                        this.clientProgress += Math.floor(Math.random() * 4) + 2;
                        if (this.clientProgress >= 20 && this.clientProgress < 45) {
                            this.clientStep = 'Mengunduh & mem-parsing dokumen halaman / API...';
                        } else if (this.clientProgress >= 45 && this.clientProgress < 70) {
                            this.clientStep = 'Sinkronisasi metadata resmi dengan MyAnimeList (Jikan v4)...';
                        } else if (this.clientProgress >= 70 && this.clientProgress < 85) {
                            this.clientStep = 'Mengekstrak mirrors streaming, server embed, dan link download...';
                        } else if (this.clientProgress >= 85) {
                            this.clientStep = 'Menyimpan katalog anime, episode, dan mirrors ke database...';
                        }
                        const indicator = document.getElementById('scraper-client-step-indicator');
                        if (indicator) {
                            indicator.innerText = this.clientStep;
                        }
                    }
                }, 750);
            },
            stop() {
                this.isRunning = false;
                this.clientProgress = 100;
                this.clientStep = 'Proses ekstraksi selesai!';
                clearInterval(this.timerInterval);
                clearInterval(this.progressInterval);
                const placeholder = document.getElementById('scraper-stream-placeholder');
                if (placeholder) {
                    placeholder.remove();
                }
            },
            clearAll() {
                this.stop();
                this.hasLogs = false;
                this.elapsed = '0.0';
                this.clientProgress = 0;
                this.clientStep = '';
                const streamEl = document.querySelector('[wire\\:stream=\'scraper-console-stream\']');
                if (streamEl) {
                    streamEl.innerHTML = '';
                }
                $wire.clearLogs();
            },
            copyLogs() {
                const el = document.getElementById('scraper-console-content');
                if (el) {
                    navigator.clipboard.writeText(el.innerText).then(() => {
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    });
                }
            }
        }"
        x-init="
            $wire.$watch('isProcessing', (val) => {
                if (val && !isRunning) {
                    start();
                } else if (!val && isRunning) {
                    stop();
                }
            });
        "
        @scraper-finished.window="stop()"
        class="bg-surface border border-stone-warm rounded-lg p-6 space-y-6"
    >
        <div class="pb-4 border-b border-stone-warm">
            <h2 class="font-serif font-semibold text-lg text-ink">1-Click Hybrid Importer</h2>
            <p class="text-sm text-ink-muted mt-0.5">Mendukung input URL anime Otakudesu maupun Oploverz. Sistem akan otomatis mendeteksi provider sumber.</p>
        </div>

        <form wire:submit="importNow" @submit="start()" class="space-y-5">
            <!-- Provider Selection Tabs -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink">Pilihan Provider Sumber</label>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="$set('selectedProvider', 'auto')"
                        class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors border {{ $selectedProvider === 'auto' ? 'bg-mana text-white border-mana' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border-stone-warm' }}"
                    >
                        Auto Detect (Otomatis)
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedProvider', 'otakudesu')"
                        class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors border {{ $selectedProvider === 'otakudesu' ? 'bg-mana text-white border-mana' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border-stone-warm' }}"
                    >
                        Otakudesu
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedProvider', 'oploverz')"
                        class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors border {{ $selectedProvider === 'oploverz' ? 'bg-mana text-white border-mana' : 'bg-alabaster hover:bg-stone-warm/50 text-ink border-stone-warm' }}"
                    >
                        Oploverz
                    </button>
                </div>
            </div>

            <!-- Source URL Input -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink">URL Anime Sumber (Otakudesu / Oploverz)</label>
                <input
                    wire:model="sourceUrl"
                    type="url"
                    placeholder="Contoh: https://otakudesu.blog/anime/... atau https://oploverz.site/series/..."
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:border-mana transition-colors"
                    required
                />
                @error('sourceUrl') <span class="text-sm text-mana block">{{ $message }}</span> @enderror

                <!-- Quick Sample Links -->
                <div class="flex flex-wrap items-center gap-2 pt-1.5 text-sm text-ink-muted">
                    <span>Contoh URL Sumber:</span>
                    <button
                        type="button"
                        wire:click="$set('sourceUrl', 'https://otakudesu.blog/anime/sousou-no-frieren-sub-indo/')"
                        class="text-mana hover:text-mana-hover hover:underline font-medium transition-colors"
                    >
                        Otakudesu (Frieren)
                    </button>
                    <span class="text-stone-warm">&bull;</span>
                    <button
                        type="button"
                        wire:click="$set('sourceUrl', 'https://oploverz.site/series/frieren-beyond-journeys-end-s1')"
                        class="text-mana hover:text-mana-hover hover:underline font-medium transition-colors"
                    >
                        Oploverz (Frieren S1)
                    </button>
                </div>
            </div>

            <!-- Direct Execution Toggle & Submit Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <label class="inline-flex items-center space-x-2 text-sm text-ink cursor-pointer">
                    <input
                        wire:model="syncMirrorsNow"
                        type="checkbox"
                        class="rounded border-stone-warm text-mana focus:ring-mana"
                    />
                    <span>Ekstrak streaming mirrors dan link download secara langsung (Sinkron)</span>
                </label>

                <button
                    type="submit"
                    wire:target="importNow"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 bg-mana hover:bg-mana-hover disabled:opacity-60 disabled:cursor-not-allowed text-white px-7 py-3 rounded-full text-sm font-semibold transition-all shrink-0 whitespace-nowrap min-w-[220px]"
                >
                    <!-- Normal State Icon -->
                    <span wire:target="importNow" wire:loading.remove class="inline-flex items-center justify-center shrink-0">
                        <x-lucide-play class="w-4 h-4 fill-current shrink-0" />
                    </span>
                    <!-- Loading State Icon -->
                    <span wire:target="importNow" wire:loading.inline-flex class="items-center justify-center shrink-0">
                        <x-lucide-loader-2 class="w-4 h-4 animate-spin shrink-0" />
                    </span>

                    <!-- Label -->
                    <span wire:target="importNow" wire:loading.remove>Import &amp; Scrape Sekarang</span>
                    <span wire:target="importNow" wire:loading.inline-block>Memproses Ekstraksi...</span>
                </button>
            </div>
        </form>

        <!-- Live Scraping Monitor & Execution Console -->
        <div
            x-show="isRunning || hasLogs"
            x-cloak
            class="mt-6 pt-6 border-t border-stone-warm space-y-4"
        >
            <!-- Console Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-alabaster/70 p-4 rounded-lg border border-stone-warm">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 rounded-md bg-surface border border-stone-warm text-mana shrink-0">
                        <x-lucide-terminal class="w-5 h-5 shrink-0" />
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-serif font-semibold text-base text-ink">Live Scraping Execution Console</h3>
                            <span
                                class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-sm font-medium border"
                                :class="isRunning ? 'bg-mana/10 text-mana border-mana/40 animate-pulse' : 'bg-surface text-ink border-stone-warm'"
                            >
                                <span class="w-2 h-2 rounded-full" :class="isRunning ? 'bg-mana' : 'bg-stone-warm'"></span>
                                <span x-text="isRunning ? 'Sedang Mengekstrak...' : 'Proses Selesai'"></span>
                            </span>
                        </div>
                        <p class="text-sm text-ink-muted mt-0.5">
                            <span x-show="isRunning" x-text="clientStep"></span>
                            <span x-show="!isRunning">{{ $currentStep ?: 'Menunggu inisialisasi proses...' }}</span>
                        </p>
                    </div>
                </div>

                <!-- Stopwatch & Metrics -->
                <div class="flex items-center space-x-4 shrink-0">
                    <div class="text-right">
                        <span class="block text-sm text-ink-muted font-medium">Waktu Eksekusi</span>
                        <div class="font-mono text-base font-bold text-ink flex items-center space-x-1.5 justify-end">
                            <x-lucide-clock class="w-4 h-4 text-mana" />
                            <span x-show="isRunning" x-text="elapsed + 's'">0.0s</span>
                            <span x-show="!isRunning">{{ $executionDuration ? $executionDuration . 's' : '0.0s' }}</span>
                        </div>
                    </div>

                    <div class="h-8 w-px bg-stone-warm"></div>

                    <div class="flex items-center space-x-2">
                        <button
                            type="button"
                            @click="copyLogs()"
                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md border border-stone-warm bg-surface hover:bg-alabaster text-ink text-sm font-medium transition-colors"
                            title="Salin Log"
                        >
                            <x-lucide-copy class="w-4 h-4 shrink-0" />
                            <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                        </button>
                        <button
                            type="button"
                            @click="clearAll()"
                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md border border-stone-warm bg-surface hover:bg-alabaster text-ink-muted hover:text-ink text-sm font-medium transition-colors"
                            title="Bersihkan Log"
                        >
                            <x-lucide-trash-2 class="w-4 h-4 shrink-0" />
                            <span>Bersihkan</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-ink">Fase Ekstraksi Multi-Sumber</span>
                    <span class="font-mono font-semibold text-mana">
                        <span x-show="isRunning" x-text="clientProgress + '%'"></span>
                        <span x-show="!isRunning">{{ $progressPercent }}%</span>
                    </span>
                </div>
                <div class="w-full bg-alabaster rounded-full h-3 border border-stone-warm overflow-hidden">
                    <div
                        class="bg-mana h-full rounded-full transition-all duration-300 ease-out"
                        :style="isRunning ? 'width: ' + clientProgress + '%' : 'width: {{ $progressPercent }}%'"
                    ></div>
                </div>
            </div>

            <!-- Console Stream Box -->
            <div class="bg-surface rounded-lg border border-stone-warm p-4 space-y-2 max-h-72 overflow-y-auto" id="scraper-console-content">
                <div wire:stream="scraper-console-stream" class="space-y-1">
                    @forelse ($executionLogs as $log)
                        <div class="flex items-start gap-3 py-1.5 font-mono text-sm border-b border-stone-warm/30 last:border-b-0">
                            <span class="text-ink-muted shrink-0">{{ $log['time'] }}</span>
                            <span class="px-2 py-0.5 rounded text-mana bg-alabaster font-semibold shrink-0">
                                [{{ $log['tag'] }}]
                            </span>
                            <span class="text-ink break-all">{{ $log['message'] }}</span>
                        </div>
                    @empty
                        <div x-show="!isRunning" class="py-2 text-sm text-ink-muted font-mono">
                            Belum ada log eksekusi. Tekan tombol 'Import &amp; Scrape Sekarang' di atas untuk memulai.
                        </div>
                        <div x-show="isRunning" class="flex items-center space-x-2 py-2 text-sm text-ink-muted font-mono">
                            <x-lucide-loader-2 class="w-4 h-4 animate-spin text-mana" />
                            <span x-text="clientStep"></span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Preview of Imported Anime -->
        @if ($importedAnime)
            <div class="mt-6 pt-6 border-t border-stone-warm bg-parchment p-5 rounded-lg space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <img src="{{ $importedAnime->poster_url }}" alt="" referrerpolicy="no-referrer" class="w-16 h-24 object-cover rounded border border-stone-warm bg-alabaster" onerror="this.onerror=null; this.src='https://placehold.co/120x180/efece6/64748b?text=Poster';" />
                        <div>
                            <h3 class="text-base font-semibold text-ink">{{ $importedAnime->title }}</h3>
                            <p class="text-sm text-ink-muted">
                                Studio: {{ $importedAnime->studio?->name ?? '-' }} &middot;
                                Skor: {{ number_format((float) $importedAnime->rating_score, 2) }} &middot;
                                {{ $importedAnime->episodes()->count() }} Episode
                            </p>
                            <p class="text-sm text-ink-muted mt-1 line-clamp-1 max-w-xl">
                                {{ $importedAnime->synopsis }}
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <a
                            href="{{ url('/admin/animes/' . $importedAnime->id . '/edit') }}"
                            class="inline-flex items-center space-x-2 bg-mana hover:bg-mana-hover text-white px-5 py-2.5 rounded-full text-sm font-semibold transition-colors"
                        >
                            <x-lucide-pencil class="w-4 h-4 shrink-0" />
                            <span>Isi Metadata</span>
                            <x-lucide-arrow-right class="w-4 h-4 shrink-0" />
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <!-- PANEL 2: Auto-Sync Ongoing Monitor -->
    <section class="bg-surface border border-stone-warm rounded-lg p-6 space-y-5">
        <div class="flex items-center justify-between pb-4 border-b border-stone-warm">
            <div>
                <h2 class="font-serif font-semibold text-lg text-ink">Auto-Sync Ongoing Monitor</h2>
                <p class="text-sm text-ink-muted mt-0.5">Daftar anime Ongoing yang terhubung dengan tautan scraping sumber.</p>
            </div>
            <span class="text-sm text-ink-muted font-medium">{{ $ongoingSources->count() }} Sumber Aktif</span>
        </div>

        @if (session()->has('ongoing_message'))
            <div class="p-3 rounded border border-mana bg-surface text-sm text-ink">
                {{ session('ongoing_message') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-warm text-ink-muted">
                        <th class="py-3 px-3 font-medium">Judul Anime</th>
                        <th class="py-3 px-3 font-medium">Provider</th>
                        <th class="py-3 px-3 font-medium">Episode Terakhir</th>
                        <th class="py-3 px-3 font-medium">Terakhir Disinkronkan</th>
                        <th class="py-3 px-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-warm">
                    @forelse ($ongoingSources as $source)
                        <tr class="hover:bg-alabaster/50 transition-colors">
                            <td class="py-3 px-3 font-medium text-ink">
                                <a href="{{ url('/anime/' . $source->anime->slug) }}" target="_blank" class="hover:text-mana transition-colors">
                                    {{ $source->anime->title }}
                                </a>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-0.5 rounded-full text-sm font-medium bg-alabaster border border-stone-warm text-ink capitalize">
                                    {{ $source->provider_name }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-ink-muted">
                                Ep {{ $source->last_scraped_episode ?? $source->anime->episodes()->count() }}
                            </td>
                            <td class="py-3 px-3 text-ink-muted">
                                {{ $source->last_synced_at ? $source->last_synced_at->diffForHumans() : 'Belum pernah' }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button
                                    wire:click="syncOngoingSource({{ $source->anime_id }}, '{{ $source->source_anime_url }}', '{{ $source->provider_name }}')"
                                    type="button"
                                    class="text-sm text-mana hover:underline font-medium"
                                >
                                    Cek Episode Baru
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-ink-muted">
                                Belum ada sumber anime Ongoing yang terdaftar. Impor anime Ongoing terlebih dahulu di atas!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- PANEL 3: Real-Time Scraper Logs -->
    <section @if (($pendingJobsCount ?? 0) > 0) wire:poll.10s @endif class="bg-surface border border-stone-warm rounded-lg p-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-stone-warm">
            <div>
                <h2 class="font-serif font-semibold text-lg text-ink">Real-Time Scraper Logs</h2>
                <p class="text-sm text-ink-muted mt-0.5">Memantau status antrean job scraping, response time, dan jumlah item yang diekstrak.</p>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                @if (($pendingJobsCount ?? 0) > 0)
                    <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-sm font-medium bg-alabaster border border-mana text-mana">
                        <span class="w-2 h-2 rounded-full bg-mana animate-pulse"></span>
                        <span>Auto-Poll Aktif ({{ $pendingJobsCount }} Job)</span>
                    </span>
                @else
                    <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-sm font-medium bg-alabaster border border-stone-warm text-ink-muted">
                        <span class="w-2 h-2 rounded-full bg-stone-warm"></span>
                        <span>Standby</span>
                    </span>
                @endif

                <button
                    type="button"
                    wire:click="$refresh"
                    class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-sm font-medium bg-alabaster hover:bg-stone-warm/50 border border-stone-warm text-ink transition-colors"
                    title="Segarkan Log"
                >
                    <x-lucide-refresh-cw class="w-3.5 h-3.5 shrink-0 text-ink-muted" />
                    <span>Segarkan</span>
                </button>

                <span class="text-sm text-ink-muted font-medium">10 Aktivitas Terbaru</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-warm text-ink-muted">
                        <th class="py-3 px-3 font-medium">Waktu</th>
                        <th class="py-3 px-3 font-medium">Provider</th>
                        <th class="py-3 px-3 font-medium">Aksi</th>
                        <th class="py-3 px-3 font-medium">Status</th>
                        <th class="py-3 px-3 font-medium">Item</th>
                        <th class="py-3 px-3 font-medium">Latency</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-warm">
                    @forelse ($recentLogs as $log)
                        <tr class="hover:bg-alabaster/50 transition-colors">
                            <td class="py-3 px-3 text-ink-muted whitespace-nowrap">{{ $log->created_at->format('H:i:s') }}</td>
                            <td class="py-3 px-3 font-semibold text-ink uppercase">{{ $log->provider_name }}</td>
                            <td class="py-3 px-3 text-ink-muted">{{ $log->action }}</td>
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-0.5 rounded-full text-sm font-medium {{ $log->status === 'success' ? 'bg-alabaster text-ink border border-stone-warm' : 'bg-stone-warm text-ink' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-ink">{{ $log->items_processed }}</td>
                            <td class="py-3 px-3 text-ink-muted">{{ $log->response_time_ms ? $log->response_time_ms . ' ms' : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-ink-muted">
                                Belum ada log aktivitas bot scraper.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
