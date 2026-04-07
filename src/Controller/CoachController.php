<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COACH')]
class CoachController extends AbstractController
{
    #[Route('/coach/dashboard', name: 'coach_dashboard')]
    public function index(): Response
    {
        return $this->render('coach/index.html.twig');
    }
}