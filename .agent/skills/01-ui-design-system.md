# Skill 01: Tofunime UI/UX Design System (Millishion Light Mode & Anti-AI Slop)

## 1. Filosofi Visual "Tofunime" (100% STRICT LIGHT MODE)
Nama brand adalah **Tofunime** (kombinasi nuansa bersih seputih *Tofu* dan arsitektur marmer putih-biru-emas Kota Suci Millishion dari dunia isekai *Mushoku Tensei*).
- **DILARANG MENGGUNAKAN DARK MODE.** Seluruh halaman publik dan CMS menggunakan tema terang yang hangat, bersih, dan elegan.

### Palet Warna Terkunci (Tailwind Tokens):
- `bg-parchment`: `#F8F6F0` (Background utama halaman - putih gading hangat, nyaman untuk mata).
- `bg-alabaster`: `#EFECE6` (Background seksi sekunder, area filter, dan footer).
- `bg-surface`: `#FFFFFF` (Background card, modal, tabel, dan dropdown).
- `border-stone-warm`: `#E2DCC8` (Garis border 1px halus di seluruh elemen kartu dan pemisah).
- `mana` (Primary Accent): `#2B7A9B` | Hover: `#1F5C77` (Biru langit/air kanal Millishion untuk tombol utama, link aktif, dan pill filter aktif).
- `gold` (Secondary Accent): `#C59B27` | Soft: `#FBF7E8` (Emas kubah katedral untuk ikon bintang skor dan aksen highlight).
- `ink` (Primary Text): `#1E293B` (Slate 800 - teks utama yang tajam dan kontras).
- `ink-muted` (Secondary Text): `#475569` (Slate 600 - teks sekunder untuk tahun, genre, dan sinopsis).

### Konfigurasi Tipografi:
- **Heading Font (`font-serif`):** `Cinzel` atau `Playfair Display` (memberikan kesan dunia fantasi isekai yang berkelas pada judul utama/seksi).
- **Body & UI Font (`font-sans`):** `Plus Jakarta Sans` atau `Inter` (sangat rapi dan modern untuk navigasi, daftar episode, dan metadata).

---

## 2. ATURAN MUTLAK "ANTI-AI SLOP" (Wajib Dipatuhi di Setiap File Blade)

1. **Disiplin Ketebalan Huruf (No Heavy Bold):**
   - **DILARANG KERAS** menggunakan class `font-bold`, `font-extrabold`, atau `font-black`.
   - Gunakan maksimal `font-semibold` (600) untuk semua Heading (`h1` s/d `h4`), judul anime pada card, dan teks tombol.
   - Gunakan `font-medium` (500) untuk menu navigasi, tab resolusi, dan badge.
   - Gunakan `font-normal` (400) untuk paragraf sinopsis.

2. **Konsistensi Hirarki HTML & Larangan Teks Kecil (`text-xs`):**
   - **DILARANG KERAS** menggunakan `text-xs` (12px) atau lebih kecil karena merusak keterbacaan.
   - Ukuran teks terkecil di seluruh website adalah `text-sm` (14px) dengan warna `#475569` (jangan gunakan `text-gray-400` yang pudar).
   - Paragraf sinopsis wajib `text-base` (16px) `leading-relaxed`.
   - Hirarki Semantik:
     - `<h1>`: `text-3xl lg:text-4xl font-serif font-semibold text-[#1E293B] tracking-tight`
     - `<h2>`: `text-2xl font-serif font-semibold text-[#1E293B]`
     - `<h3>` (Judul Card): `text-base font-sans font-semibold text-[#1E293B] line-clamp-1 group-hover:text-[#2B7A9B]`

3. **Anti-Redundansi & Diet Badge:**
   - Karena seluruh isi Tofunime sudah pasti anime Subtitle Indonesia berkualitas HD, **DILARANG** menempelkan badge "SUB INDO" atau "HD" di setiap kartu poster.
   - Di atas poster hanya boleh ada maksimal **1 indikator bersih** di sudut kanan atas (contoh: `Ep 08` dengan latar `#FFFFFF/95 text-[#1E293B] border border-[#E2DCC8] text-sm font-medium px-2.5 py-0.5 rounded`).
   - Di bawah poster hanya boleh ada: **Judul Anime (`h3`)** dan **1 baris metadata**: `Tahun Rilis` di kiri dan `★ Skor` di kanan.

4. **Larangan Warna Pelangi (No Rainbow UI):**
   - Dilarang memberi warna hijau/merah/ungu/biru neon pada daftar Genre, Tag, atau Tipe Anime.
   - Semua pill Genre, Tag, dan Author menggunakan latar `#EFECE6`, border `#E2DCC8`, dan teks `#1E293B` (berubah menjadi `bg-[#2B7A9B] text-white` hanya saat aktif difilter).

