<?php

namespace App\Security;

use App\Entity\User;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private EntityManagerInterface $em,
        private MailService $mailService
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        // Vérifie si l'appareil est déjà enregistré
        $deviceToken = $request->cookies->get('device_token_' . $user->getId());
        
        if ($deviceToken && $deviceToken === $user->getDeviceToken()) {
            // Appareil reconnu → accès direct
            return $this->redirectByRole($user);
        }

        // Génère le code 2FA
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setTwoFactorCode($code);
        $user->setTwoFactorExpiresAt(new \DateTimeImmutable('+10 minutes'));
        $this->em->flush();

        $this->mailService->sendTwoFactorCode($user, $code);

        $request->getSession()->set('2fa_user_id', $user->getId());

        return new RedirectResponse($this->router->generate('app_2fa'));
    }

    public function redirectByRole(User $user): RedirectResponse
    {
        $role = $user->getRole();
        if ($role === 'Admin') {
            return new RedirectResponse($this->router->generate('admin_dashboard'));
        } elseif ($role === 'Coach') {
            return new RedirectResponse($this->router->generate('coach_dashboard'));
        } else {
            return new RedirectResponse($this->router->generate('patient_dashboard'));
        }
    }
}