<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YouTubeService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(string $apiKey, HttpClientInterface $httpClient)
    {
        $this->apiKey     = $apiKey;
        $this->httpClient = $httpClient;
    }

    /**
     * Extract video ID from any YouTube URL format.
     * Supports: youtu.be/ID, watch?v=ID, /shorts/ID, /embed/ID
     */
    public function extractVideoId(string $url): ?string
    {
        $patterns = [
            '#youtu\.be/([a-zA-Z0-9_-]{11})#',
            '#[?&]v=([a-zA-Z0-9_-]{11})#',
            '#youtube\.com/shorts/([a-zA-Z0-9_-]{11})#',
            '#youtube\.com/embed/([a-zA-Z0-9_-]{11})#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Fetch video details from YouTube Data API v3.
     * Returns ['title', 'duration', 'thumbnail', 'embedUrl'] or null on failure.
     */
    public function getVideoDetails(string $url): ?array
    {
        $videoId = $this->extractVideoId($url);

        if (!$videoId) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/videos', [
                'query' => [
                    'part' => 'snippet,contentDetails',
                    'id'   => $videoId,
                    'key'  => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();

            if (empty($data['items'])) {
                return null;
            }

            $item    = $data['items'][0];
            $snippet = $item['snippet'];
            $details = $item['contentDetails'];

            return [
                'videoId'   => $videoId,
                'title'     => $snippet['title'],
                'thumbnail' => $snippet['thumbnails']['high']['url']
                               ?? $snippet['thumbnails']['medium']['url']
                               ?? $snippet['thumbnails']['default']['url'],
                'duration'  => $this->parseDuration($details['duration']),
                'embedUrl'  => 'https://www.youtube.com/embed/' . $videoId,
            ];
        } catch (\Exception $e) {
            // Log the error in production; for now just return null
            return null;
        }
    }

    /**
     * Convert ISO 8601 duration (PT1H2M30S) to readable format (1h 02m 30s).
     */
    private function parseDuration(string $iso): string
    {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $iso, $m);

        $h = (int) ($m[1] ?? 0);
        $min = (int) ($m[2] ?? 0);
        $s = (int) ($m[3] ?? 0);

        if ($h > 0) {
            return sprintf('%dh %02dm %02ds', $h, $min, $s);
        }
        if ($min > 0) {
            return sprintf('%dm %02ds', $min, $s);
        }
        return sprintf('%ds', $s);
    }

    /**
 * Search YouTube videos by keyword.
 * Returns array of video results with title, thumbnail, duration, URL.
 */
public function searchVideos(string $query, int $maxResults = 5): array
{
    if (empty(trim($query))) {
        return [];
    }

    try {
        // Step 1: Search for videos
        $searchResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
            'query' => [
                'part'       => 'snippet',
                'q'          => $query,
                'type'       => 'video',
                'maxResults' => $maxResults,
                'key'        => $this->apiKey,
            ],
        ]);

        $searchData = $searchResponse->toArray();

        if (empty($searchData['items'])) {
            return [];
        }

        // Collect video IDs to fetch durations
        $videoIds = array_map(
            fn($item) => $item['id']['videoId'],
            $searchData['items']
        );

        // Step 2: Get video details (duration)
        $detailsResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/videos', [
            'query' => [
                'part' => 'contentDetails,snippet',
                'id'   => implode(',', $videoIds),
                'key'  => $this->apiKey,
            ],
        ]);

        $detailsData = $detailsResponse->toArray();
        $durationMap = [];
        foreach ($detailsData['items'] ?? [] as $item) {
            $durationMap[$item['id']] = $this->parseDuration($item['contentDetails']['duration']);
        }

        // Build results
        $results = [];
        foreach ($searchData['items'] as $item) {
            $videoId = $item['id']['videoId'];
            $snippet = $item['snippet'];

            $results[] = [
                'videoId'   => $videoId,
                'title'     => $snippet['title'],
                'thumbnail' => $snippet['thumbnails']['medium']['url']
                               ?? $snippet['thumbnails']['default']['url'],
                'channel'   => $snippet['channelTitle'],
                'duration'  => $durationMap[$videoId] ?? '',
                'url'       => 'https://www.youtube.com/watch?v=' . $videoId,
                'embedUrl'  => 'https://www.youtube.com/embed/' . $videoId,
            ];
        }

        return $results;

    } catch (\Exception $e) {
        return [];
    }
}
}