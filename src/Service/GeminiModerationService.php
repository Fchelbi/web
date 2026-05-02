<?php

namespace App\Service;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

class GeminiModerationService
{
    private string $apiKey;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
    }

    /**
     * Analyze a single post with Gemini AI for content moderation.
     * Returns: ['flagged' => bool, 'reason' => string, 'confidence' => float, 'categories' => array]
     */
    public function moderatePost(Post $post): array
    {
        $prompt = $this->buildPrompt($post->getTitle() ?? '', $post->getContent() ?? '');

        $result = $this->callGeminiApi($prompt);

        if ($result['flagged']) {
            $post->setIsFlagged(true);
            $post->setFlagReason($result['reason']);
            $post->setModerationStatus('pending');
            $this->em->flush();
        }

        return $result;
    }

    /**
     * Scan all provided posts with Gemini AI.
     * Returns summary: ['scanned' => int, 'flagged' => int, 'results' => array]
     */
    public function moderateAllPosts(array $posts): array
    {
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
                'title' => $post->getTitle(),
                'flagged' => $result['flagged'],
                'reason' => $result['reason'],
            ];
        }

        $this->em->flush();

        return [
            'scanned' => $scanned,
            'flagged' => $flagged,
            'results' => $results,
        ];
    }

    private function buildPrompt(string $title, string $content): string
    {
        return <<<PROMPT
You are a content moderation AI for a community forum. Analyze the following forum post and determine if it contains any of the following:

1. Inappropriate or offensive language (profanity, slurs)
2. Abusive or harassing content (bullying, threats toward individuals)
3. Harmful or dangerous content (self-harm, dangerous activities)
4. Sexual or explicit content (pornography, sexual solicitation)
5. Violent content (graphic violence, threats of violence)
6. Hate speech or discrimination (racism, sexism, homophobia, etc.)
7. Spam or misleading content (scams, phishing, clickbait)
8. Unsafe content (doxxing, personal information exposure)

You MUST respond ONLY with valid JSON in this exact format, nothing else:
{"flagged": true, "reason": "Brief explanation of why it was flagged", "confidence": 0.95, "categories": ["hate_speech", "violence"]}

If the content is safe and appropriate, respond with:
{"flagged": false, "reason": "Content is appropriate and safe", "confidence": 0.98, "categories": []}

POST TITLE: {$title}
POST CONTENT: {$content}
PROMPT;
    }

    private function callGeminiApi(string $prompt): array
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;

        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 300,
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            return [
                'flagged' => false,
                'reason' => 'Moderation service unavailable: ' . ($error ?: "HTTP $httpCode"),
                'confidence' => 0.0,
                'categories' => [],
            ];
        }

        return $this->parseGeminiResponse($response);
    }

    private function parseGeminiResponse(string $response): array
    {
        $default = [
            'flagged' => false,
            'reason' => 'Unable to parse moderation response',
            'confidence' => 0.0,
            'categories' => [],
        ];

        $data = json_decode($response, true);
        if (!$data) {
            return $default;
        }

        // Extract the text content from Gemini's response
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (empty($text)) {
            return $default;
        }

        // Clean up: Gemini may wrap JSON in markdown code blocks
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
            'flagged' => (bool) ($result['flagged'] ?? false),
            'reason' => (string) ($result['reason'] ?? 'No reason provided'),
            'confidence' => (float) ($result['confidence'] ?? 0.0),
            'categories' => (array) ($result['categories'] ?? []),
        ];
    }
}
