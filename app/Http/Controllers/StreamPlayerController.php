<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VideoMirror;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class StreamPlayerController extends Controller
{
    /**
     * Render a clean, ad-free video player for the given mirror.
     */
    public function player(VideoMirror $mirror): Response
    {
        $embedUrl = trim((string) $mirror->embed_url);

        // 1. Desustream (Otakudesu direct stream) -> Extract direct MP4 for 100% ad-free playback
        if (str_contains($embedUrl, 'desustream.net')) {
            return $this->handleDesustream($embedUrl, $mirror);
        }

        // 2. Google / Blogger Video (Primary player for Oploverz) -> Direct clean stream
        if (str_contains($embedUrl, 'blogger.com') || str_contains($embedUrl, 'google.com')) {
            return $this->handleBlogger($embedUrl, $mirror);
        }

        // 3. Ad-heavy providers (Vidhide, StreamWish, FileLions) -> Strip ads, popups, and clickjacking
        if (str_contains($embedUrl, 'vidhide') || str_contains($embedUrl, 'streamwish') || str_contains($embedUrl, 'filelion')) {
            return $this->handleAdHeavyHost($embedUrl, $mirror);
        }

        // 4. Mega or other embeds -> Serve via clean wrapper with popup blocker
        return $this->handleGenericEmbed($embedUrl, $mirror);
    }

    /**
     * Handle Blogger/Google Video: Clean native wrapper without popups.
     */
    protected function handleBlogger(string $url, VideoMirror $mirror): Response
    {
        $escapedUrl = e($url);
        $escapedTitle = e($mirror->display_name);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$escapedTitle}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; background: #000; overflow: hidden; }
        iframe { width: 100%; height: 100%; border: 0; }
    </style>
    <script>
        window.open = function() { return null; };
    </script>
</head>
<body>
    <iframe
        src="{$escapedUrl}"
        allowfullscreen
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
    ></iframe>
</body>
</html>
HTML;

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Handle Desustream: Extract direct MP4 file from cdn.odcloud.net or archive.org.
     */
    protected function handleDesustream(string $url, VideoMirror $mirror): Response
    {
        $cacheKey = "stream_direct_url_{$mirror->id}";

        $directVideoUrl = Cache::remember($cacheKey, 1800, function () use ($url): ?string {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Referer' => 'https://otakudesu.blog/',
                ])->timeout(6)->get($url);

                if (! $response->successful()) {
                    return null;
                }

                $body = $response->body();

                // Check const videoURL = "https://cdn.odcloud.net/..."
                if (preg_match('/(?:const\s+videoURL\s*=\s*["\']|file:\s*["\'])(https?:\/\/[^"\']+\.mp4[^"\']*)/i', $body, $matches)) {
                    return $matches[1];
                }

                // Check general MP4 in page
                if (preg_match('/https?:\/\/[^"\']+\.(?:mp4|m3u8)[^"\']*/i', $body, $matches)) {
                    return $matches[0];
                }

                return null;
            } catch (\Throwable) {
                return null;
            }
        });

        if ($directVideoUrl) {
            return response($this->renderHtml5Player($directVideoUrl, $mirror->display_name))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        // Fallback: Proxy the desustream page itself without frame-ancestors CSP
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer' => 'https://otakudesu.blog/',
            ])->timeout(6)->get($url);

            return response($response->body())->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (\Throwable) {
            return response($this->renderErrorPlayer('Gagal memuat server streaming video.'), 500);
        }
    }

    /**
     * Handle Ad-heavy hosts (Vidhide, StreamWish, FileLions): Strip intrusive ads, popups, and clickjacking overlays.
     */
    protected function handleAdHeavyHost(string $url, VideoMirror $mirror): Response
    {
        try {
            $referer = str_contains($url, 'oploverz') ? 'https://oploverz.site/' : 'https://otakudesu.blog/';
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer' => $referer,
            ])->timeout(6)->get($url);

            $html = $response->body();
            $baseUrl = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);

            // 1. Strip intrusive ad network scripts & tracking
            $html = preg_replace('/<script[^>]*media\.daly2024\.com[^>]*>.*?<\/script>/is', '', $html);
            $html = preg_replace('/<script[^>]*_aso\.loadPushHelper.*?<\/script>/is', '', $html);
            $html = preg_replace('/<script[^>]*\/ad\?type=[^>]*>.*?<\/script>/is', '', $html);
            $html = preg_replace('/<script[^>]*googletagmanager\.com[^>]*>.*?<\/script>/is', '', $html);

            // 2. Remove fake verification / ADEX elements & ad containers
            $html = preg_replace('/<div[^>]*adex[^>]*>.*?<\/div>/is', '', $html);
            $html = preg_replace('/<div[^>]*ad-overlay[^>]*>.*?<\/div>/is', '', $html);

            // 3. Fix relative URLs to absolute URLs
            $html = str_replace('src="/', 'src="'.$baseUrl.'/', $html);
            $html = str_replace('href="/', 'href="'.$baseUrl.'/', $html);

            // 4. Inject aggressive Popup & Popunder Blocker script into head
            $blockerScript = <<<'HTML'
