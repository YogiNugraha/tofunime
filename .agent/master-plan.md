# Master Architecture Plan: TOFUNIME - Enterprise Anime Streaming Aggregator

## 1. Brand Identity & System Overview
- **Nama Aplikasi:** **Tofunime**
- **Konsep Visual:** Layout mengadaptasi struktur modern *Filmagnet* (Desktop-1 & Desktop-2) yang dikonversi menjadi **100% Strict Light Mode** bertema dunia fantasi *Mushoku Tensei* (Arsitektur Marmer Putih, Biru Langit/Air Kanal, dan Emas Klasik Kota Millishion).
- **Mesin Konten (Hybrid Aggregator):**
  1. **Metadata Engine (`[https://jikan.moe/](https://jikan.moe/)` - Jikan REST API v4):** Mengambil informasi resmi berupa Judul, Sinopsis, Genres, Themes/Tags (Isekai, Reincarnation, dll), Demographics, Authors/Original Creators, Studios, Producers, Skor, dan Poster HD.
  2. **Stream & Episode Scraper Engine (Contoh: `[https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/](https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/)`):** Mengambil daftar episode Sub Indo, membongkar enkripsi Base64 AJAX untuk mendapatkan URL `<iframe src="...">` dari berbagai server & resolusi (360p, 480p, 720p, 1080p), menarik link download (MP4/MKV/Batch), serta menyediakan *HTML Metadata Fallback* jika anime diambil murni dari web sumber.
- **Prinsip Penyimpanan:** Tofunime **TIDAK** menyimpan file video di server lokal. Semua pemutaran video dilakukan melalui *iframe embedding* dan *direct download links* yang tersimpan di MySQL.

## 2. Core Tech Stack
- **Framework:** Laravel 12.x (PHP 8.3+), `declare(strict_types=1);`
- **Reactive Frontend:** Livewire 3/4 + Alpine.js (Alpine.js adalah pasangan resmi Livewire untuk seluruh interaksi client-side UI seperti dropdown, modal, toggle mobile, tab switching; dilarang menggunakan script vanilla JS terpisah).
- **Icons:** Lucide Icons via `mallardduck/blade-lucide-icons` (`<x-lucide-[nama]>`). Dilarang inline `<svg>` mentah; tinggi icon wajib diselaraskan dengan line-height teks pendamping (`w-5 h-5 shrink-0` untuk `text-sm leading-5`, `w-6 h-6 shrink-0` untuk `text-base leading-6`).
- **Styling:** Tailwind CSS (Custom Design System: *Tofunime Millishion Light Theme*)
- **Database:** MySQL 8.0+ (dikelola via phpMyAdmin, menggunakan Full-Text Index & Composite Keys)
- **HTTP & Scraping:** Laravel Http Client (Guzzle), `symfony/dom-crawler`, `symfony/css-selector`

## 3. Struktur Direktori Modular (`app/`)
Agent WAJIB membangun struktur direktori secara rapi sebagai berikut:
- `app/Console/Commands/` -> `TofunimeScrapeOngoingCommand.php`, `TofunimeGrabAnimeCommand.php`, `TofunimeGenerateSitemapCommand.php`
- `app/Enums/` -> `AnimeStatus.php`, `AnimeType.php`, `TaxonomyType.php`, `VideoQuality.php`
- `app/Jobs/` -> `SyncJikanMetadataJob.php`, `ScrapeAnimeEpisodesJob.php`, `ScrapeEpisodeMirrorsJob.php`, `PingSearchEngineJob.php`
- `app/Livewire/Public/` -> `HomeIndex.php`, `AnimeCatalog.php`, `AnimeDetail.php`, `WatchPlayer.php`, `ScheduleBoard.php`, `GlobalSearch.php`
- `app/Livewire/Admin/` -> `DashboardOverview.php`, `HybridGrabberStudio.php`, `AnimeManager.php`, `EpisodeMirrorEditor.php`, `BrokenReportQueue.php`, `SiteSettingsManager.php`
- `app/Services/Jikan/` -> `JikanApiService.php`
- `app/Services/Scraper/Contracts/` -> `AnimeScraperInterface.php`
- `app/Services/Scraper/Adapters/` -> `OtakudesuScraperAdapter.php`, `SamehadakuScraperAdapter.php`
- `app/Services/Scraper/Decoders/` -> `OtakudesuAjaxDecoder.php`
- `app/Services/Scraper/` -> `HybridAnimeImporter.php`
- `app/Services/Seo/` -> `TofunimeSeoGenerator.php`, `SitemapShardingService.php`
- `app/Services/Cache/` -> `TofunimeCacheService.php`

