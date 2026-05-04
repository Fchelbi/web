<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslateService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Translate text using MyMemory API (free, no key needed).
     *
     * @param string $text     Text to translate
     * @param string $from     Source language (e.g., 'fr')
     * @param string $to       Target language (e.g., 'en', 'ar')
     * @return string|null     Translated text or null on failure
     */
    public function translate(string $text, string $from = 'fr', string $to = 'en'): ?string
    {
        if (empty(trim($text))) {
            return null;
        }

        // MyMemory has a 500 char limit per request — split if needed
        if (mb_strlen($text) > 450) {
            $text = mb_substr($text, 0, 450);
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.mymemory.translated.net/get', [
                'query' => [
                    'q'       => $text,
                    'langpair' => $from . '|' . $to,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['responseData']['translatedText'])) {
                return $data['responseData']['translatedText'];
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get available language options.
     */
    public function getLanguages(): array
    {
        return [
            'en' => 'English',
            'ar' => 'العربية',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'tr' => 'Türkçe',
        ];
    }
}