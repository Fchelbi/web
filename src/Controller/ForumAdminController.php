<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\GeminiModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/forum')]
#[IsGranted('ROLE_ADMIN')]
class ForumAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_forum')]
    public function index(
        PostRepository $postRepo,
        UserRepository $userRepo,
        CommentRepository $commentRepo
    ): Response {
        $stats = [
            'total_posts'    => $postRepo->count([]),
            'active_users'   => $userRepo->countActiveForumUsers(),
            'flagged_posts'  => $postRepo->countFlagged(),
            'banned_users'   => $userRepo->countBanned(),
        ];

        $postsPerDay    = $postRepo->getPostsPerDay(30);
        $postsByCategory = $postRepo->getPostsByCategory();
        $flaggedPosts   = $postRepo->findFlaggedPosts();
        $allPosts       = $postRepo->findAllForAdmin();
        $allComments    = $commentRepo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/forum.html.twig', [
            'stats'            => $stats,
            'postsPerDay'      => $postsPerDay,
            'postsByCategory'  => $postsByCategory,
            'flaggedPosts'     => $flaggedPosts,
            'allPosts'         => $allPosts,
            'allComments'      => $allComments,
        ]);
    }

    #[Route('/post/{id}/toggle-flag', name: 'app_admin_forum_toggle_flag', methods: ['POST'])]
    public function toggleFlag(Post $post, EntityManagerInterface $em): Response
    {
        if ($post->isFlagged()) {
            $post->setIsFlagged(false);
            $post->setFlagReason(null);
            $post->setModerationStatus('approved');
            $this->addFlash('success', 'Post unflagged successfully.');
        } else {
            $post->setIsFlagged(true);
            $post->setFlagReason('Manually flagged by admin');
            $post->setModerationStatus('pending');
            $this->addFlash('success', 'Post flagged for review.');
        }

        $em->flush();
        return $this->redirectToRoute('app_admin_forum');
    }

    #[Route('/post/{id}/approve', name: 'app_admin_forum_approve', methods: ['POST'])]
    public function approve(Post $post, EntityManagerInterface $em): Response
    {
        $post->setIsFlagged(false);
        $post->setModerationStatus('approved');
        // Keep flagReason for audit trail
        $em->flush();

        $this->addFlash('success', 'Post approved and restored.');
        return $this->redirectToRoute('app_admin_forum');
    }

    #[Route('/post/{id}/reject', name: 'app_admin_forum_reject', methods: ['POST'])]
    public function reject(Post $post, EntityManagerInterface $em): Response
    {
        $post->setModerationStatus('rejected');
        $em->flush();

        $this->addFlash('success', 'Post has been rejected/hidden.');
        return $this->redirectToRoute('app_admin_forum');
    }

    #[Route('/post/{id}/delete', name: 'app_admin_forum_delete_post', methods: ['POST'])]
    public function deletePost(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('forum_delete' . $post->getId(), $request->get('_token'))) {
            // Remove comments first
            foreach ($post->getComments() as $comment) {
                $em->remove($comment);
            }
            $em->remove($post);

            try {
                $em->flush();
                $this->addFlash('success', 'Post deleted permanently.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting post: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_admin_forum');
    }

    #[Route('/user/{id}/toggle-ban', name: 'app_admin_forum_toggle_ban', methods: ['POST'])]
    public function toggleBan(int $id, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_forum');
        }

        if ($user->isBanned()) {
            $user->setIsBanned(false);
            $this->addFlash('success', $user->getPrenom() . ' ' . $user->getNom() . ' has been unbanned.');
        } else {
            $user->setIsBanned(true);
            $this->addFlash('success', $user->getPrenom() . ' ' . $user->getNom() . ' has been banned.');
        }

        $em->flush();
        return $this->redirectToRoute('app_admin_forum');
    }

    #[Route('/moderate-all', name: 'app_admin_forum_moderate_all', methods: ['POST'])]
    public function moderateAll(
        PostRepository $postRepo,
        GeminiModerationService $moderationService
    ): Response {
        $posts = $postRepo->findAll();
        $summary = $moderationService->moderateAllPosts($posts);

        $this->addFlash('success', sprintf(
            'AI Scan complete: %d posts scanned, %d flagged for review.',
            $summary['scanned'],
            $summary['flagged']
        ));

        return $this->redirectToRoute('app_admin_forum');
    }

    #[Route('/post/{id}/moderate', name: 'app_admin_forum_moderate_single', methods: ['POST'])]
    public function moderateSingle(
        Post $post,
        GeminiModerationService $moderationService
    ): Response {
        $result = $moderationService->moderatePost($post);

        if ($result['flagged']) {
            $this->addFlash('warning', sprintf(
                'Post "%s" flagged by AI: %s (confidence: %.0f%%)',
                $post->getTitle(),
                $result['reason'],
                $result['confidence'] * 100
            ));
        } else {
            $this->addFlash('success', sprintf(
                'Post "%s" passed AI moderation — content is safe.',
                $post->getTitle()
            ));
        }

        return $this->redirectToRoute('app_admin_forum');
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_forum_delete_comment', methods: ['POST'])]
    public function deleteComment(Request $request, int $id, CommentRepository $commentRepo, EntityManagerInterface $em): Response
    {
        $comment = $commentRepo->find($id);
        if ($comment && $this->isCsrfTokenValid('forum_delete_comment' . $comment->getId(), $request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted successfully.');
        } else {
            $this->addFlash('error', 'Error deleting comment.');
        }

        return $this->redirectToRoute('app_admin_forum');
    }
}
