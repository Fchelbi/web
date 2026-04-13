<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa', name: 'app_2fa')]
    public function twoFactor(
        Request $request,
        EntityManagerInterface $em,
        MailService $mailService
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('2fa_user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        // Envoie le code si pas encore envoyé ou expiré
        if (!$user->getTwoFactorCode() || $user->getTwoFactorExpiresAt() < new \DateTimeImmutable()) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setTwoFactorCode($code);
            $user->setTwoFactorExpiresAt(new \DateTimeImmutable('+10 minutes'));
            $em->flush();
            $mailService->sendTwoFactorCode($user, $code);
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            $saveDevice = $request->request->get('save_device');

            if ($code === $user->getTwoFactorCode() &&
                $user->getTwoFactorExpiresAt() > new \DateTimeImmutable()) {

                $user->setTwoFactorCode(null);
                $user->setTwoFactorExpiresAt(null);
                $em->flush();
                $session->remove('2fa_user_id');

                // Redirect selon rôle
                $role = $user->getRole();
                if ($role === 'Admin') {
                    if (!$user->getFaceDescriptor()) {
                        $route = 'face_id_prompt';
                    } else {
                        $route = 'admin_dashboard';
                    }
                } elseif ($role === 'Coach') {
                    $route = 'coach_dashboard';
                } else {
                    $route = 'patient_dashboard';
                }

                $response = $this->redirectToRoute($route);

                // Enregistre l'appareil si demandé
                if ($saveDevice) {
                    $deviceToken = bin2hex(random_bytes(32));
                    $user->setDeviceToken($deviceToken);
                    $em->flush();

                    $cookie = Cookie::create('device_token_' . $user->getId())
                        ->withValue($deviceToken)
                        ->withExpires(new \DateTimeImmutable('+30 days'))
                        ->withPath('/')
                        ->withHttpOnly(true);

                    $response->headers->setCookie($cookie);
                }

                return $response;

            } else {
                $error = 'Code incorrect ou expiré !';
            }
        }

        return $this->render('two_factor/index.html.twig', [
            'error' => $error,
            'email' => $user->getEmail()
        ]);
    }

    #[Route('/2fa/resend', name: 'app_2fa_resend')]
    public function resend(
        Request $request,
        EntityManagerInterface $em,
        MailService $mailService
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('2fa_user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(User::class)->find($userId);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setTwoFactorCode($code);
        $user->setTwoFactorExpiresAt(new \DateTimeImmutable('+10 minutes'));
        $em->flush();
        $mailService->sendTwoFactorCode($user, $code);

        return $this->redirectToRoute('app_2fa');
    }
}