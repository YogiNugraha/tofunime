<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-stone-warm">
        <div>
            <h1 class="text-2xl lg:text-3xl font-serif font-semibold text-ink tracking-tight">
                {{ $isEditing ? 'Edit Anime: ' . ($title ?: 'Tanpa Judul') : 'Tambah Anime Baru' }}
            </h1>
        </div>

        <div class="flex items-center space-x-3">
            
        </div>
    </div>

    <!-- Flash Notification -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 p-4 rounded-xl text-sm flex items-center justify-between" style="padding: 1rem 1.25rem;">
            <div class="flex items-center space-x-2.5">
                <x-lucide-check-circle-2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span class="font-medium">{{ session('message') }}</span>
            </div>
        </div>
    @endif

    <!-- 1-Click Auto-Fill Card -->
    <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-4">
        <div class="flex items-center space-x-2.5 text-ink font-semibold text-base">
            <x-lucide-sparkles class="w-5 h-5 text-mana shrink-0" />
            <span>Auto-Fill Konten Anime (MyAnimeList / AniList)</span>
        </div>

        <p class="text-sm text-ink-muted">
            Masukkan URL MyAnimeList (contoh: <code class="bg-alabaster px-1.5 py-0.5 rounded text-ink border border-stone-warm">https://myanimelist.net/anime/52991</code>), ID numerik (<code class="bg-alabaster px-1.5 py-0.5 rounded text-ink border border-stone-warm">52991</code>), atau judul anime untuk mengisi data secara otomatis.
        </p>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <x-lucide-link class="w-4 h-4 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input
                    wire:model="malQueryOrUrl"
                    wire:keydown.enter="fetchFromMal"
                    type="text"
                    placeholder="Masukkan URL MyAnimeList, MAL ID, atau judul anime..."
                    class="w-full pl-10 pr-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink placeholder:text-ink-muted focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors"
                />
            </div>

            <button
                wire:click="fetchFromMal"
                wire:loading.attr="disabled"
                type="button"
                class="inline-flex items-center justify-center space-x-2 bg-mana hover:bg-mana-hover text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50 shrink-0"
            >
                <span wire:loading.remove wire:target="fetchFromMal">
                    <x-lucide-download class="w-4 h-4 shrink-0" />
                </span>
                <span wire:loading wire:target="fetchFromMal">
                    <x-lucide-loader-2 class="w-4 h-4 shrink-0 animate-spin" />
                </span>
                <span>Ambil Data</span>
            </button>
        </div>

        @if (!empty($autoFillMessage))
            <div class="p-4 rounded-xl text-sm flex items-start space-x-3 {{ $autoFillType === 'success' ? 'bg-emerald-50 text-emerald-900 border border-emerald-300' : 'bg-red-50 text-red-900 border border-red-200' }}" style="padding: 1rem 1.25rem;">
                @if ($autoFillType === 'success')
                    <x-lucide-check-circle-2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                @else
                    <x-lucide-alert-circle class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                @endif
                <span class="font-medium leading-relaxed">{{ $autoFillMessage }}</span>
            </div>
        @endif
    </div>

    <!-- Main Anime Form Grid -->
    <form wire:submit.prevent="save" novalidate class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left Column: Primary Content (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Informasi Dasar -->
            <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-5">
                <h2 class="font-serif font-semibold text-lg text-ink border-b border-stone-warm pb-3">Informasi Dasar</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Judul Utama <span class="text-red-500">*</span></label>
                        <input
                            wire:model.live="title"
                            type="text"
                            class="w-full px-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors"
                        />
                        @error('title') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Slug URL <span class="text-red-500">*</span></label>
                        <input
                            wire:model="slug"
                            type="text"
                            class="w-full px-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors"
                        />
                        @error('slug') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Judul Bahasa Inggris</label>
                            <input
                                wire:model="english_title"
                                type="text"
                                class="w-full px-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ink mb-1.5">Judul Asli (Jepang / Kanji)</label>
                            <input
                                wire:model="japanese_title"
                                type="text"
                                class="w-full px-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Judul Sinonim / Alternatif (Pisahkan dengan koma)</label>
                        <input
                            wire:model="synonyms_text"
                            type="text"
                            placeholder="Contoh: Frieren, SnF, Frieren the Slayer"
                            class="w-full px-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors"
                        />
                    </div>
                </div>
            </div>

            <!-- Sinopsis & Background -->
            <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-5">
                <h2 class="font-serif font-semibold text-lg text-ink border-b border-stone-warm pb-3">Sinopsis & Deskripsi</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Sinopsis Cerita</label>
                        <textarea
                            wire:model="synopsis"
                            rows="6"
                            class="w-full px-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors leading-relaxed"
                            placeholder="Tulis sinopsis lengkap dalam Bahasa Indonesia..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">Informasi Latar Belakang (Trivia / Background)</label>
                        <textarea
                            wire:model="background_info"
                            rows="3"
                            class="w-full px-4 py-2.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana focus:ring-1 focus:ring-mana transition-colors leading-relaxed"
                            placeholder="Catatan tambahan mengenai adaptasi atau penghargaan..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Kategori, Taksonomi & Kreator -->
            <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-6">
                <h2 class="font-serif font-semibold text-lg text-ink border-b border-stone-warm pb-3">Kategori, Taksonomi & Kreator</h2>

                <!-- Genre Tags -->
                <div class="space-y-2.5">
                    <label class="block text-sm font-medium text-ink">Genre</label>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($selectedGenres as $genre)
                            <span class="inline-flex items-center space-x-1.5 bg-alabaster text-ink border border-stone-warm px-3 py-1 rounded-full text-sm font-medium">
                                <span>{{ $genre }}</span>
                                <button
                                    wire:click="removeGenre('{{ $genre }}')"
                                    type="button"
                                    class="text-ink-muted hover:text-ink transition-colors"
                                >
                                    <x-lucide-x class="w-3.5 h-3.5" />
                                </button>
                            </span>
                        @endforeach
                    </div>

                    <div class="flex items-center space-x-2 pt-1">
                        <input
                            wire:model="newGenre"
                            wire:keydown.enter.prevent="addGenre"
                            type="text"
                            placeholder="Ketik genre lalu tekan Enter..."
                            class="px-3 py-1.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana"
                        />
                        <button
                            wire:click="addGenre"
                            type="button"
                            class="bg-surface hover:bg-alabaster text-ink border border-stone-warm px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        >
                            + Tambah
                        </button>
                    </div>
                </div>

                <!-- Theme / Tag Tags -->
                <div class="space-y-2.5">
                    <label class="block text-sm font-medium text-ink">Tema & Tag Cerita</label>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($selectedThemes as $theme)
                            <span class="inline-flex items-center space-x-1.5 bg-alabaster text-ink border border-stone-warm px-3 py-1 rounded-full text-sm font-medium">
                                <span>{{ $theme }}</span>
                                <button
                                    wire:click="removeTheme('{{ $theme }}')"
                                    type="button"
                                    class="text-ink-muted hover:text-ink transition-colors"
                                >
                                    <x-lucide-x class="w-3.5 h-3.5" />
                                </button>
                            </span>
                        @endforeach
                    </div>

                    <div class="flex items-center space-x-2 pt-1">
                        <input
                            wire:model="newTheme"
                            wire:keydown.enter.prevent="addTheme"
                            type="text"
                            placeholder="Ketik tema (cth: Magic, Isekai) lalu tekan Enter..."
                            class="px-3 py-1.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana"
                        />
                        <button
                            wire:click="addTheme"
                            type="button"
                            class="bg-surface hover:bg-alabaster text-ink border border-stone-warm px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        >
                            + Tambah
                        </button>
                    </div>
                </div>

                <!-- Staff & Authors -->
                <div class="space-y-3 pt-2">
                    <label class="block text-sm font-medium text-ink">Staff, Pengarang & Sutradara</label>
                    
                    @if (!empty($staffList))
                        <div class="divide-y divide-stone-warm border border-stone-warm rounded-lg overflow-hidden bg-alabaster/30">
                            @foreach ($staffList as $idx => $st)
                                <div class="px-4 py-2.5 flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium text-ink">{{ $st['name'] }}</span>
                                        <span class="text-ink-muted">({{ $st['role'] }})</span>
                                    </div>
                                    <button
                                        wire:click="removeStaff({{ $idx }})"
                                        type="button"
                                        class="text-ink-muted hover:text-red-600 transition-colors p-1"
                                        title="Hapus"
                                    >
                                        <x-lucide-trash-2 class="w-4 h-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-1">
                        <input
                            wire:model="newStaffName"
                            type="text"
                            placeholder="Nama tokoh/kreator..."
                            class="px-3 py-1.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana flex-1"
                        />
                        <select
                            wire:model="newStaffRole"
                            class="px-3 py-1.5 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana"
                        >
                            <option value="Original Story">Original Story</option>
                            <option value="Director">Director</option>
                            <option value="Character Design">Character Design</option>
                            <option value="Series Composition">Series Composition</option>
                            <option value="Music">Music</option>
                        </select>
                        <button
                            wire:click="addStaff"
                            type="button"
                            class="bg-surface hover:bg-alabaster text-ink border border-stone-warm px-4 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        >
                            + Tambah Staff
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Media & Properties (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Media Poster & Banner -->
            <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-4">
                <h2 class="font-serif font-semibold text-lg text-ink border-b border-stone-warm pb-3">Cover & Media</h2>

                <div class="space-y-3">
                    <label class="block text-sm font-medium text-ink">Preview Poster</label>
                    <div class="aspect-[2/3] w-full rounded-lg overflow-hidden border border-stone-warm bg-alabaster flex items-center justify-center relative">
                        @if (!empty($poster_url))
                            <img
                                src="{{ $poster_url }}"
                                alt="Poster Preview"
                                class="w-full h-full object-cover"
                            />
                        @else
                            <div class="text-center p-4 text-ink-muted space-y-1">
                                <x-lucide-image class="w-8 h-8 mx-auto text-stone-warm" />
                                <span class="text-sm">Belum ada poster</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">URL Hotlink Poster</label>
                        <input
                            wire:model.live="poster_url"
                            type="text"
                            placeholder="https://..."
                            class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">URL Banner Lanskap</label>
                        <input
                            wire:model="banner_url"
                            type="text"
                            placeholder="https://..."
                            class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1.5">ID Trailer YouTube</label>
                        <input
                            wire:model="trailer_youtube_id"
                            type="text"
                            placeholder="Contoh: tR8YH0G67Rk"
                            class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-sm text-ink focus:outline-none focus:border-mana"
                        />
                    </div>
                </div>
            </div>

            <!-- Properti Anime -->
            <div class="bg-surface border border-stone-warm rounded-lg p-6 space-y-4">
                <h2 class="font-serif font-semibold text-lg text-ink border-b border-stone-warm pb-3">Properti & Statistik</h2>

                <div class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-ink mb-1">Tipe</label>
                            <select
                                wire:model="type"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            >
                                @foreach ($availableTypes as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-ink mb-1">Status</label>
                            <select
                                wire:model="status"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            >
                                @foreach ($availableStatuses as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-ink mb-1">Total Episode</label>
                            <input
                                wire:model="total_episodes"
                                type="number"
                                placeholder="28"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-ink mb-1">Durasi</label>
                            <input
                                wire:model="duration"
                                type="text"
                                placeholder="24 Menit"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-ink mb-1">Studio Animasi</label>
                        <input
                            wire:model="studio_name"
                            type="text"
                            placeholder="Madhouse"
                            class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-ink mb-1">Musim Rilis</label>
                            <input
                                wire:model="season_name"
                                type="text"
                                placeholder="Fall 2023"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-ink mb-1">Tahun Rilis</label>
                            <input
                                wire:model="release_year"
                                type="number"
                                placeholder="2023"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-ink mb-1">Hari Tayang Rutin (Ongoing)</label>
                        <select
                            wire:model="release_day"
                            class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                        >
                            <option value="">-- Pilih Hari --</option>
                            @foreach ($availableDays as $d)
                                <option value="{{ $d->value }}">{{ $d->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-ink mb-1">Skor Rating (0 - 10)</label>
                            <input
                                wire:model="rating_score"
                                type="text"
                                placeholder="9.10"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            />
                        </div>

                        <div>
                            <label class="block font-medium text-ink mb-1">MyAnimeList ID</label>
                            <input
                                wire:model="mal_id"
                                type="number"
                                placeholder="52991"
                                class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-ink mb-1">Sumber Asli (Source Material)</label>
                        <input
                            wire:model="source_material"
                            type="text"
                            placeholder="Manga, Light Novel, Original..."
                            class="w-full px-3 py-2 bg-alabaster/60 border border-stone-warm rounded-lg text-ink focus:outline-none focus:border-mana"
                        />
                    </div>

                    <!-- Highlight Flags -->
                    <div class="pt-3 border-t border-stone-warm space-y-2">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input
                                wire:model="is_featured"
                                type="checkbox"
                                class="rounded border-stone-warm text-mana focus:ring-mana"
                            />
                            <span class="text-ink font-medium">Jadikan Anime Pilihan (Featured)</span>
                        </label>

                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input
                                wire:model="is_recommended"
                                type="checkbox"
                                class="rounded border-stone-warm text-mana focus:ring-mana"
                            />
                            <span class="text-ink font-medium">Rekomendasikan di Halaman Utama</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save CTA Card -->
            <div class="bg-surface border border-stone-warm rounded-lg p-5 space-y-4">
                @if (!empty($saveMessage))
                    <div
                        class="p-4 rounded-xl text-sm flex items-start space-x-3 {{ $saveType === 'success' ? 'bg-emerald-50 text-emerald-900 border border-emerald-300' : 'bg-red-50 text-red-900 border border-red-200' }} shadow-xs"
                        style="padding: 1rem 1.25rem;"
                    >
                        @if ($saveType === 'success')
                            <x-lucide-check-circle-2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                        @else
                            <x-lucide-alert-circle class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                        @endif
                        <span class="font-medium leading-relaxed">{{ $saveMessage }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="p-4 rounded-xl text-sm bg-red-50 text-red-900 border border-red-200 space-y-2 shadow-xs"
                        style="padding: 1.25rem;"
                    >
                        <div class="flex items-center space-x-2.5 font-semibold text-red-800">
                            <x-lucide-alert-triangle class="w-5 h-5 text-red-600 shrink-0" />
                            <span>Terdapat data yang belum valid:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-red-700 pl-1 text-sm" style="margin-top: 0.5rem; padding-left: 0.75rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button
                    wire:click.prevent="save"
                    wire:loading.attr="disabled"
                    type="button"
                    class="w-full inline-flex items-center justify-center space-x-2 bg-mana hover:bg-mana-hover text-white px-5 py-3 rounded-lg text-sm font-semibold transition-colors shadow-sm disabled:opacity-50 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="save">
                        <x-lucide-check class="w-4 h-4 shrink-0" />
                    </span>
                    <span wire:loading wire:target="save">
                        <x-lucide-loader-2 class="w-4 h-4 shrink-0 animate-spin" />
                    </span>
                    <span wire:loading.remove wire:target="save">
                        {{ $isEditing ? 'Perbarui Anime' : 'Simpan Anime Baru' }}
                    </span>
                    <span wire:loading wire:target="save">
                        Menyimpan Perubahan...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
