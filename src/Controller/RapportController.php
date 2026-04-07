<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
class RapportController extends AbstractController
{
    #[Route('/patient/rapports', name: 'patient_rapports')]
    public function index(): Response
    {
        $user = $this->getUser();

        // Données fictives pour la démo
        $progression = [
            ['date' => '01/03/2026', 'sommeil' => 60, 'stress' => 40, 'humeur' => 70],
            ['date' => '05/03/2026', 'sommeil' => 65, 'stress' => 35, 'humeur' => 75],
            ['date' => '10/03/2026', 'sommeil' => 70, 'stress' => 30, 'humeur' => 80],
            ['date' => '15/03/2026', 'sommeil' => 75, 'stress' => 25, 'humeur' => 85],
            ['date' => '17/03/2026', 'sommeil' => 80, 'stress' => 20, 'humeur' => 90],
        ];

        return $this->render('rapport/index.html.twig', [
            'user' => $user,
            'progression' => $progression,
        ]);
    }
}