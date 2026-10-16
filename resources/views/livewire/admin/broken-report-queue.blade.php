<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-stone-warm">
        <div>
            <h1 class="text-2xl font-serif font-semibold text-ink">Antrean Laporan Video Rusak</h1>
            <p class="text-sm text-ink-muted mt-1">Daftar keluhan pengguna terkait server mirror yang mati atau iframe error.</p>
        </div>

        <select
            wire:model.live="statusFilter"
            class="bg-surface border border-stone-warm rounded-md px-3.5 py-2 text-sm text-ink focus:outline-none focus:border-mana"
        >
            <option value="pending">Menunggu Perbaikan (Pending)</option>
            <option value="fixed">Sudah Diperbaiki (Fixed)</option>
            <option value="ignored">Diabaikan (Ignored)</option>
            <option value="">Semua Laporan</option>
        </select>
    </div>

    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-surface border border-mana text-sm text-ink">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-surface border border-stone-warm rounded-lg overflow-x-auto min-h-[360px] pb-12">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-stone-warm text-ink-muted bg-alabaster/40">
                    <th class="py-3 px-4 font-medium">Waktu</th>
                    <th class="py-3 px-4 font-medium">Anime &amp; Episode</th>
                    <th class="py-3 px-4 font-medium">Server Dilaporkan</th>
                    <th class="py-3 px-4 font-medium">Keterangan</th>
                    <th class="py-3 px-4 font-medium">Status</th>
                    <th class="py-3 px-4 font-medium text-right">Aksi Cepat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-warm">
                @forelse ($reports as $report)
                    <tr class="hover:bg-alabaster/30 transition-colors">
                        <td class="py-3 px-4 text-ink-muted whitespace-nowrap">{{ $report->created_at->diffForHumans() }}</td>
                        <td class="py-3 px-4 font-medium text-ink">
                            <a href="{{ url('/nonton/' . $report->episode->slug) }}" target="_blank" class="hover:text-mana transition-colors">
                                {{ $report->episode->anime->title }} - Ep {{ $report->episode->episode_number }}
                            </a>
                        </td>
                        <td class="py-3 px-4 text-ink-muted">
                            {{ $report->mirror?->server_name ?? 'Default Server' }} ({{ $report->mirror?->quality->value ?? 'Auto' }})
                        </td>
                        <td class="py-3 px-4 text-ink-muted max-w-xs truncate">
                            {{ $report->issue_description ?? 'Laporan otomatis player' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-sm font-medium {{ $report->status === 'fixed' ? 'bg-alabaster text-ink border border-stone-warm' : 'bg-surface text-mana border border-mana' }}">
                                {{ $report->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
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
                                    @if ($report->status === 'pending')
                                        <button
                                            wire:click="reScrapeEpisode({{ $report->id }})"
                                            @click="open = false"
                                            type="button"
                                            class="w-full flex items-center space-x-2.5 px-3.5 py-2 text-sm leading-5 text-mana hover:bg-alabaster font-medium transition-colors text-left"
                                        >
                                            <x-lucide-refresh-cw class="w-5 h-5 shrink-0 text-mana" />
                                            <span>1-Click Re-Scrape</span>
                                        </button>

                                        <button
                                            wire:click="markResolved({{ $report->id }}, 'ignored')"
                                            @click="open = false"
                                            type="button"
                                            class="w-full flex items-center space-x-2.5 px-3.5 py-2 text-sm leading-5 text-ink-muted hover:bg-alabaster hover:text-ink transition-colors text-left"
                                        >
                                            <x-lucide-ban class="w-5 h-5 shrink-0 text-ink-muted" />
                                            <span>Abaikan Laporan</span>
                                        </button>
                                    @endif

                                    <a
                                        href="{{ url('/nonton/' . $report->episode->slug) }}"
                                        target="_blank"
                                        class="flex items-center space-x-2.5 px-3.5 py-2 text-sm leading-5 text-ink hover:bg-alabaster hover:text-mana transition-colors"
                                    >
                                        <x-lucide-play class="w-5 h-5 shrink-0 text-ink-muted" />
                                        <span>Buka Player Web</span>
                                    </a>

                                    <a
                                        href="{{ url('/admin/episodes/' . $report->episode->id) }}"
                                        class="flex items-center space-x-2.5 px-3.5 py-2 text-sm leading-5 text-ink hover:bg-alabaster hover:text-mana transition-colors"
                                    >
                                        <x-lucide-server class="w-5 h-5 shrink-0 text-ink-muted" />
                                        <span>Kelola Server Mirror</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-ink-muted">
                            Tidak ada laporan video rusak pada filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $reports->links() }}
    </div>
</div>
