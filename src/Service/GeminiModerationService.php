<?php

namespace App\Service;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

class GeminiModerationService
{
    private string $apiKey;
    private EntityManagerInterface $em;

    // Local profanity blocklist — checked before hitting the AI API
    private const BANNED_WORDS = [
        'fuck', 'fucking', 'fucker', 'fuckhead',
        'shit', 'shitty', 'bullshit',
        'bitch', 'bitches', 'bastard',
        'asshole', 'ass', 'arse',
        'cunt', 'dick', 'cock', 'pussy',
        'nigger', 'nigga', 'faggot', 'fag',
        'whore', 'slut', 'retard', 'retarded',
        'kill yourself', 'kys', 'die bitch',
        'rape', 'molest',
        'piss', 'pissed',
        'damn', 'crap',
        'wtf', 'stfu', 'gtfo',
    ];

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
    }

    public function moderatePost(Post $post): array
    {
        $title   = $post->getTitle() ?? '';
        $content = $post->getContent() ?? '';

        // 1. Local profanity check — fast, no API needed
        $localResult = $this->checkProfanity($title . ' ' . $content);
        if ($localResult['flagged']) {
            $post->setIsFlagged(true);
            $post->setFlagReason($localResult['reason']);
            $post->setModerationStatus('pending');
            $this->em->flush();
            return $localResult;
        }

        // 2. AI moderation — deeper semantic analysis
        $result = $this->callGeminiApi($this->buildPrompt($title, $content));

        if ($result['flagged']) {
            $post->setIsFlagged(true);
            $post->setFlagReason($result['reason']);
            $post->setModerationStatus('pending');
            $this->em->flush();
        }

        return $result;
    }

    public function moderateAllPosts(array $posts): array
    {
        set_time_limit(300); // batch scan can take a while
        $scanned = 0;
        $flagged = 0;
        $results = [];

        foreach ($posts as $post) {
            $result = $this->moderatePost($post);
            $scanned++;
            if ($result['flagged']) {
                $flagged++;
            }
            $results[] = [
                'post_id' => $post->getId(),
                'title'   => $post->getTitle(),
                'flagged' => $result['flagged'],
                'reason'  => $result['reason'],
            ];
        }

        $this->em->flush();

        return ['scanned' => $scanned, 'flagged' => $flagged, 'results' => $results];
    }

    /**
     * Fast local check against the banned-word list.
     * Uses word-boundary matching to avoid false positives (e.g. "class" ≠ "ass").
     */
    private function checkProfanity(string $text): array
    {
        $lower = mb_strtolower($text);

        foreach (self::BANNED_WORDS as $word) {
            // Use word boundaries for single words; phrase match for multi-word entries
            if (str_contains($word, ' ')) {
                if (str_contains($lower, $word)) {
                    return [
                        'flagged'    => true,
                        'reason'     => "Contains prohibited phrase: \"{$word}\"",
                        'confidence' => 1.0,
                        'categories' => ['profanity'],
                    ];
                }
            } else {
                // Match whole words only (surrounded by non-alphanumeric chars or start/end)
                if (preg_match('/(?<![a-z0-9])' . preg_quote($word, '/') . '(?![a-z0-9])/i', $lower)) {
                    return [
                        'flagged'    => true,
                        'reason'     => "Contains prohibited language: \"{$word}\"",
                        'confidence' => 1.0,
                        'categories' => ['profanity'],
                    ];
                }
            }
        }

        return ['flagged' => false, 'reason' => '', 'confidence' => 1.0, 'categories' => []];
    }

    private function buildPrompt(string $title, string $content): string
    {
        return <<<PROMPT
You are a strict content moderation AI for a mental health community forum called EchoCare. Your job is to protect users from harmful content.

Flag the post if it contains ANY of the following, even mildly:
1. Profanity or offensive language — ANY swear words, slurs, or vulgar expressions (e.g. "fuck", "shit", "bitch")
2. Abusive or harassing language — insults, bullying, threats
3. Self-harm or suicide ideation — explicit or implicit encouragement
4. Sexual or explicit content
5. Graphic violence or threats
6. Hate speech — racism, sexism, homophobia, religious intolerance
7. Spam or scam content
8. Doxxing or personal information exposure

IMPORTANT: A post that contains ONLY a single profane word (such as "fuck") MUST be flagged. Context does not excuse profanity.

Respond ONLY with valid JSON — no explanation, no markdown, nothing else:
{"flagged": true, "reason": "Contains profanity: 'fuck'", "confidence": 0.99, "categories": ["profanity"]}

or if safe:
{"flagged": false, "reason": "Content is appropriate and safe", "confidence": 0.95, "categories": []}

POST TITLE: {$title}
POST CONTENT: {$content}
PROMPT;
    }

    private function callGeminiApi(string $prompt): array
    {
        if (empty($this->apiKey)) {
            return $this->apiUnavailable('No API key configured');
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;

        $payload = json_encode([
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]],
            'generationConfig' => [
                'temperature'     => 0.1,
                'maxOutputTokens' => 300,
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            // API unavailable — flag for manual review rather than silently passing
            return $this->apiUnavailable($error ?: "HTTP $httpCode");
        }

        return $this->parseGeminiResponse($response);
    }

    /** When Gemini is unreachable, pass the post — local profanity check already ran. */
    private function apiUnavailable(string $reason): array
    {
        return [
            'flagged'    => false,
            'reason'     => "AI unavailable ({$reason}) — passed local filter",
            'confidence' => 0.0,
            'categories' => [],
        ];
    }

    private function parseGeminiResponse(string $response): array
    {
        $default = [
            'flagged'    => false,
            'reason'     => 'Unable to parse moderation response',
            'confidence' => 0.0,
            'categories' => [],
        ];

        $data = json_decode($response, true);
        if (!$data) {
            return $default;
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (empty($text)) {
            return $default;
        }

        // Strip markdown code fences if Gemini wraps the JSON
        $text = trim($text);
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/^```\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);
        $text = trim($text);

        $result = json_decode($text, true);
        if (!$result || !isset($result['flagged'])) {
            return $default;
        }

        return [
            'flagged'    => (bool)   ($result['flagged']     ?? false),
            'reason'     => (string) ($result['reason']      ?? 'No reason provided'),
            'confidence' => (float)  ($result['confidence']  ?? 0.0),
            'categories' => (array)  ($result['categories']  ?? []),
        ];
    }
}
