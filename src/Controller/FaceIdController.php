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
    
    #[Route('/admin/face-prompt', name: 'face_id_prompt')]
    public function prompt(): Response
    {
        return $this->render('face_id/prompt.html.twig');
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
    
    #[Route('/admin/face-check', name: 'face_check')]
    public function faceCheck(): JsonResponse
    {
        $user = $this->getUser();
        return new JsonResponse([
            'hasDescriptor' => $user && $user->getFaceDescriptor() !== null
        ]);
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

        $admins = $repo->createQueryBuilder('u')
            ->where('u.role = :role')
            ->andWhere('u.faceDescriptor IS NOT NULL')
            ->setParameter('role', 'Admin')
            ->getQuery()
            ->getResult();

        foreach ($admins as $admin) {
            $savedDescriptor = json_decode($admin->getFaceDescriptor(), true);
            $distance = $this->euclideanDistance($inputDescriptor, $savedDescriptor);

            if ($distance < 0.5) {
                // ✅ Crée la session Symfony correctement
                $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                    $admin,
                    'main',
                    $admin->getRoles()
                );
                $this->container->get('security.token_storage')->setToken($token);
                $request->getSession()->set('_security_main', serialize($token));
                $request->getSession()->save();

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
