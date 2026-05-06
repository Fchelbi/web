<?php

namespace App\Controller;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use App\Form\ConsultationType;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class ConsultationAiController extends AbstractController
{
    #[Route('/consultation/auto-assign', name: 'consultation_auto_assign', methods: ['POST'])]
    public function autoAssignCoach(
        Request $request,
        AiService $aiService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        /**
         * Endpoint that automatically suggests a coach and creates a consultation.
         * Useful for the patient to quickly book with the most available psychologist.
         */

        $motif = $request->request->get('motif');
        $dateConsultation = $request->request->get('dateConsultation');

        if (!$motif || !$dateConsultation) {
            return $this->json(['error' => 'Missing motif or dateConsultation'], 400);
        }

        try {
            $dateTime = new \DateTime($dateConsultation);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }

        // Get the most available coach
        $suggestedCoach = $aiService->suggestMostAvailablePsy();

        if ($suggestedCoach === null) {
            return $this->json(['error' => 'No coaches available at the moment'], 503);
        }

        // Create and save the consultation
        $consultation = new ConsultationEnLigne();
        $consultation->setMotif($motif);
        $consultation->setDateConsultation($dateTime);
        $consultation->setPsychologue($suggestedCoach);
        $consultation->setUser($this->getUser()); // Current logged-in user (patient)
        $consultation->setStatut(ConsultationEnLigne::STATUT_EN_ATTENTE);

        $entityManager->persist($consultation);
        $entityManager->flush();

        $logger->info('Auto-assigned consultation', [
            'consultation_id' => $consultation->getId(),
            'coach_id' => $suggestedCoach->getId(),
            'coach_name' => $suggestedCoach->getName(),
        ]);

        return $this->json([
            'success' => true,
            'consultation_id' => $consultation->getId(),
            'assigned_coach' => [
                'id' => $suggestedCoach->getId(),
                'name' => $suggestedCoach->getName(),
                'email' => $suggestedCoach->getEmail(),
            ],
        ], 201);
    }

    #[Route('/consultation/suggest-coach', name: 'consultation_suggest_coach', methods: ['GET'])]
    public function suggestCoach(
        AiService $aiService,
    ): Response {
        /**
         * Endpoint that suggests a coach WITHOUT creating a consultation.
         * Useful for the patient to see who is available before committing.
         */

        $suggestedCoach = $aiService->suggestMostAvailablePsy();

        if ($suggestedCoach === null) {
            return $this->json(['error' => 'No coaches available'], 503);
        }

        return $this->json([
            'suggested_coach' => [
                'id' => $suggestedCoach->getId(),
                'name' => $suggestedCoach->getName(),
                'email' => $suggestedCoach->getEmail(),
                'phone' => $suggestedCoach->getNumTel(),
            ],
        ]);
    }

    #[Route('/consultation/coaches-availability', name: 'coaches_availability', methods: ['GET'])]
    public function showCoachesAvailability(
        AiService $aiService,
    ): Response {
        /**
         * Show all coaches with their current consultation counts.
         * This is a helper endpoint for debugging or displaying availability to users.
         */

        $coachesData = $aiService->getCoachesWithConsultationCounts();

        return $this->json([
            'coaches' => $coachesData,
            'total' => count($coachesData),
        ]);
    }

    #[Route('/consultation/suggest-coach-from-date', name: 'consultation_suggest_from_date', methods: ['GET'])]
    public function suggestCoachFromDate(
        Request $request,
        AiService $aiService,
    ): Response {
        /**
         * Endpoint that suggests a coach, counting only upcoming consultations
         * from a specific date onwards.
         *
         * Query param: ?fromDate=2026-04-25
         */

        $fromDateStr = $request->query->get('fromDate');

        if (!$fromDateStr) {
            return $this->json(['error' => 'Missing fromDate query parameter'], 400);
        }

        try {
            $fromDate = new \DateTime($fromDateStr);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format (use YYYY-MM-DD)'], 400);
        }

        // Get the most available coach, filtering consultations from the given date
        $suggestedCoach = $aiService->suggestMostAvailablePsy($fromDate);

        if ($suggestedCoach === null) {
            return $this->json(['error' => 'No coaches available'], 503);
        }

        return $this->json([
            'suggested_coach' => [
                'id' => $suggestedCoach->getId(),
                'name' => $suggestedCoach->getName(),
                'email' => $suggestedCoach->getEmail(),
            ],
            'from_date' => $fromDate->format('Y-m-d'),
        ]);
    }
}
