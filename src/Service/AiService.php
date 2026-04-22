<?php

namespace App\Service;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function suggestPsychologue(string $motif): string
    {
        $motif = trim($motif);

        if ($motif === '') {
            throw new RuntimeException('Le motif est obligatoire.');
        }

        $apiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? '';

        if ($apiKey === '' || $apiKey === 'xxxxx') {
            throw new RuntimeException('OPENAI_API_KEY est manquant dans le fichier .env.');
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Analyze this psychological problem and suggest a suitable type of psychologist: ' . $motif,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 120,
            ],
        ]);

        $data = $response->toArray(false);

        if (($data['error']['message'] ?? null) !== null) {
            throw new RuntimeException($data['error']['message']);
        }

        $suggestion = trim($data['choices'][0]['message']['content'] ?? '');

        if ($suggestion === '') {
            throw new RuntimeException('Aucune suggestion recue.');
        }

        return $suggestion;
    }
}