<script>
(function() {
    // Neutralize window.open so clicks cannot trigger new tabs or popup ads
    window.open = function() {
        console.warn('[Tofunime Shield] Popup ad attempt blocked.');
        return null;
    };
    window.alert = function() { return null; };
    window.confirm = function() { return false; };

    // Neutralize clickjacking full-screen transparent overlays
    document.addEventListener('DOMContentLoaded', function() {
        var removeAdElements = function() {
            var overlays = document.querySelectorAll('div[id*="ad"], div[class*="ad"], iframe[id*="aswift"], a[target="_blank"]');
            overlays.forEach(function(el) {
                if (!el.closest('.jwplayer') && !el.closest('video')) {
                    var style = window.getComputedStyle(el);
                    if (style.position === 'fixed' || style.position === 'absolute' || parseInt(style.zIndex, 10) > 100) {
                        el.remove();
                    }
                }
            });
        };
        removeAdElements();
        setInterval(removeAdElements, 1000);
    });
})();
</script>
HTML;

            if (str_contains($html, '<head>')) {
                $html = str_replace('<head>', '<head>'.$blockerScript, $html);
            } else {
                $html = $blockerScript.$html;
            }

            return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (\Throwable) {
            return response($this->renderErrorPlayer('Gagal memuat server Vidhide.'), 500);
        }
    }

    /**
     * Handle Generic Embed with popup shield.
     */
    protected function handleGenericEmbed(string $url, VideoMirror $mirror): Response
    {
        $escapedUrl = e($url);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$mirror->display_name}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; background: #000; overflow: hidden; }
        iframe { width: 100%; height: 100%; border: 0; }
    </style>
    <script>
        // Block window.open popups
        window.open = function() { return null; };
    </script>
</head>
<body>
    <iframe
        src="{$escapedUrl}"
        allowfullscreen
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
    ></iframe>
</body>
</html>
HTML;

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Pure, Clean, Ad-Free HTML5 Video Player.
     */
    protected function renderHtml5Player(string $videoUrl, string $title): string
    {
        $escapedUrl = e($videoUrl);
        $escapedTitle = e($title);

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$escapedTitle}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%;
            height: 100%;
            background: #000;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        .player-wrap {
            position: relative;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            outline: none;
        }
        /* Custom Loading Spinner */
        .spinner {
            position: absolute;
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: #0d9488;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
            z-index: 10;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="player-wrap">
        <div id="loader" class="spinner"></div>
        <video
            id="tofunimeVideo"
            controls
            playsinline
            preload="metadata"
            autoplay
        >
            <source src="{$escapedUrl}" type="video/mp4">
            Browser Anda tidak mendukung pemutar video HTML5.
        </video>
    </div>

    <script>
        const video = document.getElementById('tofunimeVideo');
        const loader = document.getElementById('loader');

        video.addEventListener('waiting', () => loader.style.display = 'block');
        video.addEventListener('playing', () => loader.style.display = 'none');
        video.addEventListener('canplay', () => loader.style.display = 'none');

        // Prevent unwanted navigation/popups
        window.open = function() { return null; };
    </script>
</body>
</html>
HTML;
    }

    /**
     * Fallback error player view.
     */
    protected function renderErrorPlayer(string $message): string
    {
        $escapedMsg = e($message);

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
    <style>
        body { margin: 0; background: #000; color: #fff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; }
        .msg { padding: 20px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="msg">
        <p>{$escapedMsg}</p>
        <p style="margin-top: 8px; font-size: 14px; opacity: 0.7;">Silakan pilih server mirror lainnya di bawah.</p>
    </div>
</body>
</html>
HTML;
    }
}
