# Skill 04: Tofunime Smart Livewire Player, Auto-Fallback & Monetization

## 1. Komponen `App\Livewire\Public\WatchPlayer`
- **Reactive Resolution & Server Switcher:**
  - Menampilkan tab resolusi (`360p`, `480p`, `720p`, `1080p`) dan daftar tombol server (`DesuDrive`, `Vidhide`, `Mp4Upload`, `Kraken`, `Mega`).
  - Saat user mengklik server lain, hanya komponen `<iframe>` yang di-*refresh* secara instan via Livewire tanpa me-reload halaman.
- **Auto-Fallback & Ganti Server:**
  - Tombol `Ganti Server` di bawah player akan mencatat laporan ke tabel `broken_reports` dan langsung memindahkan `$activeMirrorId` ke server cadangan berikutnya secara instan.
  - Jika sebuah mirror dilaporkan rusak >= 5 kali, sistem menonaktifkan mirror tersebut dan memicu `ScrapeEpisodeMirrorsJob` untuk mengambil ulang URL terbaru.
- **Penyimpanan History Otomatis:**
  - Penyimpanan otomatis episode terakhir yang ditonton ke `localStorage` serta tabel `watch_histories`.
- **Navigasi Episode Kronologis:**
  - Tombol navigasi di header player dihitung secara urutan kronologis: Episode sebelumnya (nomor lebih kecil) berada di sebelah kiri (`← Ep 17`), dan Episode selanjutnya (nomor lebih besar) berada di sebelah kanan (`Ep 19 →`). Grid episode di bawah player tetap mempertahankan urutan terbaru ke terlama.
- **Resolusi Episode Halaman Detail Anime (`AnimeDetail`):**
  - Untuk mencegah tombol aksi terbalik karena urutan relasi `episodes()` default adalah descending:
    - Episode Pertama/Awal (`Mulai Nonton Ep 1`): Ambil via `$anime->episodes->sortBy('episode_order')->first()`.
    - Episode Terbaru/Akhir (`Episode Terbaru (Ep N)`): Ambil via `$anime->episodes->sortByDesc('episode_order')->first()`.
    - Jangan pernah menggunakan `$anime->episodes->first()` tanpa penegasan sorting untuk episode awal.

## 2. Kebijakan Iframe Embed Player & Stream Isolation Controller
- **Stream Isolation Controller (`/stream/player/{mirror}`):**
  - Semua pemutaran video eksternal diarahkan melalui `StreamPlayerController` untuk membersihkan iklan invasif pihak ketiga.
  - Untuk embed Otakudesu (`desustream.net`): Mengisolasi link direct MP4 (`cdn.odcloud.net` / `archive.org`) dan memutarnya langsung menggunakan native HTML5 `<video controls>` dengan zero third-party ads.
  - Untuk embed Oploverz (`blogger.com`): Me-render iframe blogger secara ad-free dan menonaktifkan layer clickjacking/fake captcha ("Verify you are human - ADEX.COM").
- **Hindari Atribut `sandbox` pada `<iframe>` Player:**
  - Provider video streaming pihak ketiga (Blogger, Mega, StreamWish, Doodstream, Otakudesu player) secara eksplisit mendeteksi keberadaan atribut `sandbox`.
  - Jika `sandbox` disematkan, player pihak ketiga akan memblokir pemutaran dan memunculkan error: *"This video is not available due to sandboxed iframe!"*.
  - Gunakan `allowfullscreen`, `referrerpolicy="no-referrer"`, dan `allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"` tanpa atribut `sandbox`.
- **Penghapusan Mode Fokus (Theater Mode):**
  - Mode fokus dihapus agar antarmuka bar kontrol player tetap bersih, ringkas, dan fokus pada pemilihan resolusi, mirror server, dan tombol `Ganti Server`.

## 3. Manajemen Slot Iklan (`<x-ad-slot key="..." />`)
- Mendukung penempatan banner internal yang tidak merusak estetika Millishion Light Mode: `home_below_hero`, `player_preroll_overlay`, dan `player_below_controls`.
- Jika slot tidak aktif di CMS Admin, komponen tidak boleh me-render tag HTML apa pun agar *white space* tetap terjaga.
