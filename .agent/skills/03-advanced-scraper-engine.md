# Skill 03: Tofunime Hybrid Engine (Jikan API v4 + Otakudesu Scraper)

## 1. Jikan REST API v4 Service (`App\Services\Jikan\JikanApiService`)
Dokumentasi resmi yang digunakan adalah **Jikan API v4 (`[https://api.jikan.moe/v4](https://api.jikan.moe/v4)`)**.
Buat method berikut di dalam `JikanApiService`:

1. `searchAnime(string $query): ?array`
   - Memanggil `GET [https://api.jikan.moe/v4/anime?q=](https://api.jikan.moe/v4/anime?q=){urlencode($query)}&limit=5`.
   - Digunakan untuk mencari `mal_id` secara otomatis ketika Admin hanya memasukkan URL dari Otakudesu!
2. `getFullAnimeDetails(int $malId): array`
   - Memanggil `GET [https://api.jikan.moe/v4/anime/](https://api.jikan.moe/v4/anime/){$malId}/full`.
   - Memetakan response JSON `data` ke tabel Tofunime:
     - `title`, `title_english`, `title_japanese`, `title_synonyms` -> tabel `animes`.
     - `images.webp.large_image_url` -> `poster_url`.
     - `trailer.youtube_id` -> `trailer_youtube_id`.
     - `source` (Manga / Light novel) -> `source_material`.
     - `score`, `scored_by`, `rank`, `rating`, `duration`, `episodes`, `year`, `season` -> tabel `animes` & `seasons`.
     - `studios[]` & `producers[]` -> `updateOrCreate` ke tabel `studios`.
     - `genres[]` -> `updateOrCreate` ke tabel `taxonomies` dengan `type = 'genre'`.
     - `themes[]` (misal: Isekai, Reincarnation) -> `updateOrCreate` ke tabel `taxonomies` dengan `type = 'theme'`.
     - `demographics[]` (misal: Shounen, Seinen) -> `updateOrCreate` ke tabel `taxonomies` dengan `type = 'demographic'`.
3. `getAnimeAuthorsAndStaff(int $malId): array`
   - Memanggil `GET [https://api.jikan.moe/v4/anime/](https://api.jikan.moe/v4/anime/){$malId}/staff`.
   - Filter item yang memiliki `positions` mengandung `"Original Creator"`, `"Author"`, `"Director"`, atau `"Script"`.
   - Simpan ke tabel `people` dan hubungkan ke `anime_person`.
- **Rate Limit Protection:** Jikan API membatasi 3 request/detik (60 request/menit). Pasang `usleep(400000)` (0.4 detik) antar panggilan endpoint Jikan dan gunakan `Http::retry(3, 1000)`.

---

## 2. Otakudesu Scraper Adapter (`App\Services\Scraper\Adapters\OtakudesuScraperAdapter`)
Target referensi: `[https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/](https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/)` (Pastikan domain `[https://otakudesu.blog](https://otakudesu.blog)` disimpan di `site_settings` dengan key `scraper_otakudesu_base_url` agar fleksibel).

### A. Ekstraksi Halaman Detail Anime (`/anime/{slug}`)
Ketika bot membuka URL seperti `[https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/](https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/)`, lakukan parsing DOM menggunakan `Symfony\Component\DomCrawler\Crawler`:
1. **Poster Fallback:** Ambil atribut `src` dari `.fotoanime img`.
2. **Metadata Box (`.infozin .infozingle p`):**
   - Ekstrak pasangan teks di dalam `<p><span><b>Judul</b>: ...</span></p>`, `Jepang`, `Skor`, `Produser`, `Tipe`, `Status`, `Total Episode`, `Durasi`, `Tanggal Rilis`, `Studio`, dan `Genre`.
   - Data ini digunakan sebagai **Fallback/Pelengkap** jika anime belum punya `mal_id`, dan sinopsis bahasa Indonesia diambil dari `.sinopc p` (digabungkan dengan `<br>`).
