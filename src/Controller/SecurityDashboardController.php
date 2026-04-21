<?php

namespace App\Controller;

use App\Repository\LoginAttemptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SecurityDashboardController extends AbstractController
{
    #[Route('/admin/security', name: 'admin_security')]
    public function index(LoginAttemptRepository $repo): Response
    {
        return $this->render('admin/security.html.twig', [
            'blocked'    => $repo->getRecentBlocked(),
            'suspicious' => $repo->getAllSuspicious(),
        ]);
    }

    #[Route('/admin/security/unblock/{id}', name: 'admin_unblock', methods: ['POST'])]
    public function unblock(int $id, LoginAttemptRepository $repo, \Doctrine\ORM\EntityManagerInterface $em): Response
    {
        $attempt = $repo->find($id);
        if ($attempt) {
            $attempt->setBlockedUntil(null);
            $attempt->setAttempts(0);
            $em->flush();
        }
        return $this->redirectToRoute('admin_security');
    }
}