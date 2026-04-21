<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(PostRepository $postRepo, UserRepository $userRepo, CommentRepository $commentRepo): Response
    {
        $stats = [
            'total_posts' => $postRepo->count([]),
            'total_users' => $userRepo->count([]),
            'total_comments' => $commentRepo->count([]),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepo): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    #[Route('/moderation', name: 'app_admin_moderation')]
    public function moderation(PostRepository $postRepo, CommentRepository $commentRepo): Response
    {
        return $this->render('admin/moderation.html.twig', [
            'posts' => $postRepo->findAll(),
            'comments' => $commentRepo->findAll(),
        ]);
    }
}
