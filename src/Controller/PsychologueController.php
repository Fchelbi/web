<?php

namespace App\Controller;

use App\Repository\PsychologueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PsychologueController extends AbstractController
{
    #[Route('/psychologues', name: 'psychologue_list', methods: ['GET'])]
    public function index(PsychologueRepository $psychologueRepository): Response
    {
        return $this->render('psychologue/index.html.twig', [
            'psychologues' => $psychologueRepository->findAllOrderedByName(),
        ]);
    }
}
