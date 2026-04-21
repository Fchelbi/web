<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(PostRepository $postRepo, UserRepository $userRepo, CommentRepository $commentRepo): Response
    {
        $stats = [
            'total_posts'    => $postRepo->count([]),
            'total_users'    => $userRepo->count([]),
            'total_comments' => $commentRepo->count([]),
        ];

        return $this->render('admin/dashboard.html.twig', ['stats' => $stats]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(Request $request, UserRepository $userRepo): Response
    {
        $search = $request->query->get('search', '');
        $role   = $request->query->get('role', '');
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = 10;

        [$users, $total] = $userRepo->searchPaginated($search, $role, $page, $limit);

        $response = $this->render('admin/users.html.twig', [
            'users'         => $users,
            'search'        => $search,
            'role'          => $role,
            'page'          => $page,
            'totalPages'    => (int) ceil($total / $limit),
            'total'         => $total,
            'totalAdmins'   => $userRepo->count(['role' => 'Admin']),
            'totalCoachs'   => $userRepo->count(['role' => 'Coach']),
            'totalPatients' => $userRepo->count(['role' => 'Patient']),
        ]);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/moderation', name: 'app_admin_moderation')]
    public function moderation(PostRepository $postRepo, CommentRepository $commentRepo): Response
    {
        return $this->render('admin/moderation.html.twig', [
            'posts'    => $postRepo->findAll(),
            'comments' => $commentRepo->findAll(),
        ]);
    }
}