5. **Standar Icon — Lucide Icons & Penyelarasan Line-Height:**
   - **Wajib menggunakan Lucide Icons** via Blade Icons package (`<x-lucide-[nama-icon]>` dari `mallardduck/blade-lucide-icons`).
   - **DILARANG KERAS** menulis raw inline `<svg>` secara langsung di file Blade.
   - **Aturan Tinggi Icon (Icon Height = Text Line-Height):**
     - Ukuran tinggi icon (`h-*`) **wajib persis sama** dengan `line-height` teks pendampingnya:
       - Teks `text-sm` (line-height 20px / `leading-5`): gunakan `w-5 h-5 shrink-0`.
       - Teks `text-base` (line-height 24px / `leading-6`): gunakan `w-6 h-6 shrink-0`.
       - Teks button padat / compact: gunakan `w-4 h-4 shrink-0`.
     - Selalu tambahkan utility `shrink-0` agar icon tidak gepeng atau terdistorsi saat container mengecil.
   - **Minimalisir Icon:** Gunakan icon hanya untuk elemen fungsional: Search (`<x-lucide-search>`), Play (`<x-lucide-play>`), Skor Rating (`<x-lucide-star>`), Aksi Menu (`<x-lucide-more-vertical>`), Navigasi/Eksternal, Status, dan CMS CRUD.

6. **White Space Lega:**
   - Gunakan container `max-w-7xl mx-auto px-6 lg:px-8`.
   - Jarak vertikal antar seksi `py-14` s/d `py-16`.
   - Jarak antar kartu poster `gap-6 lg:gap-8` (rasio poster selalu `aspect-[2/3] rounded-lg overflow-hidden border border-[#E2DCC8]`).

7. **Client-Side Reactivity — Wajib Pasangan Resmi Livewire + Alpine.js:**
   - Karena stack utama menggunakan **Livewire**, seluruh interaksi client-side UI (dropdown, modal, toggle drawer mobile, popover, tab switching lokal) **WAJIB menggunakan Alpine.js** (`x-data`, `x-show`, `x-cloak`, `@click.outside`, `x-transition`, `$refs`).
   - **DILARANG KERAS** menyisipkan tag `<script>` vanilla JS acak, jQuery, atau library JS pihak ketiga untuk interaktivitas UI semacam ini. Semua status UI lokal harus dikelola secara deklaratif melalui atribut Alpine.js.

8. **Standar Menu Aksi Tabel CMS (Dropdown Titik Tiga):**
   - Pada tabel CMS Admin (seperti daftar anime, episode, laporan link), jika satu baris memiliki lebih dari satu tombol aksi, **WAJIB disembunyikan di dalam dropdown tombol titik 3 vertikal** (`<x-lucide-more-vertical class="w-5 h-5 shrink-0" />`).
   - Menu dropdown dipicu menggunakan Alpine.js (`x-data="{ open: false }"` dengan `@click.outside="open = false"` dan `x-cloak`).
   - Sidebar navigasi Admin CMS **wajib diposisikan di sisi kiri** layar.

9. **Reset & Desain Scrollbar Terintegrasi Tema:**
   - Seluruh scrollbar website (publik dan admin studio) wajib menggunakan kustomisasi CSS terpadu:
     - **Lebar Default:** `6px` (tampak ramping dan elegan, tidak merusak layout).
     - **Saat Hover / Drag Aktif:** Melebar halus ke `8px`.
     - **Warna Track:** `transparent` (tidak ada latar abu-abu tebal khas browser bawaan).
     - **Warna Thumb:** `#D3CBBE` (stone-warm lembut selaras dengan border tema).
     - **Warna Saat Hover:** `#2B7A9B` (Mana primary).
     - **Warna Saat Active/Drag:** `#1F5C77` (Mana deep).
     - **Bentuk:** `border-radius: 9999px` (pill bulat mulus).

10. **Standar Flat & Larangan Efek Shadow pada Card CMS Admin (No Shadow Rules):**
    - **DILARANG KERAS** menggunakan class `shadow`, `shadow-sm`, `shadow-xs`, `shadow-md`, atau `shadow-lg` pada card, container tabel, atau filter card di seluruh area CMS Admin.
    - Seluruh card di Admin CMS wajib menggunakan gaya flat yang tenang, bersih, dan konsisten: `bg-surface border border-stone-warm rounded-lg`.
    - Input pencarian dan dropdown select menggunakan `rounded-lg border border-stone-warm bg-parchment` tanpa shadow.