## 4. Urutan Eksekusi Wajib (Execution Pipeline)
Agent wajib mengeksekusi proyek ini secara bertahap:
- **Tahap 1:** Buat Enums, 15 tabel Migrations (lengkap dengan relasi Author, Tag, Genre, Mirror), dan Eloquent Models sesuai `02-enterprise-database.md`.
- **Tahap 2:** Konfigurasi Tailwind CSS, Typography, Master Layout, dan Navbar **Tofunime** sesuai `01-ui-design-system.md`. Patuhi seluruh aturan **Anti-AI Slop**.
- **Tahap 3:** Bangun `JikanApiService`, `OtakudesuScraperAdapter`, `OtakudesuAjaxDecoder`, dan `HybridAnimeImporter` sesuai `03-advanced-scraper-engine.md`.
- **Tahap 4:** Bangun halaman publik (Home, Detail, Katalog, Jadwal) dan komponen Livewire `WatchPlayer` sesuai `04-player-and-monetization.md`.
- **Tahap 5:** Terapkan SEO dinamis Tofunime, Schema.org JSON-LD, Sitemap, dan Caching sesuai `05-seo-and-performance.md`.
- **Tahap 6:** Bangun CMS Dashboard Admin Tofunime dengan arsitektur linear: *Single Door Ingestion* melalui Hybrid Grabber Studio (`/admin/scraper`), Katalog Master dengan Rich Filtering (`/admin/animes`), Editor Metadata (`/admin/animes/{id}/edit`), dan Editor Episode/Mirror (`/admin/episodes/{episode}`) dengan gaya Flat No-Shadow sesuai `06-cms-and-user-ecosystem.md` dan `01-ui-design-system.md`.

---

## 5. Status Milestone Pengembangan

### ✅ FASE 1: Core Foundation & Aggregator (SELESAI)
- **Database & Architecture:** 15 tabel migrasi, model, factory, seeder, dan queue worker aktif.
- **Design System:** Tofunime Millishion Light Mode (flat, border-based, zero shadow, anti-AI slop).
- **Hybrid Scraper Engine:** Multi-driver Otakudesu & Oploverz dengan Livewire scraping log console stream dan tombol cepat "Isi Metadata".
- **Secure Stream Player:**
  - Isolasi stream via `/stream/player/{mirror}` (direct MP4 HTML5 player untuk DesuStream & ad-free blogger iframe).
  - Eliminasi total popunder dan iklan clickjacking pihak ketiga.
  - Navigasi kronologis episode (`← Ep [N-1]` di kiri, `Ep [N+1] →` di kanan).
  - Tombol auto-fallback `Ganti Server` instan.
- **Halaman Publik & SEO:** Beranda, Detail Anime, Watch Player, Jadwal Rilis, Katalog Filter, dan Dynamic Sitemap/JSON-LD Schema.
- **CMS Admin:** Single-door grabber studio, master anime manager dengan multi-filter, dan metadata editor.
- **Quality Assurance:** 22 Automated Feature Tests (174 assertions) Passed 100%.

### ✅ FASE 2 - Tahap 1: Revisi Hero Showcase & Multi-Featured Carousel (SELESAI)
- **Seamless Split Hero Background:** 3-level horizontal blend (100% solid parchment `#F8F6F0` di sisi teks kiri, 50% blend di tengah, dan 0% blend/tajam di sisi kanan mentok tanpa pemisah bawah atau gradasi vertikal).
- **Multi-Featured Carousel (Alpine.js):** Mendukung banyak anime featured sekaligus, rotasi otomatis durasi 6 detik dengan crossfade halus.
- **CSS Grid Stacking Anti-Jeglug:** Seluruh slide foreground berada di sel grid yang sama (`col-start-1 row-start-1 min-h-[380px] lg:min-h-[360px]`), mencegah tumpukan layer ke bawah dan menghilangkan hentakan vertikal (*jeglug*) saat transisi.
- **Kapsul Loader & Navigasi Kustom:** Kontrol kapsul terpadu `(←) [ - - = - - ] (→)` dengan animasi *filling bar* warna Mana Blue (`#2B7A9B`) sesuai interval timer 50ms, serta mendukung lompatan slide instan via klik.
- **Targeted Hover Pause:** Pause timer presisi yang hanya aktif ketika kursor berada di atas Judul Anime atau tombol "Tonton Sekarang".
- **Otomasi Cache & Admin:** `AnimeObserver` untuk auto-invalidation cache hero/schedule, dan multi-select toggle featured pada CMS `AnimeManager`.
- **Quality Assurance:** 22 Automated Feature Tests (174 assertions) Passed 100%.

### 🚀 FASE 2 - Tahap Selanjutnya: Upcoming Roadmap
- Siap melanjutkan fitur dan penyempurnaan berikutnya di Fase 2 sesuai arahan pengguna.
