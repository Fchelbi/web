<?php

namespace App\Service;

use App\Entity\ConsultationEnLigne;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GoogleMeetService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function createMeetLink(ConsultationEnLigne $consultation): string
    {
        $dateConsultation = $consultation->getDateConsultation();

        if ($dateConsultation === null) {
            throw new RuntimeException('La date de consultation est obligatoire pour generer un lien Meet.');
        }

        $calendarService = new Calendar($this->createClient());
        $calendarId = $_ENV['GOOGLE_CALENDAR_ID'] ?? $_SERVER['GOOGLE_CALENDAR_ID'] ?? 'primary';

        $start = (clone $dateConsultation);
        $end = (clone $dateConsultation)->modify('+1 hour');

        $event = new Event([
            'summary' => 'Consultation EchoCare',
            'description' => $this->buildDescription($consultation),
            'start' => new EventDateTime([
                'dateTime' => $start->format(DATE_RFC3339),
                'timeZone' => $start->getTimezone()->getName(),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $end->format(DATE_RFC3339),
                'timeZone' => $end->getTimezone()->getName(),
            ]),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid('consultation_', true),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ]);

        $createdEvent = $calendarService->events->insert($calendarId, $event, [
            'conferenceDataVersion' => 1,
        ]);

        $meetLink = $createdEvent->getHangoutLink();

        if (!$meetLink) {
            throw new RuntimeException('Google Calendar n a pas retourne de lien Meet.');
        }

        return $meetLink;
    }

    public function getAuthorizationUrl(): string
    {
        $client = $this->createBaseClient();

        return $client->createAuthUrl();
    }

    public function saveTokenFromCode(string $code): void
    {
        $client = $this->createBaseClient();
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($accessToken['error'])) {
            throw new RuntimeException($accessToken['error_description'] ?? $accessToken['error']);
        }

        $tokenPath = $this->resolvePath($this->getTokenPath());
        $tokenDir = dirname($tokenPath);

        if (!is_dir($tokenDir) && !mkdir($tokenDir, 0775, true) && !is_dir($tokenDir)) {
            throw new RuntimeException('Impossible de creer le dossier du token Google.');
        }

        file_put_contents($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT));
    }

    private function createClient(): Client
    {
        $client = $this->createBaseClient();

        if (!$this->usesOAuthClient()) {
            return $client;
        }

        $tokenPath = $this->resolvePath($this->getTokenPath());

        if (!is_file($tokenPath)) {
            throw new RuntimeException('Connexion Google requise. Ouvrez /google/connect pour autoriser Calendar.');
        }

        $client->setAccessToken(json_decode((string) file_get_contents($tokenPath), true));

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();

            if (!$refreshToken) {
                throw new RuntimeException('Token Google expire. Ouvrez /google/connect pour autoriser a nouveau.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken['refresh_token'] = $refreshToken;
            file_put_contents($tokenPath, json_encode($newToken, JSON_PRETTY_PRINT));
            $client->setAccessToken($newToken);
        }

        return $client;
    }

    private function createBaseClient(): Client
    {
        $credentialsPath = $_ENV['GOOGLE_CREDENTIALS_PATH'] ?? $_SERVER['GOOGLE_CREDENTIALS_PATH'] ?? null;

        if (!$credentialsPath) {
            throw new RuntimeException('GOOGLE_CREDENTIALS_PATH est manquant dans le fichier .env.');
        }

        $credentialsPath = $this->resolvePath($credentialsPath);

        if (!is_file($credentialsPath)) {
            throw new RuntimeException('Le fichier credentials Google est introuvable.');
        }

        $client = new Client();
        $client->setApplicationName('EchoCare Consultation');
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([Calendar::CALENDAR]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setRedirectUri($this->getRedirectUri());

        return $client;
    }

    private function usesOAuthClient(): bool
    {
        $credentialsPath = $_ENV['GOOGLE_CREDENTIALS_PATH'] ?? $_SERVER['GOOGLE_CREDENTIALS_PATH'] ?? null;

        if (!$credentialsPath) {
            return false;
        }

        $credentialsPath = $this->resolvePath($credentialsPath);

        if (!is_file($credentialsPath)) {
            return false;
        }

        $credentials = json_decode((string) file_get_contents($credentialsPath), true);

        return isset($credentials['installed']) || isset($credentials['web']);
    }

    private function getTokenPath(): string
    {
        return $_ENV['GOOGLE_TOKEN_PATH'] ?? $_SERVER['GOOGLE_TOKEN_PATH'] ?? 'config/google/token.json';
    }

    private function getRedirectUri(): string
    {
        return $_ENV['GOOGLE_REDIRECT_URI'] ?? $_SERVER['GOOGLE_REDIRECT_URI'] ?? 'http://localhost:8000/google/callback';
    }

    private function resolvePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return $this->projectDir . DIRECTORY_SEPARATOR . $path;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function buildDescription(ConsultationEnLigne $consultation): string
    {
        $patient = $consultation->getUser()?->getNomComplet() ?? 'Patient';
        $psychologue = $consultation->getPsychologue()?->getNomComplet() ?? 'Psychologue';
        $motif = $consultation->getMotif() ?: 'Aucun motif indique';

        return sprintf(
            "Patient: %s\nPsychologue: %s\nMotif: %s",
            $patient,
            $psychologue,
            $motif
        );
    }
}
