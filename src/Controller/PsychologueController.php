<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PsychologueController extends AbstractController
{
    #[Route('/psychologues', name: 'psychologue_list', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response
    {
        $psychologues = $paginator->paginate(
            $userRepository->createCoachesQueryBuilder(),
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('psychologue/index.html.twig', [
            'psychologues' => $psychologues,
        ]);
    }
}
