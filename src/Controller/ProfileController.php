<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
class ProfileController extends AbstractController
{
    #[Route('/patient/profile', name: 'patient_profile')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $this->getUser();
        $success = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setNumTel($request->request->get('num_tel'));

            $pw = $request->request->get('password');
            $confirm = $request->request->get('confirm_password');

            if ($pw) {
                if ($pw !== $confirm) {
                    $error = 'Les mots de passe ne correspondent pas !';
                } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/', $pw)) {
                    $error = 'Mot de passe trop faible !';
                } else {
                    $user->setPassword($hasher->hashPassword($user, $pw));
                }
            }

            if (!$error) {
                $em->flush();
                $success = 'Profil mis à jour avec succès !';
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'success' => $success,
            'error' => $error,
        ]);
    }
}