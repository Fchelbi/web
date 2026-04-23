<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BadWordsService
{
    private array $badWords = [
        // Français
        'merde', 'putain', 'connard', 'connasse', 'salope', 'pute',
        'enculé', 'encule', 'batard', 'bâtard', 'fdp', 'ntm', 'tg',
        'pd', 'couille', 'bite', 'chier', 'chiasse', 'bordel',
        'nique', 'niquer', 'ta gueule', 'fils de pute', 'gros con',
        'va te faire', 'ferme ta gueule', 'pauvre con', 'espece de con',
        // Anglais
        'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'cunt',
        'dick', 'cock', 'pussy', 'whore', 'slut', 'motherfucker',
        'fucker', 'bullshit', 'ass', 'idiot', 'stupid', 'moron',
        'loser', 'retard', 'wtf', 'stfu', 'gtfo', 'dumbass',
        'jackass', 'dipshit', 'shithead', 'screw you', 'go to hell',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private MailService $mailService
    ) {}

    public function containsBadWords(string $text): bool
    {
        return count($this->getBadWordsFound($text)) > 0;
    }

    public function getBadWordsFound(string $text): array
    {
        // Normalise le texte
        $normalized = strtolower($text);
        $normalized = $this->normalizeAccents($normalized);

        $found = [];
        foreach ($this->badWords as $word) {
            $wordNorm = strtolower($this->normalizeAccents($word));

            // Cherche le mot dans le texte (avec ou sans espaces autour)
            if (preg_match('/(?<![a-z0-9])' . preg_quote($wordNorm, '/') . '(?![a-z0-9])/i', $normalized)) {
                $found[] = $word;
            }
        }
        return $found;
    }

    private function normalizeAccents(string $text): string
    {
        $accents = [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        return str_replace(array_keys($accents), array_values($accents), $text);
    }

    public function handleBadWord(User $user): array
    {
        $user->incrementBadWordsCount();
        $count = $user->getBadWordsCount();

        if ($count >= 3) {
            $user->setBanned(true);
            $user->setBannedAt(new \DateTime());
            $this->em->flush();

            try {
                $this->mailService->sendBanAlert($user);
            } catch (\Exception $e) {}

            return [
                'action'  => 'banned',
                'message' => 'Votre compte a ete banni apres 3 avertissements pour langage inapproprie.',
                'count'   => $count,
            ];
        }

        $this->em->flush();
        $remaining = 3 - $count;

        return [
            'action'  => 'warning',
            'message' => "Avertissement {$count}/3 — Langage inapproprie detecte ! Encore {$remaining} avertissement(s) avant bannissement.",
            'count'   => $count,
        ];
    }
}