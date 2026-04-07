<?php

namespace App\Controller;

use App\Repository\BienEtreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
class PatientController extends AbstractController
{
    #[Route('/patient/dashboard', name: 'patient_dashboard')]
    public function index(BienEtreRepository $repo): Response
    {
        $user = $this->getUser();
        $last = $repo->findOneBy(['user' => $user], ['createdAt' => 'DESC']);

        $response = $this->render('patient/index.html.twig', [
            'last' => $last,
        ]);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}