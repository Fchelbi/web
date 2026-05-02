<?php

namespace App\Controller;

use App\Service\BruteForceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private BruteForceService $bruteForce,
    ) {}

    #[Route(path: '/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $ip           = $request->getClientIp();
        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Enregistre tentative echouee
        if ($error && $lastUsername) {
            $this->bruteForce->recordFailedAttempt($ip, $lastUsername);
        }

        // Verifie si l'email est bloque
        $blocked   = $lastUsername ? $this->bruteForce->isBlocked($lastUsername) : false;
        $remaining = $blocked ? $this->bruteForce->getRemainingTime($lastUsername) : 0;
        $attempts  = $lastUsername ? $this->bruteForce->getAttempts($lastUsername) : 0;

        $response = new Response();
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
            'blocked'       => $blocked,
            'remaining'     => $remaining,
            'attempts'      => $attempts,
        ], $response);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank.');
    }

    #[Route('/check-auth', name: 'check_auth')]
    public function checkAuth(): JsonResponse
    {
        return new JsonResponse(['authenticated' => $this->getUser() !== null]);
    }

    #[Route('/check-blocked', name: 'check_blocked')]
    public function checkBlocked(Request $request): JsonResponse
    {
        $email     = $request->query->get('email', '');
        $blocked   = $email ? $this->bruteForce->isBlocked($email) : false;
        $remaining = $blocked ? $this->bruteForce->getRemainingTime($email) : 0;

        return new JsonResponse([
            'blocked'   => $blocked,
            'remaining' => $remaining,
        ]);
    }
}