11. **Anti-Clutter Headings & Larangan Ornamen/Breadcrumb Redundan:**
    - **DILARANG** menambahkan kotak/box icon dekoratif (misal `<div class="w-10 h-10 rounded-xl bg-mana/10...">`) di samping judul utama `<h1>`. Biarkan judul serif berdiri elegan dengan ruang nafas luas.
    - **DILARANG** membuat sub-breadcrumb manual di dalam area konten (`Admin / Manajemen Anime / ...`) karena layout admin sudah memiliki breadcrumb resmi di navbar atas.
    - **DILARANG** menambahkan tombol navigasi ganda / redundan di header halaman jika menu tersebut sudah tersedia di sidebar kiri (contoh: tombol *Impor Anime via Scraper* di katalog anime) atau navbar kanan atas (contoh: tombol *Lihat di Frontend* di editor anime).
    - **DILARANG** menempelkan badge status dekoratif yang tidak perlu di header atau card title (contoh: `Queue Worker Standby`, `Multi-Sumber`, atau badge counter `Menampilkan X dari Y Anime` di samping input pencarian). Berikan whitespace yang luas dan bebas sesak.

12. **Disiplin Tabel Master List CMS (Anti-Too-Much & Whitespace Lega):**
    - Tabel master list harus bersih, tidak ramai, dan bernafas lega (`py-3.5 px-5` s/d `py-4 px-5`).
    - Tampilkan informasi secukupnya yang esensial: Kolom `Anime`, `Studio`, `Skor`, `Episode`, `Featured`, `Aksi`. Detail mendalam adalah urusan halaman detail/edit yang bisa diakses dengan 1-klik.
    - **DILARANG** menampilkan tumpukan pill badge genre di setiap baris tabel master (membuat tabel penuh sesak dan bising).
    - **DILARANG** menempelkan overlay badge skor di atas sudut poster thumbnail. Poster harus bersih murni.
    - Tampilkan skor sebagai teks bersih minimalis (`★ 9.10`), bukan dibungkus pill box abu-abu.
    - Teks sekunder metadata di bawah judul dibuat subtle (`TV · Ongoing · 2026`), tanpa background badge.

---

## 3. Struktur Layout Tofunime (Adaptasi Filmagnet Desktop-1 & Desktop-2)

### A. Halaman Utama (Desktop-1 Layout)
1. **Navbar (`h-20`):** Kiri: Brand **TOFUNIME** (`font-serif font-semibold text-2xl tracking-wider text-[#1E293B]` dengan titik aksen `#C59B27`). Tengah: `Beranda`, `Katalog Anime`, `Ongoing`, `Jadwal Rilis`, `Genre & Tag`. Kanan: Livewire Search Modal & tombol `Masuk`.
2. **Hero Showcase (Diagonal Split):** Menampilkan *Featured Anime* dengan artwork lebar di kanan dan area teks berlatar `#F8F6F0` di kiri. Menampilkan musim/tahun (warna `#C59B27`), Judul `<h1>`, 3 Genre utama, sinopsis 2 baris, dan tombol bulat `Tonton Sekarang` (`bg-[#2B7A9B] text-white px-7 py-3 rounded-full font-semibold`).
3. **Section "Update Episode Terbaru" (Grid 4 Kolom Besar):** Dilengkapi pill filter (`Semua`, `TV Series`, `Movie`, `OVA`) di sisi kanan header.
4. **Section "Studio & Direktori Pilihan":**
   - 1 baris Grid 6 kolom poster kompak.
   - 4 Kartu Banner Studio Ikonik (`Studio Bind`, `MAPPA`, `Ufotable`, `Madhouse`) berlatar `#FFFFFF border border-[#E2DCC8] py-8 rounded-lg text-center`.
   - 1 baris Grid 6 kolom poster kompak di bawahnya, diikuti Pagination angka dan deskripsi singkat Tofunime di Footer.

### B. Halaman Detail & Nonton (Desktop-2 Layout)
1. **Header Detail Anime:**
   - Tombol `← Kembali` di atas.
   - Kiri: Poster Anime (`aspect-[2/3]`).
   - Kanan: Label status/episode terbaru (`text-[#C59B27] font-medium text-sm`), Judul `<h1>`, Judul Jepang (`text-base text-[#475569]`), baris metadata (`Studio · Author/Creator · Musim · Durasi`), kotak Skor Rating & tombol `Mulai Nonton`, paragraf Sinopsis (`text-base leading-relaxed`), serta deretan pill **Genre & Theme/Tag** yang bisa diklik.
2. **Area Video Player & Download (Livewire `WatchPlayer`):**
   - Layar Iframe `aspect-video` berbingkai `#E2DCC8`.
   - Baris kontrol tunggal di bawah player: Pemilih Resolusi (`360p` - `1080p`), Pemilih Server Mirror, dan tombol `Lapor Video Rusak`.
   - Kartu Link Download per resolusi (MP4/MKV/Batch) yang tersusun rapi.
3. **Daftar Episode & Rekomendasi Serupa:**
   - Deretan tombol angka episode (`01`, `02`, `03`...) berukuran nyaman (`text-base font-medium`).
   - Di bawahnya terdapat seksi **"Rekomendasi Anime Serupa"** (Grid 4 kolom besar + 6 kolom kompak).