3. **Daftar Episode & Batch (`.episodelist ul li`):**
   - Di halaman anime Otakudesu terdapat beberapa blok `.episodelist` (blok Batch dan blok Daftar Episode Reguler).
   - Untuk setiap `<li><span><a href="...">`:
     - Ambil URL episode dan judulnya.
     - Ekstrak nomor episode menggunakan Regex `/Episode\s+([0-9]+(\.[0-9]+)?)/i`.
     - Jika URL mengarah ke `/batch/`, tandai untuk diekstrak link download batch-nya ke tabel `download_links` (`is_batch = true`).
     - Jika URL mengarah ke `/episode/`, simpan ke tabel `episodes` (gunakan `updateOrCreate` berdasarkan `anime_id` dan `episode_number`) lalu antrekan ke `ScrapeEpisodeMirrorsJob`.

### B. Ekstraksi Halaman Episode & Dekoder Base64 AJAX (`OtakudesuAjaxDecoder.php`)
Saat `ScrapeEpisodeMirrorsJob` memproses URL episode:
1. **Default Iframe:** Ambil URL `src` dari `#pembed iframe` sebagai mirror default pertama.
2. **Multi-Server & Multi-Resolusi (`.mirrorstream ul`):**
   - Setiap list resolusi (`ul.m360p`, `ul.m480p`, `ul.m720p`) memiliki elemen `<li><a data-content="{base64_string}">NamaServer</a></li>`.
   - Decode `data-content` (`json_decode(base64_decode($payload), true)`) yang menghasilkan `{"id": post_id, "i": index, "q": quality}`.
   - Ambil **WordPress Nonce** dengan mengirim POST request ke `[https://otakudesu.blog/wp-admin/admin-ajax.php](https://otakudesu.blog/wp-admin/admin-ajax.php)` membawa body `action=aa1208d27f29ca340c92c66d1926f13f` (atau ekstrak string action dari inline script halaman episode).
   - Kirim POST request kedua ke `[https://otakudesu.blog/wp-admin/admin-ajax.php](https://otakudesu.blog/wp-admin/admin-ajax.php)` membawa `id`, `i`, `q`, `nonce`, dan `action=2a3505c93b0035d3f455df82bf976b84`.
   - Response yang didapat adalah string Base64 berisi tag `<iframe src="...">`. Lakukan `base64_decode`, ekstrak URL `src`-nya, dan simpan ke tabel `video_mirrors` beserta nama server (`DesuDrive`, `Mp4upload`, `Kraken`, `Mega`, `Vidhide`, dll) dan resolusinya!
3. **Link Download (`.download ul li`):**
   - Loop setiap baris resolusi (`<strong>360p / 480p / 720p / 1080p</strong>`), ambil ukuran file (`<i>`), dan semua tag `<a href="...">NamaHost</a>` untuk disimpan ke tabel `download_links`.

---

## 3. Orkestrator `HybridAnimeImporter.php`
Service ini menyatukan Jikan API dan Otakudesu Scraper dalam 1 alur kerja otomatis:
- **Skenario 1 (Input URL Sumber Otakudesu / Oploverz):**
  1. Scrape halaman sumber untuk mendapatkan Judul, Sinopsis Bahasa Indonesia, dan Daftar Episode.
  2. Episode dan mirrors video disimpan rapi ke database MySQL.
  3. Alur ingestion otomatis menampilkan tombol **"Isi Metadata"** yang mengalihkan Admin ke `/admin/animes/{id}/edit` untuk auto-fill metadata via MAL/AniList tanpa mengharuskan input MAL ID manual di awal.
  4. Dispatch `ScrapeEpisodeMirrorsJob` untuk menarik seluruh server video dari setiap episode di background worker.

---

## 4. Oploverz Scraper Adapter (`App\Services\Scraper\Adapters\OploverzScraperAdapter`)
- Menggunakan endpoint API / HTML Oploverz untuk mengekstrak episode dan embed mirrors.
- Server mirror Blogger yang diekstrak dibersihkan melalui `StreamPlayerController` agar aman dari layer iklan popup dan clickjacking pihak ketiga.
