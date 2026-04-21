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

    #[Route('/test-delete/{id}', name: 'app_post_test_delete', methods: ['GET'])]
    public function testDelete(Post $post): Response
    {
        file_put_contents(__DIR__.'/../../../debug.log', date('Y-m-d H:i:s') . " - Test delete called for post ID: " . $post->getId() . "\n", FILE_APPEND);
        return new Response('Test delete called for post ' . $post->getId());
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
        $post->setLikes(($post->getLikes() ?? 0) + 1);
        $entityManager->flush();
        return new JsonResponse(['likes' => $post->getLikes()]);
    }

    #[Route('/{id}/dislike', name: 'app_post_dislike', methods: ['POST'])]
    public function dislike(Post $post, EntityManagerInterface $entityManager): JsonResponse
    {
        $post->setDislikes(($post->getDislikes() ?? 0) + 1);
        $entityManager->flush();
        return new JsonResponse(['dislikes' => $post->getDislikes()]);
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

            // Assign to default admin user
            $userRepo = $entityManager->getRepository(\App\Entity\User::class);
            $user = $userRepo->find(1);
            if (!$user) {
                $user = $userRepo->findOneBy([]);
            }
            $post->setUser($user);
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

        return $this->renderForm('post/show.html.twig', [
            'post' => $post,
            'comment_form' => $form,
        ]);
    }

    #[Route('/{id}/comment', name: 'app_post_comment', methods: ['POST'])]
    public function comment(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $comment = new \App\Entity\Comment();
        $comment->setPost($post);
        $form = $this->createForm(\App\Form\CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime());
            $userRepo = $entityManager->getRepository(\App\Entity\User::class);
            $user = $userRepo->find(1);
            if (!$user) {
                $user = $userRepo->findOneBy([]);
            }
            $comment->setUser($user);
            $entityManager->persist($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
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

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
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
}
