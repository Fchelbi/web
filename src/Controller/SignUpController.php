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

class SignUpController extends AbstractController
{
    #[Route('/signup', name: 'app_signup')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        MailService $mailService
    ): Response {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setEmail($request->request->get('email'));
            $user->setRole($request->request->get('role'));
            $user->setNumTel($request->request->get('num_tel'));

            $hashed = $hasher->hashPassword($user, $request->request->get('password'));
            $user->setPassword($hashed);

            // Token de vérification
            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);
            $user->setIsVerified(false);

            $em->persist($user);
            $em->flush();

            // Envoi du mail
            $mailService->sendVerificationEmail($user, $token);

            return $this->render('sign_up/verification_sent.html.twig', [
                'email' => $user->getEmail()
            ]);
        }

        return $this->render('sign_up/index.html.twig');
    }
    #[Route('/debug-login', name: 'debug_login')]
public function debugLogin(
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher
): Response {
    $user = $em->getRepository(User::class)->findOneBy(['email' => 'emnaboughoufa123@gmail.com']);
    
    if (!$user) {
        return new \Symfony\Component\HttpFoundation\Response('❌ User not found!');
    }

    $pwCheck = $hasher->isPasswordValid($user, 'Admin123!');
    
    return new \Symfony\Component\HttpFoundation\Response(
        'Email: ' . $user->getEmail() . '<br>' .
        'Role: ' . $user->getRole() . '<br>' .
        'isVerified: ' . ($user->isVerified() ? 'true' : 'false') . '<br>' .
        'Password hash: ' . $user->getPassword() . '<br>' .
        'Password valid (Admin123!): ' . ($pwCheck ? '✅ YES' : '❌ NO')
    );
}
    #[Route('/verify/{token}', name: 'app_verify')]

public function verify(string $token, EntityManagerInterface $em): Response
{
    $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

    if (!$user) {
        return $this->render('sign_up/verification_invalid.html.twig');
    }

    $user->setIsVerified(true);
    $user->setVerificationToken(null);
    $em->flush();

    return $this->render('sign_up/verification_success.html.twig', [
        'role' => $user->getRole()
    ]);
}
}