<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(string $apiKey, HttpClientInterface $httpClient)
    {
        $this->apiKey     = $apiKey;
        $this->httpClient = $httpClient;
    }

    /**
     * Generate quiz questions from a formation topic.
     */
    public function generateQuizQuestions(string $formationTitle, ?string $formationDescription, int $count = 5): ?array
    {
        $desc = $formationDescription ?? 'Pas de description disponible';

        $prompt = <<<PROMPT
Tu es un expert en création de quiz éducatifs pour une plateforme de santé et bien-être appelée EchoCare.

Génère exactement {$count} questions à choix multiples sur le sujet suivant :
- Titre : {$formationTitle}
- Description : {$desc}

Règles :
- Chaque question doit avoir exactement 4 réponses possibles
- Une seule réponse correcte par question
- Les questions doivent être variées
- Niveau adapté à des patients (pas trop technique)
- Chaque question vaut 1 point

Réponds UNIQUEMENT avec un tableau JSON valide, sans markdown, sans backticks, sous ce format exact :
[
  {
    "question": "Texte de la question ?",
    "points": 1,
    "answers": [
      {"text": "Réponse A", "correct": false},
      {"text": "Réponse B", "correct": true},
      {"text": "Réponse C", "correct": false},
      {"text": "Réponse D", "correct": false}
    ]
  }
]
PROMPT;

        $text = $this->callAI($prompt);
        if (!$text) {
            error_log('[GeminiService] callAI returned null for generateQuizQuestions');
            return null;
        }

        $questions = $this->extractJsonArray($text);
        if (!$questions) {
            error_log('[GeminiService] JSON parse failed. Raw response: ' . substr($text, 0, 500));
            return null;
        }

        return $questions;
    }

    /**
     * Analyze sentiment of a feedback text.
     */
    public function analyzeSentiment(string $feedbackText): ?array
    {
        $escaped = addslashes($feedbackText);

        $prompt = <<<PROMPT
Tu es un analyseur de sentiments pour une plateforme de santé EchoCare.

Analyse le sentiment du feedback patient suivant :
"{$escaped}"

Réponds UNIQUEMENT avec un objet JSON valide, sans markdown, sans backticks :
{
  "sentiment": "positive",
  "confidence": 0.85,
  "summary": "Résumé en une phrase du feedback"
}

Le champ sentiment doit être exactement "positive", "negative" ou "neutral".
PROMPT;

        $text = $this->callAI($prompt, 0.3, 256);
        if (!$text) return null;

        $result = $this->extractJsonObject($text);
        if (!$result || !isset($result['sentiment'])) return null;

        return $result;
    }

    /**
     * AI Chatbot — answers questions about a formation.
     */
    public function chatAboutFormation(
        string $formationTitle,
        ?string $formationDescription,
        ?string $formationCategory,
        string $userMessage,
        array $history = []
    ): ?string {
        $desc = $formationDescription ?? 'Pas de description disponible';
        $cat  = $formationCategory ?? 'Non classée';

        $systemPrompt = "Tu es l'assistant IA d'EchoCare, une plateforme de santé et bien-être. "
            . "Tu aides les patients à comprendre la formation suivante : "
            . "Titre: {$formationTitle}, Catégorie: {$cat}, Description: {$desc}. "
            . "Réponds en français, sois concis (3-4 phrases max), reste dans le contexte de la formation. "
            . "Sois encourageant et bienveillant. Ne fournis jamais de diagnostic médical.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['text'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $this->callAIChat($messages);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Extract a JSON array from a string that may contain markdown fences,
     * extra text before/after the array, or thinking tags.
     */
    private function extractJsonArray(string $text): ?array
    {
        // Strip <think>...</think> blocks (some models include these)
        $text = preg_replace('/<think>.*?<\/think>/s', '', $text);

        // Strip markdown fences
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/```/', '', $text);
        $text = trim($text);

        // Try direct decode first
        $decoded = json_decode($text, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }

        // Find the first [ ... ] block in the text
        $start = strpos($text, '[');
        $end   = strrpos($text, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $json    = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($json, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Extract a JSON object from a string that may contain extra text.
     */
    private function extractJsonObject(string $text): ?array
    {
        $text = preg_replace('/<think>.*?<\/think>/s', '', $text);
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/```/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $json    = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Call API with a single user prompt.
     */
    private function callAI(string $prompt, float $temperature = 0.7, int $maxTokens = 2048): ?string
    {
        return $this->callAIChat(
            [['role' => 'user', 'content' => $prompt]],
            $temperature,
            $maxTokens
        );
    }

    /**
     * Call OpenRouter API — tries multiple free models in order until one works.
     */
    private function callAIChat(array $messages, float $temperature = 0.7, int $maxTokens = 2048): ?string
    {
        $models = [
            'meta-llama/llama-3.1-8b-instruct:free',
            'meta-llama/llama-4-scout:free',
            'deepseek/deepseek-chat-v3-0324:free',
            'qwen/qwen3-8b:free',
            'mistralai/mistral-7b-instruct:free',
        ];

        foreach ($models as $model) {
            try {
                $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                        'HTTP-Referer'  => 'https://echocare.local',
                        'X-Title'       => 'EchoCare',
                    ],
                    'json' => [
                        'model'       => $model,
                        'messages'    => $messages,
                        'temperature' => $temperature,
                        'max_tokens'  => $maxTokens,
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();

                // Check for API-level error
                if (isset($data['error'])) {
                    error_log("[GeminiService] Model {$model} error: " . json_encode($data['error']));
                    continue;
                }

                $text = $data['choices'][0]['message']['content'] ?? null;
                if ($text) {
                    error_log("[GeminiService] Success with model: {$model}");
                    return $text;
                }

            } catch (\Exception $e) {
                error_log("[GeminiService] Model {$model} exception: " . $e->getMessage());
                continue;
            }
        }

        error_log('[GeminiService] All models failed.');
        return null;
    }
}