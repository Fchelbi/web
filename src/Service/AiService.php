<?php

namespace App\Service;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiService
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ?string $geminiApiKey = null,
    ) {
    }

    /**
     * Suggest a psychologist based on motif (for backward compatibility).
     * Currently ignores motif and returns most available coach.
     * 
     * @param string $motif Consultation motif (currently unused)
     * @return string|null Coach name or null
     */
    public function suggestPsychologue(string $motif): ?string
    {
        $coach = $this->suggestMostAvailablePsy();
        return $coach ? $coach->getName() : null;
    }

    /**
     * Get the most available psychologist using Gemini AI.
     * 
     * Falls back to simple PHP logic if Gemini fails.
     * 
     * @param \DateTimeInterface|null $fromDate Optional: filter consultations from this date onwards
     * @return User|null The most available psychologist (Coach)
     */
    public function suggestMostAvailablePsy(?\DateTimeInterface $fromDate = null): ?User
    {
        // Fetch all coaches with their consultation counts
        $coachesData = $this->getCoachesWithConsultationCounts($fromDate);

        if (empty($coachesData)) {
            $this->logger->warning('No coaches available');
            return null;
        }

        // Try Gemini API first
        if ($this->geminiApiKey) {
            try {
                $selectedPsyId = $this->selectCoachViaGemini($coachesData);
                if ($selectedPsyId !== null) {
                    $psy = $this->entityManager->getRepository(User::class)->find($selectedPsyId);
                    if ($psy) {
                        $this->logger->info('Selected coach via Gemini AI', ['coach_id' => $selectedPsyId]);
                        return $psy;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Gemini API failed, using fallback', ['error' => $e->getMessage()]);
            }
        } else {
            $this->logger->info('Gemini API key not configured, using fallback');
        }

        // Fallback: return coach with minimum consultations
        return $this->selectCoachByMinConsultations($coachesData);
    }

    /**
     * Get all coaches with their consultation counts.
     * Public method for display/debugging purposes.
     * Uses optimized query with GROUP BY to avoid N+1 queries.
     *
     * @param \DateTimeInterface|null $fromDate Optional: count only consultations from this date
     * @return array<int, array{id: int, name: string, consultations: int}>
     */
    public function getCoachesWithConsultationCounts(?\DateTimeInterface $fromDate = null): array
    {
        try {
            $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');

            $qb->select('u.id, CONCAT(u.prenom, \' \', u.nom) as name, COUNT(c.id) as consultations')
                ->leftJoin(ConsultationEnLigne::class, 'c', 'WITH', 'c.psychologue = u.id')
                ->where('u.role = :role')
                ->setParameter('role', User::ROLE_COACH)
                ->groupBy('u.id')
                ->orderBy('consultations', 'ASC');

            // Optional: filter by date (count only upcoming consultations)
            if ($fromDate !== null) {
                $qb->andWhere('c.dateConsultation IS NULL OR c.dateConsultation >= :fromDate')
                    ->setParameter('fromDate', $fromDate);
            }

            $results = $qb->getQuery()->getResult();

            return array_map(
                fn($row) => [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'consultations' => (int) $row['consultations'],
                ],
                $results
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch coaches', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Call Gemini API to select the most available coach.
     *
     * @param array $coachesData Array of coaches with consultation counts
     * @return int|null Selected coach ID, or null if selection failed
     */
    private function selectCoachViaGemini(array $coachesData): ?int
    {
        $prompt = $this->buildGeminiPrompt($coachesData);

        try {
            $response = $this->httpClient->request('POST', self::GEMINI_API_URL . '?key=' . $this->geminiApiKey, [
                'timeout' => 5,
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'topP' => 0.8,
                        'topK' => 10,
                        'maxOutputTokens' => 100,
                    ],
                ],
            ]);

            $data = $response->toArray();

            // Extract the response text
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $data['candidates'][0]['content']['parts'][0]['text'];
                
                // Try to extract coach ID from the response
                if (preg_match('/ID[:\s]+(\d+)|coach[:\s]+ID[:\s]+(\d+)/i', $text, $matches)) {
                    $coachId = (int) ($matches[1] ?? $matches[2]);
                    
                    // Validate the ID exists in our coaches data
                    if (in_array($coachId, array_column($coachesData, 'id'))) {
                        return $coachId;
                    }
                }

                $this->logger->warning('Could not parse coach ID from Gemini response', ['response' => $text]);
                return null;
            }

            $this->logger->warning('Unexpected Gemini API response structure', ['response' => $data]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Gemini API request failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Build the prompt to send to Gemini API.
     *
     * @param array $coachesData Array of coaches with consultation counts
     * @return string The prompt text
     */
    private function buildGeminiPrompt(array $coachesData): string
    {
        $coachesList = '';
        foreach ($coachesData as $coach) {
            $coachesList .= sprintf(
                "- Coach ID: %d, Name: %s, Current Consultations: %d\n",
                $coach['id'],
                $coach['name'],
                $coach['consultations']
            );
        }

        return <<<PROMPT
You are a scheduling assistant for a psychology consultation platform.

Below is a list of available coaches and their current consultation counts:

$coachesList

Your task: Suggest the MOST AVAILABLE coach (the one with the LOWEST number of consultations).

Return ONLY the coach ID of the most available coach. Format your response as:
"Selected Coach ID: {id}"

Example response: "Selected Coach ID: 2"
PROMPT;
    }

    /**
     * Fallback: Select coach with minimum consultations using PHP.
     *
     * @param array $coachesData Array of coaches with consultation counts
     * @return User|null The coach with the lowest consultation count
     */
    private function selectCoachByMinConsultations(array $coachesData): ?User
    {
        if (empty($coachesData)) {
            return null;
        }

        // Sort by consultations (ascending) and get the first one
        usort($coachesData, fn($a, $b) => $a['consultations'] <=> $b['consultations']);
        $selectedCoachId = $coachesData[0]['id'];

        $psy = $this->entityManager->getRepository(User::class)->find($selectedCoachId);
        
        if ($psy) {
            $this->logger->info('Selected coach via fallback method', [
                'coach_id' => $selectedCoachId,
                'consultations' => $coachesData[0]['consultations'],
            ]);
        }

        return $psy;
    }
}
