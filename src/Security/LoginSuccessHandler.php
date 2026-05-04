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
            return $this->redirectByRole($user, $request);
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

    public function redirectByRole(User $user, Request $request = null): RedirectResponse
    {
        $role = $user->getRole();

        if ($role === 'Admin') {
            // Vérifie si l'admin a déjà un Face ID
            if ($request && !$user->getFaceDescriptor()) {
                $request->getSession()->set('show_face_id_prompt', true);
            }
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        } elseif ($role === 'Coach') {
            return new RedirectResponse($this->router->generate('coach_dashboard'));
        } else {
            return new RedirectResponse($this->router->generate('patient_dashboard'));
        }
    }
}