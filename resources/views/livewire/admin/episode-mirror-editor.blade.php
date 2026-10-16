<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-stone-warm">
        <div>
            <div class="flex items-center space-x-2 text-sm text-ink-muted">
                <a href="{{ url('/admin/animes') }}" class="hover:text-mana transition-colors">Katalog Anime</a>
                <span>/</span>
                <span>{{ $episode->anime->title }}</span>
                <span>/</span>
                <span class="text-ink font-medium">Ep {{ $episode->episode_number }}</span>
            </div>
            <h1 class="text-2xl font-serif font-semibold text-ink mt-1">
                Kelola Server Video Mirror (Ep {{ $episode->episode_number }})
            </h1>
        </div>

        <div class="flex items-center space-x-3">
            <button
                wire:click="reScrapeNow"
                type="button"
                class="bg-mana hover:bg-mana-hover text-white px-5 py-2.5 rounded-full text-sm font-semibold transition-colors"
            >
                1-Click Re-Scrape Episode
            </button>
            <a
                href="{{ url('/nonton/' . $episode->slug) }}"
                target="_blank"
                class="bg-surface hover:bg-alabaster text-ink border border-stone-warm px-4 py-2.5 rounded-full text-sm font-medium transition-colors"
            >
                Preview di Web &rarr;
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-surface border border-mana text-sm text-ink">
            {{ session('message') }}
        </div>
    @endif

    <!-- Mirror List Table -->
    <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-4">
        <h2 class="font-serif font-semibold text-lg text-ink">Daftar Streaming Server Mirror</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-warm text-ink-muted">
                        <th class="py-3 px-3 font-medium">Nama Server</th>
                        <th class="py-3 px-3 font-medium">Resolusi</th>
                        <th class="py-3 px-3 font-medium">Embed URL</th>
                        <th class="py-3 px-3 font-medium text-center">Status</th>
                        <th class="py-3 px-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-warm">
                    @forelse ($episode->mirrors as $mirror)
                        <tr class="hover:bg-alabaster/30 transition-colors">
                            <td class="py-3 px-3 font-semibold text-ink">{{ $mirror->server_name }}</td>
                            <td class="py-3 px-3 text-ink-muted">{{ $mirror->quality->value }}</td>
                            <td class="py-3 px-3 text-ink-muted max-w-md truncate">{{ $mirror->embed_url }}</td>
                            <td class="py-3 px-3 text-center">
                                <button
                                    wire:click="toggleMirrorActive({{ $mirror->id }})"
                                    type="button"
                                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $mirror->is_active ? 'bg-alabaster text-ink border border-stone-warm' : 'bg-stone-warm text-ink-muted' }}"
                                >
                                    {{ $mirror->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button
                                    wire:confirm="Yakin ingin menghapus mirror ini?"
                                    wire:click="deleteMirror({{ $mirror->id }})"
                                    type="button"
                                    class="text-ink-muted hover:text-ink text-sm font-medium"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-ink-muted">
                                Belum ada server mirror untuk episode ini. Klik Re-Scrape di atas atau tambahkan manual di bawah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Manual Mirror Form -->
    <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-4">
        <h2 class="font-serif font-semibold text-lg text-ink">Tambah Server Mirror Manual</h2>

        <form wire:submit="addMirror" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
            <div class="sm:col-span-3 space-y-1">
                <label class="block text-sm font-medium text-ink">Nama Server</label>
                <input
                    wire:model="newServerName"
                    type="text"
                    placeholder="Contoh: Mega, Vidhide"
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3 py-2 text-sm text-ink focus:outline-none focus:border-mana"
                    required
                />
            </div>

            <div class="sm:col-span-2 space-y-1">
                <label class="block text-sm font-medium text-ink">Resolusi</label>
                <select
                    wire:model="newQuality"
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3 py-2 text-sm text-ink focus:outline-none focus:border-mana"
                >
                    <option value="360p">360p</option>
                    <option value="480p">480p</option>
                    <option value="720p">720p</option>
                    <option value="1080p">1080p</option>
                </select>
            </div>

            <div class="sm:col-span-5 space-y-1">
                <label class="block text-sm font-medium text-ink">Embed URL (Iframe Source)</label>
                <input
                    wire:model="newEmbedUrl"
                    type="url"
                    placeholder="https://server.com/embed/..."
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3 py-2 text-sm text-ink focus:outline-none focus:border-mana"
                    required
                />
            </div>

            <div class="sm:col-span-2">
                <button
                    type="submit"
                    class="w-full bg-mana hover:bg-mana-hover text-white px-4 py-2 rounded-md text-sm font-semibold transition-colors"
                >
                    + Simpan
                </button>
            </div>
        </form>
    </div>
</div>
