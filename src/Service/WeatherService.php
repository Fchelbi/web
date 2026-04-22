<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(string $apiKey, HttpClientInterface $httpClient)
    {
        $this->apiKey     = $apiKey;
        $this->httpClient = $httpClient;
    }

    /**
     * Get current weather + health tip for a city.
     * Returns ['city', 'temp', 'condition', 'icon', 'humidity', 'wind', 'healthTip']
     */
    public function getWeatherWithTip(string $city = 'Tunis'): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.weatherapi.com/v1/current.json', [
                'query' => [
                    'key' => $this->apiKey,
                    'q'   => $city,
                    'lang' => 'fr',
                ],
            ]);

            $data = $response->toArray();
            $current = $data['current'];
            $temp = $current['temp_c'];
            $condition = $current['condition']['text'];
            $humidity = $current['humidity'];

            // Generate health tip based on weather
            $tip = $this->generateHealthTip($temp, $condition, $humidity);

            return [
                'city'      => $data['location']['name'],
                'country'   => $data['location']['country'],
                'temp'      => $temp,
                'feelsLike' => $current['feelslike_c'],
                'condition' => $condition,
                'icon'      => 'https:' . $current['condition']['icon'],
                'humidity'  => $humidity,
                'wind'      => $current['wind_kph'],
                'uv'        => $current['uv'] ?? 0,
                'healthTip' => $tip,
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateHealthTip(float $temp, string $condition, int $humidity): string
    {
        $tips = [];

        // Temperature-based tips
        if ($temp >= 35) {
            $tips[] = "🔥 Chaleur extrême ! Restez hydraté, évitez les exercices en plein air entre 12h et 16h.";
        } elseif ($temp >= 28) {
            $tips[] = "☀️ Il fait chaud ! Buvez au moins 2L d'eau et privilégiez les exercices en intérieur.";
        } elseif ($temp >= 20) {
            $tips[] = "🌤️ Température idéale pour les activités en extérieur ! Profitez d'une marche ou du jogging.";
        } elseif ($temp >= 10) {
            $tips[] = "🧥 Temps frais — échauffez-vous bien avant tout exercice physique.";
        } else {
            $tips[] = "❄️ Il fait froid ! Couvrez-vous bien et privilégiez les exercices en intérieur.";
        }

        // Humidity tips
        if ($humidity > 80) {
            $tips[] = "💧 Humidité élevée — respirez calmement et faites des pauses pendant l'effort.";
        }

        // Condition-based tips
        $condLower = strtolower($condition);
        if (str_contains($condLower, 'pluie') || str_contains($condLower, 'rain')) {
            $tips[] = "🌧️ Il pleut — parfait pour une séance de méditation ou de yoga en intérieur !";
        }
        if (str_contains($condLower, 'soleil') || str_contains($condLower, 'sunny') || str_contains($condLower, 'ensoleillé')) {
            $tips[] = "🧴 Pensez à la crème solaire si vous faites du sport en extérieur.";
        }

        return implode(' ', $tips);
    }
}