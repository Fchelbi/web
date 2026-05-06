<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\BienEtre;
use App\Entity\Comment;
use App\Entity\LoginAttempt;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        // Remove BienEtre records
        foreach ($em->getRepository(BienEtre::class)->findBy(['user' => $user]) as $b) {
            $em->remove($b);
        }

        // Remove login attempts
        foreach ($em->getRepository(LoginAttempt::class)->findBy(['email' => $user->getEmail()]) as $a) {
            $em->remove($a);
        }

        // Remove posts (and their comments via orphanRemoval)
        $posts = $em->getRepository(Post::class)->findBy(['user' => $user]);
        foreach ($posts as $post) {
            foreach ($post->getComments() as $comment) {
                $em->remove($comment);
            }
            $em->remove($post);
        }

        // Remove comments authored by the user on other posts
        $comments = $em->getRepository(Comment::class)->findBy(['user' => $user]);
        foreach ($comments as $comment) {
            $em->remove($comment);
        }

        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/export-pdf', name: 'admin_export_pdf')]
    public function exportPdf(
        UserRepository $userRepo,
        PostRepository $postRepo,
        CommentRepository $commentRepo,
        EntityManagerInterface $em
    ): Response {
        $totalUsers    = $userRepo->count([]);
        $totalAdmins   = $userRepo->count(['role' => 'Admin']);
        $totalCoachs   = $userRepo->count(['role' => 'Coach']);
        $totalPatients = $userRepo->count(['role' => 'Patient']);
        $totalPosts    = $postRepo->count([]);
        $flaggedPosts  = $postRepo->countFlagged();
        $totalComments = $commentRepo->count([]);
        $bannedUsers   = $userRepo->countBanned();
        $activeForumUsers = $userRepo->countActiveForumUsers();
        $postsByCategory  = $postRepo->getPostsByCategory();
        $recentUsers      = $userRepo->findBy([], ['id' => 'DESC'], 10);
        $allUsers         = $userRepo->findBy([], ['nom' => 'ASC']);

        $avgBienEtre = $em->createQuery("
            SELECT AVG(b.sommeil) as avgSommeil,
                   AVG(b.stress) as avgStress,
                   AVG(b.humeur) as avgHumeur
            FROM App\Entity\BienEtre b
        ")->getSingleResult();

        $html = $this->renderView('admin/pdf_stats.html.twig', [
            'date'             => new \DateTime(),
            'totalUsers'       => $totalUsers,
            'totalAdmins'      => $totalAdmins,
            'totalCoachs'      => $totalCoachs,
            'totalPatients'    => $totalPatients,
            'totalPosts'       => $totalPosts,
            'flaggedPosts'     => $flaggedPosts,
            'totalComments'    => $totalComments,
            'bannedUsers'      => $bannedUsers,
            'activeForumUsers' => $activeForumUsers,
            'postsByCategory'  => $postsByCategory,
            'avgBienEtre'      => $avgBienEtre,
            'recentUsers'      => $recentUsers,
            'allUsers'         => $allUsers,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'echocare-stats-' . (new \DateTime())->format('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/analytics', name: 'admin_analytics')]
    public function analytics(
        UserRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $totalAdmins   = $repo->count(['role' => 'Admin']);
        $totalCoachs   = $repo->count(['role' => 'Coach']);
        $totalPatients = $repo->count(['role' => 'Patient']);
        $total         = $repo->count([]);

        $avgBienEtre = $em->createQuery("
            SELECT AVG(b.sommeil) as avgSommeil,
                   AVG(b.stress) as avgStress,
                   AVG(b.humeur) as avgHumeur
            FROM App\Entity\BienEtre b
        ")->getSingleResult();

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