<footer class="bg-alabaster border-t border-stone-warm mt-auto">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-14 lg:py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
            <!-- Brand Column -->
            <div class="md:col-span-2 space-y-4">
                <a href="{{ url('/') }}" class="inline-block transition-transform hover:scale-[1.02] duration-150">
                    <img
                        src="{{ asset('assets/logo-tofunime.png') }}"
                        alt="TOFUNIME"
                        class="h-12 w-auto object-contain"
                    />
                </a>
                <p class="text-sm text-ink-muted leading-relaxed max-w-lg">
                    Agregator anime modern bertema Millishion Light Mode yang menghadirkan pengalaman streaming dan unduhan episode Subtitle Indonesia berkualitas tinggi, cepat, bersih, dan bebas dari iklan agresif.
                </p>
                <div class="pt-2 text-sm text-ink-muted">
                    Metadata resmi disinkronkan secara cerdas via Jikan API v4 dan server mirror multi-resolusi.
                </div>
            </div>

            <!-- Quick Directory -->
            <div>
                <h4 class="font-serif font-semibold text-base text-ink mb-4">Navigasi Utama</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ url('/') }}" class="text-ink-muted hover:text-mana transition-colors">Beranda</a></li>
                    <li><a href="{{ url('/katalog') }}" class="text-ink-muted hover:text-mana transition-colors">Katalog Lengkap</a></li>
                    <li><a href="{{ url('/ongoing') }}" class="text-ink-muted hover:text-mana transition-colors">Anime Sedang Tayang</a></li>
                    <li><a href="{{ url('/jadwal-rilis') }}" class="text-ink-muted hover:text-mana transition-colors">Jadwal Rilis Mingguan</a></li>
                </ul>
            </div>

            <!-- Popular Genres & Tags -->
            <div>
                <h4 class="font-serif font-semibold text-base text-ink mb-4">Eksplorasi Populer</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ url('/tag/isekai') }}" class="text-ink-muted hover:text-mana transition-colors">Isekai</a></li>
                    <li><a href="{{ url('/genre/fantasy') }}" class="text-ink-muted hover:text-mana transition-colors">Fantasy</a></li>
                    <li><a href="{{ url('/genre/action') }}" class="text-ink-muted hover:text-mana transition-colors">Action</a></li>
                    <li><a href="{{ url('/tag/reincarnation') }}" class="text-ink-muted hover:text-mana transition-colors">Reincarnation</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-stone-warm flex flex-col sm:flex-row items-center justify-between text-sm text-ink-muted">
            <p>&copy; {{ date('Y') }} Tofunime. Seluruh hak cipta dilindungi undang-undang.</p>
            <p class="mt-2 sm:mt-0 font-medium">100% Strict Light Theme &middot; Terinspirasi Kota Suci Millishion</p>
        </div>
    </div>
</footer>
