<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;

#[Route('/post')]
class PostController extends AbstractController
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    #[Route('', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository, Request $request): Response
    {
        $query = $request->query->get('q');
        
        if ($query) {
            // Search posts by keyword
            $posts = $postRepository->searchByKeyword($query, 5, 0);
        } else {
            // Load initial 5 posts for lazy loading demo
            $posts = $postRepository->findBy([], ['createdAt' => 'DESC'], 5, 0);
        }
        
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'search_query' => $query,
        ]);
    }

    #[Route('/load-more/{offset}', name: 'app_post_load_more', methods: ['GET'])]
    public function loadMore(int $offset, PostRepository $postRepository): JsonResponse
    {
        $limit = 5;
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        
        // Render partial view for the fetched posts
        $html = '';
        foreach ($posts as $post) {
            $html .= $this->renderView('post/_post_card.html.twig', ['post' => $post]);
        }
        
        return new JsonResponse([
            'html' => $html,
            'count' => count($posts)
        ]);
    }

    #[Route('/{id}/like', name: 'app_post_like', methods: ['POST'])]
    public function like(Post $post, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 403);
        }

        // If user disliked before, remove that dislike
        if ($post->getDislikedByUsers()->contains($user)) {
            $post->removeDislikedByUser($user);
        }

        // Toggle like
        if ($post->getLikedByUsers()->contains($user)) {
            $post->removeLikedByUser($user);
        } else {
            $post->addLikedByUser($user);
        }

        $entityManager->flush();

        return new JsonResponse([
            'likes' => $post->getLikedByUsers()->count(),
            'dislikes' => $post->getDislikedByUsers()->count()
        ]);
    }

    #[Route('/{id}/dislike', name: 'app_post_dislike', methods: ['POST'])]
    public function dislike(Post $post, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 403);
        }

        // If user liked before, remove that like
        if ($post->getLikedByUsers()->contains($user)) {
            $post->removeLikedByUser($user);
        }

        // Toggle dislike
        if ($post->getDislikedByUsers()->contains($user)) {
            $post->removeDislikedByUser($user);
        } else {
            $post->addDislikedByUser($user);
        }

        $entityManager->flush();

        return new JsonResponse([
            'likes' => $post->getLikedByUsers()->count(),
            'dislikes' => $post->getDislikedByUsers()->count()
        ]);
    }

    #[Route('/comment/{id}/like', name: 'app_comment_like', methods: ['POST'])]
    public function likeComment(\App\Entity\Comment $comment, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 403);
        }

        if ($comment->getDislikedByUsers()->contains($user)) {
            $comment->removeDislikedByUser($user);
        }

        if ($comment->getLikedByUsers()->contains($user)) {
            $comment->removeLikedByUser($user);
        } else {
            $comment->addLikedByUser($user);
        }

        $entityManager->flush();

        return new JsonResponse([
            'likes' => $comment->getLikedByUsers()->count(),
            'dislikes' => $comment->getDislikedByUsers()->count()
        ]);
    }

    #[Route('/comment/{id}/dislike', name: 'app_comment_dislike', methods: ['POST'])]
    public function dislikeComment(\App\Entity\Comment $comment, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 403);
        }

        if ($comment->getLikedByUsers()->contains($user)) {
            $comment->removeLikedByUser($user);
        }

        if ($comment->getDislikedByUsers()->contains($user)) {
            $comment->removeDislikedByUser($user);
        } else {
            $comment->addDislikedByUser($user);
        }

        $entityManager->flush();

        return new JsonResponse([
            'likes' => $comment->getLikedByUsers()->count(),
            'dislikes' => $comment->getDislikedByUsers()->count()
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Auto-create a default category if none exist yet to prevent form validation loops
        $categoryRepo = $entityManager->getRepository(\App\Entity\Category::class);
        if ($categoryRepo->count([]) === 0) {
            $cat = new \App\Entity\Category();
            $cat->setName('General');
            $entityManager->persist($cat);
            $entityManager->flush();
        }

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$post->getCreatedAt()) {
                $post->setCreatedAt(new \DateTime());
                $post->setLikes(0);
                $post->setDislikes(0);
            }

            // Handle file upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move($this->getParameter('photos_directory'), $newFilename);
                    $post->setPhoto('/uploads/posts/'.$newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading file: '.$e->getMessage());
                }
            }

            $post->setUser($this->getUser());
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        $form = $this->createForm(\App\Form\CommentType::class, new \App\Entity\Comment());

        $topLevelComments = $post->getComments()->filter(fn($c) => $c->getParent() === null)->toArray();
        usort($topLevelComments, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comment_form' => $form->createView(),
            'topLevelComments' => $topLevelComments,
        ]);
    }

    #[Route('/{id}/comment', name: 'app_post_comment', methods: ['POST'])]
    public function comment(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $comment = new \App\Entity\Comment();
        $comment->setPost($post);
        
        // Handle nested reply
        $parentId = $request->request->get('parentId');
        if ($parentId) {
            $parent = $entityManager->getRepository(\App\Entity\Comment::class)->find($parentId);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $form = $this->createForm(\App\Form\CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'You must be logged in to comment.');
                return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
            }
            $comment->setCreatedAt(new \DateTime());
            $comment->setUser($user);
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Comment saved!');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($post->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only edit your own posts.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                // Delete old photo if exists
                if ($post->getPhoto()) {
                    $oldPath = $this->getParameter('kernel.project_dir').'/public'.$post->getPhoto();
                    if (file_exists($oldPath)) {
                        $this->filesystem->remove($oldPath);
                    }
                }

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move($this->getParameter('photos_directory'), $newFilename);
                    $post->setPhoto('/uploads/posts/'.$newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading file: '.$e->getMessage());
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($post->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only delete your own posts.');
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->get('_token'))) {
            if ($post->getPhoto()) {
                $photoPath = $this->getParameter('kernel.project_dir').'/public'.$post->getPhoto();
                if (file_exists($photoPath)) {
                    try {
                        $this->filesystem->remove($photoPath);
                    } catch (\Exception $e) {
                        // ignore photo deletion err
                    }
                }
            }

            foreach ($post->getComments() as $comment) {
                $entityManager->remove($comment);
            }

            $entityManager->remove($post);

            try {
                $entityManager->flush();
                $this->addFlash('success', 'Post deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Unable to delete post: '.$e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token. The page may have expired.');
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, \App\Entity\Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $post = $comment->getPost();
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->get('_token'))) {
            // Check permissions (owner or admin)
            if ($this->getUser() === $comment->getUser() || $this->isGranted('ROLE_ADMIN')) {
                $entityManager->remove($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Comment deleted successfully.');
            } else {
                $this->addFlash('error', 'Access denied.');
            }
        }

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }
    #[Route('/api/advice', name: 'api_deepseek_advice', methods: ['GET'])]
    public function getAdvice(): JsonResponse
    {
        $apiKey = $_ENV['DEEPSEEK_API_KEY'] ?? '';
        if (!$apiKey) {
            return new JsonResponse(['error' => 'API key missing'], 500);
        }

        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Give me one short sentence of random wisdom, advice, or motivational thought. Do not use quotes or introductory text.'
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.7
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['choices'][0]['message']['content'])) {
                return new JsonResponse([
                    'advice' => trim($responseData['choices'][0]['message']['content'], ' "')
                ]);
            }
        }
        
        return new JsonResponse(['error' => 'Failed to generate advice'], 500);
    }
}
