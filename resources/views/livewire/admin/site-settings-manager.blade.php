<div class="space-y-8">
    <div class="pb-6 border-b border-stone-warm">
        <h1 class="text-2xl font-serif font-semibold text-ink">Pengaturan Sistem &amp; Monetisasi</h1>
        <p class="text-sm text-ink-muted mt-1">Konfigurasi dinamis domain scraper, SEO default, dan visibilitas slot iklan.</p>
    </div>

    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-surface border border-mana text-sm text-ink">
            {{ session('message') }}
        </div>
    @endif

    <!-- Scraper Base URL & Site Identity Form -->
    <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-6">
        <h2 class="font-serif font-semibold text-lg text-ink">Konfigurasi Scraper &amp; Identitas Situs</h2>

        <form wire:submit="saveSettings" class="space-y-4 max-w-2xl">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink">Base URL Sumber Otakudesu</label>
                <input
                    wire:model="scraperBaseUrl"
                    type="url"
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:border-mana"
                    required
                />
                <span class="block text-sm text-ink-muted">Domain mirror Otakudesu (Default: https://otakudesu.blog).</span>
                @error('scraperBaseUrl') <span class="text-sm text-mana block">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink">Base URL Sumber Oploverz</label>
                <input
                    wire:model="scraperOploverzBaseUrl"
                    type="url"
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:border-mana"
                    required
                />
                <span class="block text-sm text-ink-muted">Domain sumber Oploverz (Default: https://oploverz.site). Menggunakan SvelteKit JSON devalue endpoint.</span>
                @error('scraperOploverzBaseUrl') <span class="text-sm text-mana block">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink">Default Scraper Mode</label>
                <select
                    wire:model="defaultScraperProvider"
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:border-mana"
                >
                    <option value="auto">Auto-Detect Domain URL (Rekomendasi)</option>
                    <option value="otakudesu">Otakudesu (HTML Parser)</option>
                    <option value="oploverz">Oploverz (SvelteKit API)</option>
                </select>
                <span class="block text-sm text-ink-muted">Menentukan parser scraper yang digunakan saat mengimpor atau memeriksa serial anime.</span>
                @error('defaultScraperProvider') <span class="text-sm text-mana block">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink">Judul Situs (Default SEO Title)</label>
                <input
                    wire:model="siteTitle"
                    type="text"
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:border-mana"
                    required
                />
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink">Tagline Situs</label>
                <input
                    wire:model="siteTagline"
                    type="text"
                    class="w-full bg-parchment border border-stone-warm rounded-md px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:border-mana"
                    required
                />
            </div>

            <button
                type="submit"
                class="bg-mana hover:bg-mana-hover text-white px-6 py-2.5 rounded-full text-sm font-semibold transition-colors"
            >
                Simpan Perubahan
            </button>
        </form>
    </div>

    <!-- Ad Slots Manager -->
    <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-4">
        <div>
            <h2 class="font-serif font-semibold text-lg text-ink">Manajemen Slot Iklan Banner</h2>
            <p class="text-sm text-ink-muted mt-0.5">Semua slot iklan dirancang tidak mengganggu white space dan estetika Millishion Light Mode.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-warm text-ink-muted">
                        <th class="py-3 px-3 font-medium">Slot Key</th>
                        <th class="py-3 px-3 font-medium">Nama Slot</th>
                        <th class="py-3 px-3 font-medium text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-warm">
                    @forelse ($adSlots as $slot)
                        <tr class="hover:bg-alabaster/30 transition-colors">
                            <td class="py-3 px-3 font-mono text-sm text-mana">{{ $slot->key }}</td>
                            <td class="py-3 px-3 font-semibold text-ink">{{ $slot->name }}</td>
                            <td class="py-3 px-3 text-center">
                                <button
                                    wire:click="toggleAdSlot({{ $slot->id }})"
                                    type="button"
                                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $slot->is_active ? 'bg-mana text-white' : 'bg-alabaster text-ink-muted border border-stone-warm' }}"
                                >
                                    {{ $slot->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-ink-muted">
                                Belum ada slot iklan yang dikonfigurasi di database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
