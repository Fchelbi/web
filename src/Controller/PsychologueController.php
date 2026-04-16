<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PsychologueController extends AbstractController
{
    #[Route('/psychologues', name: 'psychologue_list', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $psychologues = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', User::ROLE_COACH)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('psychologue/index.html.twig', [
            'psychologues' => $psychologues,
        ]);
    }
}
