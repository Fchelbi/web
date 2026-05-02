<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        MailService $mailService
    ): Response {
        $success = false;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $em->flush();
                $mailService->sendResetPasswordEmail($user, $token);
            }

            $success = true;
        }

        return $this->render('reset_password/forgot.html.twig', [
            'success' => $success
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->render('reset_password/invalid.html.twig');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $pw = $request->request->get('password');
            $confirm = $request->request->get('confirm_password');

            if ($pw !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas';
            } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/', $pw)) {
                $error = 'Mot de passe trop faible';
            } else {
                $user->setPassword($hasher->hashPassword($user, $pw));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $em->flush();
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('reset_password/reset.html.twig', [
            'token' => $token,
            'error' => $error
        ]);
    }
}