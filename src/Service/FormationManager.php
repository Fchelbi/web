<?php
namespace App\Service;

use App\Entity\Formation;

/**
 * FormationManager — service métier pour valider les règles de l'entité Formation.
 * Règles extraites directement des annotations Assert de l'entité Formation.
 */
class FormationManager
{
    /**
     * Valide les règles métier d'une formation.
     *
     * Règle 1 : Le titre est obligatoire (NotBlank)
     * Règle 2 : Le titre doit avoir entre 3 et 255 caractères (Length)
     * Règle 3 : La catégorie doit être parmi les valeurs autorisées (Choice)
     * Règle 4 : La description ne peut pas dépasser 2000 caractères (Length)
     */
    public function validate(Formation $formation): bool
    {
        // Règle 1 : titre obligatoire
        if (empty(trim($formation->getTitle()))) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }

        // Règle 2a : titre minimum 3 caractères
        if (strlen(trim($formation->getTitle())) < 3) {
            throw new \InvalidArgumentException('Le titre doit avoir au moins 3 caractères.');
        }

        // Règle 2b : titre maximum 255 caractères
        if (strlen($formation->getTitle()) > 255) {
            throw new \InvalidArgumentException('Le titre ne peut pas dépasser 255 caractères.');
        }

        // Règle 3 : catégorie valide (même liste que Assert\Choice dans l'entité)
        $allowedCategories = [
            'Nutrition',
            'Sport & Fitness',
            'Santé Mentale',
            'Méditation',
            'Gestion du Stress',
            'Autre',
        ];

        if ($formation->getCategory() !== null &&
            !in_array($formation->getCategory(), $allowedCategories, true)) {
            throw new \InvalidArgumentException('Catégorie invalide.');
        }

        // Règle 4 : description max 2000 caractères
        if ($formation->getDescription() !== null &&
            strlen($formation->getDescription()) > 2000) {
            throw new \InvalidArgumentException('La description ne peut pas dépasser 2000 caractères.');
        }

        return true;
    }

    /**
     * Calcule le score de complétude d'une formation (0 à 100%).
     */
    public function calculateCompletionScore(Formation $formation): int
    {
        $score = 0;
        if (!empty(trim($formation->getTitle())))  $score += 25;
        if (!empty($formation->getDescription()))  $score += 25;
        if ($formation->getCategory() !== null)    $score += 25;
        if ($formation->getVideoUrl() !== null)    $score += 25;
        return $score;
    }
}