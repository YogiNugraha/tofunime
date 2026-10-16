# Skill 05: Tofunime SEO Architecture, Schema.org & Caching

## 1. Struktur URL & Meta Dinamis Tofunime
- **Home:** `/` -> `Tofunime - Nonton & Download Anime Sub Indo Kualitas HD`
- **Detail Anime:** `/anime/{anime:slug}` -> `Nonton {Judul} Sub Indo Streaming & Download Batch - Tofunime`
- **Nonton Episode:** `/nonton/{episode:slug}` -> `Nonton {Judul} Episode {Nomor} Sub Indo - Tofunime`
- **Genre / Tag (Theme) / Studio / Author:**
  - `/genre/{taxonomy:slug}` (Contoh: `/genre/fantasy`)
  - `/tag/{taxonomy:slug}` (Contoh: `/tag/isekai`, `/tag/reincarnation`)
  - `/studio/{studio:slug}` (Contoh: `/studio/studio-bind`)
  - `/author/{person:slug}` (Halaman daftar anime berdasarkan Original Creator/Author!)

## 2. JSON-LD Schema.org (`TofunimeSeoGenerator.php`)
- Pada `/anime/{slug}`: Render schema `TVSeries` yang menyertakan `author` (dari tabel `people`), `productionCompany` (dari `studios`), `genre` (dari `taxonomies`), dan `aggregateRating`.
- Pada `/nonton/{slug}`: Render schema `VideoObject` & `TVEpisode` lengkap dengan `embedUrl`, `thumbnailUrl`, dan `uploadDate`.

## 3. Sitemap Sharding & High-Traffic Caching
- Pecah sitemap menjadi `sitemap-static.xml`, `sitemap-taxonomies.xml`, `sitemap-animes-1.xml`, dan `sitemap-episodes-{part}.xml`.
- Gunakan `TofunimeCacheService` (`Cache::remember`) untuk seluruh query di halaman Beranda dan Jadwal Rilis. Otomatis lakukan *cache invalidation* saat `EpisodeObserver` mendeteksi episode baru.
- Aktifkan `Model::preventLazyLoading(! app()->isProduction())` di `AppServiceProvider`.
