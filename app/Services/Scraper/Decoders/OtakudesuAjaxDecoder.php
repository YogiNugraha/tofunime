<?php

declare(strict_types=1);

namespace App\Services\Scraper\Decoders;

use App\Models\SiteSetting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtakudesuAjaxDecoder
{
    protected string $defaultNonceAction = 'aa1208d27f29ca340c92c66d1926f13f';

    protected string $defaultStreamAction = '2a3505c93b0035d3f455df82bf976b84';

    /**
     * Decode the data-content JSON payload and retrieve the iframe embed URL.
     *
     * @param  string  $dataContentBase64  Base64 string from data-content attribute
     * @param  string|null  $nonce  WordPress AJAX nonce
     * @param  string|null  $htmlContext  Raw HTML of the episode page for regex extraction
     */
    public function decodeMirror(
        string $dataContentBase64,
        ?string $nonce = null,
        ?string $htmlContext = null
    ): ?string {
        try {
            $decodedJson = base64_decode($dataContentBase64);
            /** @var array{id?: int|string, i?: int|string, q?: string}|null $payload */
            $payload = json_decode($decodedJson, true);

            if (! is_array($payload) || ! isset($payload['id'], $payload['i'], $payload['q'])) {
                Log::warning('OtakudesuAjaxDecoder: Invalid payload structure', ['raw' => $dataContentBase64]);

                return null;
            }

            try {
                $baseUrl = (string) SiteSetting::get('scraper_otakudesu_base_url', 'https://otakudesu.blog');
            } catch (\Throwable) {
                $baseUrl = 'https://otakudesu.blog';
            }
            $ajaxUrl = rtrim($baseUrl, '/').'/wp-admin/admin-ajax.php';

            // Determine actions if available in page script
            $nonceAction = $this->defaultNonceAction;
            $streamAction = $this->defaultStreamAction;

            if ($htmlContext !== null) {
                $actions = $this->extractActionsFromHtml($htmlContext);
                if (! empty($actions['nonce_action'])) {
                    $nonceAction = $actions['nonce_action'];
                }
                if (! empty($actions['stream_action'])) {
                    $streamAction = $actions['stream_action'];
                }
            }

            // Step 1: Obtain Nonce if not already cached/provided
            $activeNonce = $nonce ?? $this->fetchNonce($ajaxUrl, $nonceAction);

            // Step 2: Request stream iframe
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $baseUrl,
            ])
                ->asForm()
                ->retry(2, 500)
                ->timeout(12)
                ->post($ajaxUrl, [
                    'id' => $payload['id'],
                    'i' => $payload['i'],
                    'q' => $payload['q'],
                    'nonce' => $activeNonce,
                    'action' => $streamAction,
                ]);

            if (! $response->successful()) {
                Log::warning('OtakudesuAjaxDecoder: AJAX request failed', [
                    'status' => $response->status(),
                    'payload' => $payload,
                ]);

                return null;
            }

            // Step 3: Decode JSON/Base64 response HTML
            $json = $response->json();
            $rawBase64 = is_array($json) && isset($json['data'])
                ? (string) $json['data']
                : trim($response->body());

            $decodedHtml = base64_decode($rawBase64);
            if (empty($decodedHtml)) {
                $decodedHtml = $rawBase64;
            }

            $url = $this->extractSrcFromIframe($decodedHtml);

            if ($url !== null && str_contains($url, 'maintenance')) {
                return null;
            }

            return $url;
        } catch (Exception $e) {
            Log::error('OtakudesuAjaxDecoder: Exception during decode', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch WordPress Nonce via admin-ajax.php.
     */
    public function fetchNonce(string $ajaxUrl, ?string $action = null): string
    {
        $actionName = $action ?? $this->defaultNonceAction;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
                ->asForm()
                ->retry(2, 500)
                ->timeout(10)
                ->post($ajaxUrl, [
                    'action' => $actionName,
                ]);

            if ($response->successful()) {
                $body = trim($response->body());
                $json = json_decode($body, true);

                if (is_array($json) && isset($json['data'])) {
                    return (string) $json['data'];
                }

                if (! empty($body) && strlen($body) < 100) {
                    return $body;
                }
            }
        } catch (Exception $e) {
            Log::warning('OtakudesuAjaxDecoder: Failed to fetch nonce', ['error' => $e->getMessage()]);
        }

        return '';
    }

    /**
     * Extract iframe src URL from raw HTML string.
     */
    public function extractSrcFromIframe(string $html): ?string
    {
        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            $url = $matches[1];
            // Normalize protocol-relative URLs
            if (str_starts_with($url, '//')) {
                $url = 'https:'.$url;
            }

            return $url;
        }

        // Direct URL fallback if response is already a URL
        if (filter_var($html, FILTER_VALIDATE_URL)) {
            return $html;
        }

        return null;
    }

    /**
     * Extract dynamic ajax actions from inline scripts in episode HTML.
     *
     * @return array{nonce_action?: string, stream_action?: string}
     */
    public function extractActionsFromHtml(string $html): array
    {
        $actions = [];

        // 1. Nonce action pattern: data: { action: "aa1208d27f29ca340c92c66d1926f13f" }
        if (preg_match('/data\s*:\s*\{\s*action\s*:\s*["\']([a-f0-9]{32})["\']\s*\}/i', $html, $mNonce)) {
            $actions['nonce_action'] = $mNonce[1];
        }

        // 2. Stream action pattern: ... nonce: ..., action: "2a3505c93b0035d3f455df82bf976b84"
        if (preg_match('/nonce\s*:[^,]+,\s*action\s*:\s*["\']([a-f0-9]{32})["\']/i', $html, $mStream)) {
            $actions['stream_action'] = $mStream[1];
        }

        // 3. Fallback: If specific patterns not found, parse general action hex hashes
        if (empty($actions['nonce_action']) || empty($actions['stream_action'])) {
            if (preg_match_all('/action\s*:\s*["\']([a-f0-9]{32})["\']/i', $html, $matches)) {
                if (count($matches[1]) >= 2) {
                    // In Otakudesu ternary, stream action appears first, nonce action second
                    $actions['stream_action'] = $actions['stream_action'] ?? $matches[1][0];
                    $actions['nonce_action'] = $actions['nonce_action'] ?? $matches[1][1];
                }
            }
        }

        return $actions;
    }
}
