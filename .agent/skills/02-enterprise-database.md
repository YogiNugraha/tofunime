# Skill 02: Tofunime Enterprise Database Schema (Jikan + Scraper Ready)

Buat seluruh Migration dan Model Eloquent untuk menampung kekayaan data dari **Jikan API** (Judul, Genre, Theme/Tag, Demographic, Author/Creator, Studio, Producer) sekaligus data streaming dari **Otakudesu Scraper** (Episode, Video Mirrors, Download Links).

## 1. Tabel Metadata & Taksonomi (Sinkron dengan Jikan API)

### A. Tabel `studios` (Studio & Producer)
- `id` (BigInt, PK)
- `mal_id` (BigInt Unsigned, Nullable, Unique) - ID Studio dari Jikan API.
- `name` (String 120)
- `slug` (String 140, Unique)
- `type` (Enum: `studio`, `producer`, `licensor`, default: `studio`)
- `is_featured` (Boolean, default: false, Indexed)
- `timestamps`

### B. Tabel `people` (Authors, Original Creators, Directors)
Menyimpan data Author/Mangaka/Light Novel Creator yang diambil dari endpoint `/anime/{id}/staff` Jikan API.
- `id` (BigInt, PK)
- `mal_id` (BigInt Unsigned, Nullable, Unique)
- `name` (String 150)
- `slug` (String 170, Unique)
- `image_url` (String 500, Nullable)
- `timestamps`

### C. Tabel `anime_person` (Pivot Author/Staff ke Anime)
- `anime_id` (FK ke `animes` CascadeOnDelete)
- `person_id` (FK ke `people` CascadeOnDelete)
- `role` (String 100) - Contoh: `"Original Creator"`, `"Author"`, `"Director"`, `"Character Design"`.
- `PRIMARY KEY(['anime_id', 'person_id', 'role'])`

### D. Tabel `taxonomies` (Menyatukan Genre, Theme/Tag, & Demographic Jikan)
Di Jikan API, data terbagi menjadi `genres` (Action, Fantasy), `themes` / tags (Isekai, Reincarnation, Magic, School), dan `demographics` (Shounen, Seinen).
- `id` (BigInt, PK)
- `mal_id` (BigInt Unsigned, Nullable)
- `name` (String 100)
- `slug` (String 120, Unique)
- `type` (Enum: `genre`, `theme`, `demographic`, default: `genre`, Indexed)
- `description` (Text, Nullable)
- `timestamps`

### E. Tabel `anime_taxonomy` (Pivot)
- `anime_id` (FK ke `animes` CascadeOnDelete)
- `taxonomy_id` (FK ke `taxonomies` CascadeOnDelete)
- `PRIMARY KEY(['anime_id', 'taxonomy_id'])`

### F. Tabel `seasons`
- `id`, `name` (Contoh: "Fall 2026"), `slug` (Unique), `year` (SmallInt, Indexed), `quarter` (Enum: `Winter`, `Spring`, `Summer`, `Fall`), `timestamps`.

