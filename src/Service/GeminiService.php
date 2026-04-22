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

Réponds UNIQUEMENT avec un JSON valide, sans markdown, sans backticks, sous ce format exact :
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
        if (!$text) return null;

        // Clean markdown fences
        $text = preg_replace('/^```(?:json)?\s*/', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $questions = json_decode($text, true);
        if (!is_array($questions) || empty($questions)) return null;

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

Réponds UNIQUEMENT avec un JSON valide, sans markdown, sans backticks :
{
  "sentiment": "positive",
  "confidence": 0.85,
  "summary": "Résumé en une phrase du feedback"
}

Le champ sentiment doit être exactement "positive", "negative" ou "neutral".
PROMPT;

        $text = $this->callAI($prompt, 0.3, 256);
        if (!$text) return null;

        $text = preg_replace('/^```(?:json)?\s*/', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $result = json_decode($text, true);
        if (!is_array($result) || !isset($result['sentiment'])) return null;

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

        // Build messages array for OpenRouter
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add history
        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['text'],
            ];
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $this->callAIChat($messages);
    }

    /**
     * Call OpenRouter API with a single prompt.
     */
    private function callAI(string $prompt, float $temperature = 0.7, int $maxTokens = 2048): ?string
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->callAIChat($messages, $temperature, $maxTokens);
    }

    /**
     * Call OpenRouter API with messages array.
     * Uses free models: google/gemini-2.0-flash-exp:free or meta-llama/llama-3.1-8b-instruct:free
     */
    private function callAIChat(array $messages, float $temperature = 0.7, int $maxTokens = 2048): ?string
    {
        // Try multiple free models in order
        $models = [
            'google/gemini-2.0-flash-exp:free',
            'meta-llama/llama-4-scout:free',
            'deepseek/deepseek-chat-v3-0324:free',
            'qwen/qwen3-8b:free',
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
                ]);

                $data = $response->toArray();

                $text = $data['choices'][0]['message']['content'] ?? null;
                if ($text) {
                    return $text;
                }

            } catch (\Exception $e) {
                // Try next model
                continue;
            }
        }

        return null;
    }
}