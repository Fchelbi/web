<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaceIdController extends AbstractController
{
    #[Route('/admin/face-register', name: 'face_register')]
    public function register(): Response
    {
        return $this->render('face_id/register.html.twig');
    }

    #[Route('/admin/face-register/save', name: 'face_register_save', methods: ['POST'])]
    public function saveDescriptor(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['descriptor'])) {
            return new JsonResponse(['success' => false, 'message' => 'Pas de données'], 400);
        }

        $user->setFaceDescriptor(json_encode($data['descriptor']));
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
    #[Route('/clear-face-prompt', name: 'clear_face_prompt')]
    public function clearPrompt(Request $request): JsonResponse
    {
        $request->getSession()->remove('show_face_id_prompt');
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/login/face', name: 'face_login')]
    public function faceLogin(): Response
    {
        return $this->render('face_id/login.html.twig');
    }

    #[Route('/login/face/verify', name: 'face_login_verify', methods: ['POST'])]
    public function verifyFace(
        Request $request,
        UserRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['descriptor'])) {
            return new JsonResponse(['success' => false], 400);
        }

        $inputDescriptor = $data['descriptor'];

        // Cherche tous les admins avec face descriptor
        $admins = $repo->createQueryBuilder('u')
            ->where('u.role = :role')
            ->andWhere('u.faceDescriptor IS NOT NULL')
            ->setParameter('role', 'Admin')
            ->getQuery()
            ->getResult();

        foreach ($admins as $admin) {
            $savedDescriptor = json_decode($admin->getFaceDescriptor(), true);

            // Calcule la distance euclidienne
            $distance = $this->euclideanDistance($inputDescriptor, $savedDescriptor);

            if ($distance < 0.5) {
                // Match trouvé — créer la session
                $request->getSession()->set('face_authenticated_user_id', $admin->getId());
                return new JsonResponse([
                    'success' => true,
                    'redirect' => '/admin/dashboard'
                ]);
            }
        }

        return new JsonResponse(['success' => false, 'message' => 'Visage non reconnu']);
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += ($a[$i] - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }
}