### G. Tabel `animes`
- `id` (BigInt, PK)
- `mal_id` (BigInt Unsigned, Nullable, Unique)
- `studio_id` (FK ke `studios`, Nullable)
- `season_id` (FK ke `seasons`, Nullable)
- `title` (String 255) - Judul utama.
- `slug` (String 255, Unique)
- `english_title` (String 255, Nullable)
- `japanese_title` (String 255, Nullable)
- `synonyms` (JSON, Nullable) - Array judul alternatif dari Jikan.
- `synopsis` (Text, Nullable) - Sinopsis bahasa Indonesia (dari scraper/terjemahan) atau Inggris (dari Jikan).
- `background_info` (Text, Nullable) - Info tambahan dari Jikan.
- `source_material` (String 80, Nullable) - Contoh: `"Light novel"`, `"Manga"`, `"Web manga"`, `"Original"` (dari field `source` Jikan API).
- `poster_url` (String 500, Nullable)
- `banner_url` (String 500, Nullable)
- `trailer_youtube_id` (String 50, Nullable)
- `type` (Enum: `TV`, `Movie`, `OVA`, `ONA`, `Special`, default: `TV`)
- `status` (Enum: `Ongoing`, `Completed`, `Upcoming`, default: `Ongoing`)
- `total_episodes` (SmallInt Unsigned, Nullable)
- `duration` (String 60, Nullable)
- `release_year` (Year, Nullable)
- `aired_string` (String 100, Nullable)
- `release_day` (Enum: `Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday`, `Sunday`, `Random`, Nullable)
- `rating_score` (Decimal 4,2, default: 0.00)
- `scored_by` (Int Unsigned, default: 0)
- `rank` (Int Unsigned, Nullable)
- `age_rating` (String 80, Nullable)
- `is_featured` (Boolean, default: false)
- `is_recommended` (Boolean, default: false)
- `views_count` (BigInt Unsigned, default: 0)
- `latest_episode_at` (Timestamp, Nullable) - Diupdate otomatis oleh `EpisodeObserver`.
- `seo_title`, `seo_description`, `seo_keywords` (String/Text, Nullable)
- `timestamps`
- **INDEXES:**
  - `FULLTEXT(['title', 'english_title', 'japanese_title'])`
  - `INDEX(['status', 'latest_episode_at'])`
  - `INDEX(['type', 'status', 'rating_score'])`
  - `INDEX(['release_day', 'status'])`

---

## 2. Tabel Episode, Streaming Server & Scraper Engine

### H. Tabel `episodes`
- `id` (BigInt, PK)
- `anime_id` (FK ke `animes` CascadeOnDelete)
- `episode_number` (String 20)
- `episode_order` (Decimal 6,2)
- `title` (String 255, Nullable)
- `slug` (String 255, Unique) - Format: `{anime-slug}-episode-{number}-sub-indo`
- `source_episode_url` (String 500, Nullable) - URL asal episode di Otakudesu/web sumber.
- `views_count` (BigInt Unsigned, default: 0)
- `aired_at` (Timestamp, Nullable)
- `timestamps`
- **INDEXES:** `UNIQUE(['anime_id', 'episode_number'])`, `INDEX(['anime_id', 'episode_order'])`.

### I. Tabel `video_mirrors`
- `id`, `episode_id` (FK Cascade), `server_name` (String 80), `quality` (Enum: `360p`, `480p`, `720p`, `1080p`, `4K`), `embed_url` (Text), `is_default` (Bool), `is_active` (Bool, default: true), `priority_order` (TinyInt, default: 1), `timestamps`.
- **INDEXES:** `INDEX(['episode_id', 'is_active', 'quality', 'priority_order'])`.

### J. Tabel `download_links`
- `id`, `anime_id` (FK), `episode_id` (FK, Nullable untuk Batch), `is_batch` (Bool, default: false), `format` (Enum: `MP4`, `MKV`, `x265`), `quality` (Enum: `360p`, `480p`, `720p`, `1080p`), `host_name` (String 80), `url` (Text), `file_size` (String 40, Nullable), `timestamps`.

### K. Tabel `scraper_sources`
Menyimpan hubungan anime dengan URL sumber seperti `[https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/](https://otakudesu.blog/anime/tensei-deshita-s2-sub-indo/)`.
- `id`, `anime_id` (FK Cascade), `provider_name` (String 50, default: `otakudesu`), `source_anime_url` (String 500, Unique), `auto_sync` (Bool, default: true), `last_scraped_episode` (String 20, Nullable), `last_synced_at` (Timestamp, Nullable), `timestamps`.

### L. Tabel Pendukung Operasional
- `scraper_logs`: Mencatat riwayat jalannya bot scraper dan response time.
- `broken_reports`: Laporan video mati dari user.
- `user_bookmarks` & `watch_histories`: Fitur koleksi user.
- `ad_slots` & `site_settings`: Menyimpan slot banner iklan dan pengaturan dinamis (seperti Base URL `[https://otakudesu.blog](https://otakudesu.blog)`).
