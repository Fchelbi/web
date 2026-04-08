<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(Request $request, UserRepository $repo): Response
    {
        // ← Récupère les paramètres GET (recherche + filtre rôle + page)
        $search = $request->query->get('search', '');
        $role   = $request->query->get('role', '');
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = 10; // Nombre d'utilisateurs par page

        // ← Requête dynamique via le repository
        [$users, $total] = $repo->searchPaginated($search, $role, $page, $limit);

        $totalPages = (int) ceil($total / $limit);

        $response = $this->render('admin/index.html.twig', [
            'users'      => $users,
            'search'     => $search,
            'role'       => $role,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
            'totalAdmins'   => $repo->count(['role' => 'Admin']),
            'totalCoachs'   => $repo->count(['role' => 'Coach']),
            'totalPatients' => $repo->count(['role' => 'Patient']),
        ]);

        // ← No-cache headers (feature 2)
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Vary', 'Cookie');

        return $response;
    }
}