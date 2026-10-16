<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnimeStatus;
use App\Enums\AnimeType;
use App\Enums\ReleaseDay;
use App\Enums\SeasonQuarter;
use App\Enums\StudioType;
use App\Enums\TaxonomyType;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Person;
use App\Models\Season;
use App\Models\SiteSetting;
use App\Models\Studio;
use App\Models\Taxonomy;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial demo data.
     */
    public function run(): void
    {
        // 1. Initial Site Settings
        SiteSetting::set('scraper_otakudesu_base_url', 'https://otakudesu.blog', 'scraper', 'Base URL resmi sumber scraper Otakudesu');
        SiteSetting::set('scraper_oploverz_base_url', 'https://oploverz.site', 'scraper', 'Base URL resmi sumber scraper Oploverz');
        SiteSetting::set('default_scraper_provider', 'auto', 'scraper', 'Mode default scraper bot (auto detect)');
        SiteSetting::set('site_name', 'Tofunime', 'general', 'Nama website utama');
        SiteSetting::set('site_title', 'Tofunime - Nonton & Download Anime Sub Indo Kualitas HD', 'general', 'Judul situs utama');
        SiteSetting::set('site_tagline', 'Streaming Anime Subtitle Indonesia Bersih & Elegan', 'general', 'Tagline Tofunime');
        SiteSetting::set('meta_description', 'Nonton dan download anime subtitle Indonesia terlengkap dan terupdate kualitas HD tanpa iklan mengganggu di Tofunime.', 'seo', 'Meta description default SEO');
        SiteSetting::set('meta_keywords', 'nonton anime, anime sub indo, download anime, tofunime, streaming anime hd, jadwal anime', 'seo', 'Meta keywords default SEO');

        // 2. 1 Akun Admin (email: admin@tofunime.test, password: password)
        User::updateOrCreate(
            ['email' => 'admin@tofunime.test'],
            [
                'name' => 'Tofunime Admin',
                'password' => bcrypt('password'),
            ]
        );

        // 3. 4 Featured Studios (Studio Bind, MAPPA, Ufotable, Madhouse) + other notable studios
        $bind = Studio::updateOrCreate(
            ['slug' => 'studio-bind'],
            ['name' => 'Studio Bind', 'type' => StudioType::Studio, 'is_featured' => true]
        );
        $mappa = Studio::updateOrCreate(
            ['slug' => 'mappa'],
            ['name' => 'MAPPA', 'type' => StudioType::Studio, 'is_featured' => true]
        );
        $ufotable = Studio::updateOrCreate(
            ['slug' => 'ufotable'],
            ['name' => 'Ufotable', 'type' => StudioType::Studio, 'is_featured' => true]
        );
        $madhouse = Studio::updateOrCreate(
            ['slug' => 'madhouse'],
            ['name' => 'Madhouse', 'type' => StudioType::Studio, 'is_featured' => true]
        );
        $wit = Studio::updateOrCreate(
            ['slug' => 'wit-studio'],
            ['name' => 'Wit Studio', 'type' => StudioType::Studio, 'is_featured' => false]
        );
        $clover = Studio::updateOrCreate(
            ['slug' => 'cloverworks'],
            ['name' => 'CloverWorks', 'type' => StudioType::Studio, 'is_featured' => false]
        );

        // 4. Seasons
        $fall2026 = Season::updateOrCreate(
            ['slug' => 'fall-2026'],
            ['name' => 'Fall 2026', 'year' => 2026, 'quarter' => SeasonQuarter::Fall]
        );

        // 5. Taxonomies (Genres & Themes)
        $fantasy = Taxonomy::updateOrCreate(
            ['slug' => 'fantasy'],
            ['name' => 'Fantasy', 'type' => TaxonomyType::Genre]
        );
        $action = Taxonomy::updateOrCreate(
            ['slug' => 'action'],
            ['name' => 'Action', 'type' => TaxonomyType::Genre]
        );
        $adventure = Taxonomy::updateOrCreate(
            ['slug' => 'adventure'],
            ['name' => 'Adventure', 'type' => TaxonomyType::Genre]
        );
        $drama = Taxonomy::updateOrCreate(
            ['slug' => 'drama'],
            ['name' => 'Drama', 'type' => TaxonomyType::Genre]
        );
        $isekai = Taxonomy::updateOrCreate(
            ['slug' => 'isekai'],
            ['name' => 'Isekai', 'type' => TaxonomyType::Theme]
        );
        $reincarnation = Taxonomy::updateOrCreate(
            ['slug' => 'reincarnation'],
            ['name' => 'Reincarnation', 'type' => TaxonomyType::Theme]
        );
        $magic = Taxonomy::updateOrCreate(
            ['slug' => 'magic'],
            ['name' => 'Magic', 'type' => TaxonomyType::Theme]
        );
        $shounen = Taxonomy::updateOrCreate(
            ['slug' => 'shounen'],
            ['name' => 'Shounen', 'type' => TaxonomyType::Demographic]
        );

        // 6. People (Authors & Directors)
        $rifujin = Person::updateOrCreate(
            ['slug' => 'rifujin-na-magonote'],
            ['name' => 'Rifujin na Magonote']
        );
        $kanehito = Person::updateOrCreate(
            ['slug' => 'kanehito-yamada'],
            ['name' => 'Kanehito Yamada']
        );
        $gege = Person::updateOrCreate(
            ['slug' => 'gege-akutami'],
            ['name' => 'Gege Akutami']
        );

        // 7. Featured Hero Anime: Mushoku Tensei Season 2
        $mushoku = Anime::updateOrCreate(
            ['slug' => 'mushoku-tensei-ii-isekai-ittara-honki-dasu'],
            [
                'mal_id' => 51179,
                'studio_id' => $bind->id,
                'season_id' => $fall2026->id,
                'title' => 'Mushoku Tensei II: Isekai Ittara Honki Dasu',
                'english_title' => 'Mushoku Tensei: Jobless Reincarnation Season 2',
                'japanese_title' => '無職転生 II ～異世界行ったら本気だす～',
                'synopsis' => 'Kelanjutan perjalanan Rudeus Greyrat yang memasuki usia remaja setelah insiden bencana teleportasi benua Fittoa. Dengan tekad membara untuk menyembuhkan luka batinnya dan mencari keberadaan ibunya, Rudeus mendaftar di Akademi Sihir Ranoa.',
                'poster_url' => 'https://cdn.myanimelist.net/images/anime/1070/138139l.webp',
                'banner_url' => 'https://cdn.myanimelist.net/images/anime/1070/138139l.webp',
                'type' => AnimeType::TV,
                'status' => AnimeStatus::Ongoing,
                'total_episodes' => 24,
                'duration' => '24 menit',
                'release_year' => 2026,
                'aired_string' => 'Jul 2026 to ?',
                'release_day' => ReleaseDay::Sunday,
                'rating_score' => 8.72,
                'scored_by' => 312000,
                'rank' => 45,
                'age_rating' => 'R - 17+',
                'is_featured' => true,
                'is_recommended' => true,
                'views_count' => 125400,
                'latest_episode_at' => now(),
            ]
        );
        $mushoku->taxonomies()->sync([$fantasy->id, $adventure->id, $isekai->id, $reincarnation->id, $magic->id]);
        $mushoku->people()->sync([$rifujin->id => ['role' => 'Original Creator']]);

        for ($i = 1; $i <= 12; $i++) {
            Episode::updateOrCreate(
                ['slug' => "mushoku-tensei-ii-episode-{$i}-sub-indo"],
                [
                    'anime_id' => $mushoku->id,
                    'episode_number' => (string) $i,
                    'episode_order' => (float) $i,
                    'title' => 'Episode '.$i,
                    'aired_at' => now()->subDays(12 - $i),
                    'views_count' => 5400 * $i,
                ]
            );
        }

        // 8. Other Popular Animes for Grid Showcase
        $animeCatalog = [
            [
                'title' => 'Sousou no Frieren',
                'slug' => 'sousou-no-frieren',
                'studio_id' => $madhouse->id,
                'author_id' => $kanehito->id,
                'rating' => 9.34,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1015/138006l.webp',
                'episodes' => 28,
                'genres' => [$fantasy->id, $adventure->id, $drama->id, $shounen->id],
            ],
            [
                'title' => 'Jujutsu Kaisen Season 2',
                'slug' => 'jujutsu-kaisen-season-2',
                'studio_id' => $mappa->id,
                'author_id' => $gege->id,
                'rating' => 8.82,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1792/138042l.webp',
                'episodes' => 23,
                'genres' => [$action->id, $fantasy->id, $shounen->id],
            ],
            [
                'title' => 'Kimetsu no Yaiba: Hashira Geiko-hen',
                'slug' => 'kimetsu-no-yaiba-hashira-geiko-hen',
                'studio_id' => $ufotable->id,
                'author_id' => null,
                'rating' => 8.56,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1410/141478l.webp',
                'episodes' => 8,
                'genres' => [$action->id, $fantasy->id, $shounen->id],
            ],
            [
                'title' => 'Chainsaw Man Reze Arc',
                'slug' => 'chainsaw-man-reze-arc',
                'studio_id' => $mappa->id,
                'author_id' => null,
                'rating' => 8.65,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1806/126216l.webp',
                'episodes' => 1,
                'genres' => [$action->id, $drama->id, $shounen->id],
            ],
            [
                'title' => 'Kage no Jitsuryokusha ni Naritakute! S2',
                'slug' => 'kage-no-jitsuryokusha-ni-naritakute-s2',
                'studio_id' => $bind->id,
                'author_id' => null,
                'rating' => 8.35,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1373/138044l.webp',
                'episodes' => 12,
                'genres' => [$action->id, $fantasy->id, $isekai->id],
            ],
            [
                'title' => 'Tensei shitara Slime Datta Ken S3',
                'slug' => 'tensei-shitara-slime-datta-ken-s3',
                'studio_id' => $clover->id,
                'author_id' => null,
                'rating' => 8.24,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1090/141443l.webp',
                'episodes' => 24,
                'genres' => [$fantasy->id, $adventure->id, $isekai->id],
            ],
            [
                'title' => 'Solo Leveling: Arise',
                'slug' => 'solo-leveling-arise',
                'studio_id' => $wit->id,
                'author_id' => null,
                'rating' => 8.41,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1045/141477l.webp',
                'episodes' => 12,
                'genres' => [$action->id, $fantasy->id],
            ],
            [
                'title' => 'Re:Zero kara Hajimeru Isekai Seikatsu S3',
                'slug' => 're-zero-kara-hajimeru-isekai-seikatsu-s3',
                'studio_id' => $bind->id,
                'author_id' => null,
                'rating' => 8.68,
                'year' => 2026,
                'poster' => 'https://cdn.myanimelist.net/images/anime/1487/141480l.webp',
                'episodes' => 16,
                'genres' => [$drama->id, $fantasy->id, $isekai->id],
            ],
        ];

        foreach ($animeCatalog as $data) {
            $anime = Anime::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'studio_id' => $data['studio_id'],
                    'season_id' => $fall2026->id,
                    'title' => $data['title'],
                    'synopsis' => "Sinopsis lengkap anime {$data['title']} dengan alur cerita menarik dan kualitas animasi terbaik.",
                    'poster_url' => $data['poster'],
                    'banner_url' => $data['poster'],
                    'type' => AnimeType::TV,
                    'status' => AnimeStatus::Ongoing,
                    'total_episodes' => $data['episodes'],
                    'duration' => '24 menit',
                    'release_year' => $data['year'],
                    'release_day' => ReleaseDay::Saturday,
                    'rating_score' => $data['rating'],
                    'scored_by' => rand(15000, 95000),
                    'is_featured' => false,
                    'is_recommended' => true,
                    'views_count' => rand(20000, 90000),
                    'latest_episode_at' => now(),
                ]
            );

            $anime->taxonomies()->sync($data['genres']);

            if (! empty($data['author_id'])) {
                $anime->people()->sync([$data['author_id'] => ['role' => 'Original Creator']]);
            }

            for ($ep = 1; $ep <= min(8, $data['episodes']); $ep++) {
                Episode::updateOrCreate(
                    ['slug' => "{$data['slug']}-episode-{$ep}-sub-indo"],
                    [
                        'anime_id' => $anime->id,
                        'episode_number' => (string) $ep,
                        'episode_order' => (float) $ep,
                        'title' => "Episode {$ep}",
                        'aired_at' => now()->subDays(rand(1, 14)),
                        'views_count' => rand(1000, 25000),
                    ]
                );
            }
        }
    }
}
