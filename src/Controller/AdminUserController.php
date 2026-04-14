<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminUserController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
public function index(Request $request, UserRepository $repo): Response
{
    $search = $request->query->get('search', '');
    $role   = $request->query->get('role', '');
    $page   = max(1, (int) $request->query->get('page', 1));
    $limit  = 4;

    [$users, $total] = $repo->searchPaginated($search, $role, $page, $limit);

    $totalPages = (int) ceil($total / $limit);

    return $this->render('admin/index.html.twig', [
        'users'         => $users,
        'search'        => $search,
        'role'          => $role,
        'page'          => $page,
        'totalPages'    => $totalPages,
        'total'         => $total,
        'totalAdmins'   => $repo->count(['role' => 'Admin']),
        'totalCoachs'   => $repo->count(['role' => 'Coach']),
        'totalPatients' => $repo->count(['role' => 'Patient']),
    ]);
}

    #[Route('/user/new', name: 'admin_user_new')]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setEmail($request->request->get('email'));
            $user->setRole($request->request->get('role'));
            $user->setNumTel($request->request->get('num_tel'));
            $hashed = $hasher->hashPassword($user, $request->request->get('password'));
            $user->setPassword($hashed);
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/new.html.twig');
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setEmail($request->request->get('email'));
            $user->setRole($request->request->get('role'));
            $user->setNumTel($request->request->get('num_tel'));
            $pw = $request->request->get('password');
            if ($pw) {
                $hashed = $hasher->hashPassword($user, $pw);
                $user->setPassword($hashed);
            }
            $em->flush();
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/edit.html.twig', ['user' => $user]);
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_dashboard');
    }
    #[Route('/export-pdf', name: 'admin_export_pdf')]
    public function exportPdf(UserRepository $repo): Response
    {
        $users = $repo->findBy([], ['nom' => 'ASC']);

        return $this->render('admin/pdf_export.html.twig', [
            'users' => $users,
            'date'  => new \DateTime(),
        ]);
    }
    #[Route('/analytics', name: 'admin_analytics')]
    public function analytics(
        UserRepository $repo,
        EntityManagerInterface $em
    ): Response {
        // Répartition par rôle
        $totalAdmins   = $repo->count(['role' => 'Admin']);
        $totalCoachs   = $repo->count(['role' => 'Coach']);
        $totalPatients = $repo->count(['role' => 'Patient']);
        $total         = $repo->count([]);

        // Score bien-être moyen
        $avgBienEtre = $em->createQuery("
            SELECT AVG(b.sommeil) as avgSommeil,
                AVG(b.stress) as avgStress,
                AVG(b.humeur) as avgHumeur
            FROM App\Entity\BienEtre b
        ")->getSingleResult();

        // Dernières inscriptions
        $lastUsers = $repo->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/analytics.html.twig', [
            'totalAdmins'   => $totalAdmins,
            'totalCoachs'   => $totalCoachs,
            'totalPatients' => $totalPatients,
            'total'         => $total,
            'avgBienEtre'   => $avgBienEtre,
            'lastUsers'     => $lastUsers,
        ]);
    }
}