# Skill 06: Tofunime CMS Dashboard & Hybrid Grabber Studio

Seluruh halaman Admin (`/admin/*`) menggunakan desain **Tofunime Millishion Light Mode**, flat tanpa shadow, dan wajib mematuhi aturan **Anti-AI Slop**.

---

## 1. Arsitektur Alur Linear & Single Door Importer (Terstruktur dari Atas ke Bawah)

Untuk mencegah redundansi data, duplikasi pintu masuk, atau anime manual kosong tanpa episode/video, alur sistem CMS Tofunime distandarisasi secara linear:

```
[ 1. Hybrid Grabber Studio ] (/admin/scraper)
  └── SATU-SATUNYA PINTU MASUK ANIME BARU
      ├── Ekstrak metadata default dari link sumber (Otakudesu / Oploverz)
      ├── (Opsional) Enrich data resmi dari MyAnimeList / AniList
      └── Episode & Mirror video otomatis tersimpan rapi

[ 2. Katalog Anime Master ] (/admin/animes)
  └── DAFTAR INVENTARIS & FILTER LENGKAP
      ├── Rich Filter setara frontend (Search live, Status, Tipe, Genre, Sort, Reset)
      ├── TIDAK ADA tombol "+ Tambah Anime" manual kosong (/admin/animes/create di-redirect ke /admin/scraper)
      └── Tabel bersih minimalis (tanpa badge genre bertumpuk atau shadow)

[ 3. Editor Anime ] (/admin/animes/{id}/edit)
  └── KHUSUS MEMOLES METADATA
      ├── Edit judul, sinopsis, poster/banner, studio, genre, staf, rating, status featured
      └── Bebas dari panel scraping / episode sync yang redundan

[ 4. Episode & Mirror Manager ] (/admin/episodes/{episode})
  └── PENGELOLAAN SERVER SPESIFIK
      └── Kelola iframe embed server dan link download per episode
```

---

## 2. Fitur Utama CMS Dashboard Tofunime

### A. Hybrid Grabber Studio (`/admin/scraper`) - PINTU GERBANG UTAMA
Halaman ini memiliki 3 panel kerja:
1. **1-Click Hybrid Importer (Otakudesu + Oploverz):**
   - **Input Tunggal:** `URL Anime Sumber` (Otakudesu HTML atau Oploverz JSON API) dengan pilihan provider (Auto / Otakudesu / Oploverz).
   - **Cara Kerja:** Ekstrak data dan streaming mirrors secara otomatis dari provider sumber. Begitu selesai, tersedia tombol tunggal **"Isi Metadata"** yang langsung mengalihkan admin ke `/admin/animes/{id}/edit` untuk melengkapi metadata via editor/Jikan auto-fill.
2. **Auto-Sync Ongoing Monitor:**
   - Daftar anime `Ongoing` yang aktif terhubung dengan URL scraper.
   - Tombol cek episode baru otomatis.
3. **Real-Time Scraper Console & Logs:**
   - Log konsol Alpine live timer, status antrean Job, latency, dan jumlah mirror yang diekstrak.

### B. Manajemen Katalog Anime (`/admin/animes`)
- Daftar master seluruh anime dalam sistem.
- **Rich Filtering Dashboard:** Pencarian debounced, filter Status, Tipe Format, Genre dinamis dari tabel `taxonomies`, dan Urutan.
- **Tabel Minimalis:** Kolom `Anime`, `Studio`, `Skor`, `Episode`, `Featured`, `Aksi`. Bebas dari tumpukan pill badge dan bebas shadow.
- Menu dropdown aksi titik 3 vertikal: *Edit Metadata*, *Tarik Ulang Data MAL*, *Lihat di Website*, *Kelola Episode & Mirror*, *Set Featured*, dan *Hapus Anime*.

### C. Editor Metadata Anime (`/admin/animes/{id}/edit`)
- Murni editor metadata: Informasi Dasar (Judul, Slug, Sinonim), Cover & Media (Poster, Banner, Trailer YouTube), Klasifikasi (Tipe, Status, Hari Rilis, Tahun, Rating), Studio, Season, Genre & Theme, Staff, dan Status Featured Showcase.
- Dilengkapi alat Auto-Fill 1-klik dari MyAnimeList/AniList untuk sinkronisasi metadata cepat.

### D. Broken Report Queue (`/admin/reports`) & Settings (`/admin/settings`)
- Daftar laporan video rusak dari user per episode/mirror dengan tombol penanganan instan.
- Pengaturan dinamis **Base URL Sumber Scraper** (domain Otakudesu/Oploverz yang bisa diubah kapan saja tanpa deploy kode).

---

## 3. Disiplin Styling & Whitespace Admin (Wajib Dipatuhi)
1. **Card Flat No-Shadow:** Dilarang menggunakan utility `shadow*` pada card/container admin (`bg-surface border border-stone-warm rounded-lg`).
2. **Heading Bersih:** Dilarang meletakkan box ikon dekoratif di samping judul `<h1>`.
3. **No Double Breadcrumb:** Jangan buat sub-breadcrumb di dalam konten; manfaatkan breadcrumb layout topbar.
4. **No Redundant Navigation Buttons:** Hindari membuat tombol navigasi redundan jika pintasannya sudah tersedia di sidebar menu atau topbar.
