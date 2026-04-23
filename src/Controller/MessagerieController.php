<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\BadWordsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MessagerieController extends AbstractController
{
    #[Route('/messagerie', name: 'messagerie')]
    public function index(
        MessageRepository $msgRepo,
        UserRepository $userRepo
    ): Response {
        $user = $this->getUser();
        $role = $user->getRole();

        if ($role === 'Patient') {
            $contacts = $userRepo->findBy(['role' => 'Coach']);
        } elseif ($role === 'Coach') {
            $contacts = $userRepo->findBy(['role' => 'Patient']);
        } else {
            $contacts = $userRepo->findAll();
        }

        $unreadCount = $msgRepo->countUnread($user);

        return $this->render('messagerie/index.html.twig', [
            'contacts'    => $contacts,
            'unreadCount' => $unreadCount,
            'currentUser' => $user,
        ]);
    }

    // ✅ Routes spécifiques AVANT les routes avec {id}
    #[Route('/messagerie/notifications/check', name: 'messagerie_notif_check', methods: ['GET'])]
    public function checkNotifications(MessageRepository $msgRepo): JsonResponse
    {
        $user     = $this->getUser();
        $messages = $msgRepo->createQueryBuilder('m')
            ->where('m.destinataire = :user')
            ->andWhere('m.lu = false')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($messages as $msg) {
            $data[] = [
                'id'         => $msg->getId(),
                'contenu'    => $msg->getContenu(),
                'senderId'   => $msg->getExpediteur()->getId(),
                'senderName' => $msg->getExpediteur()->getPrenom() . ' ' . $msg->getExpediteur()->getNom(),
                'dateEnvoi'  => $msg->getDateEnvoi()->format('H:i'),
            ];
        }

        return new JsonResponse(['messages' => $data]);
    }

    #[Route('/messagerie/unread', name: 'messagerie_unread', methods: ['GET'])]
    public function unread(MessageRepository $msgRepo): JsonResponse
    {
        return new JsonResponse(['count' => $msgRepo->countUnread($this->getUser())]);
    }

    #[Route('/messagerie/msg/{id}/edit', name: 'messagerie_edit_msg', methods: ['POST'])]
    public function editMsg(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        BadWordsService $badWords
    ): JsonResponse {
        $msg  = $em->getRepository(Message::class)->find($id);
        $user = $this->getUser();

        if (!$msg || $msg->getExpediteur()->getId() !== $user->getId()) {
            return new JsonResponse(['success' => false], 403);
        }

        $data    = json_decode($request->getContent(), true);
        $contenu = $data['contenu'] ?? '';

        if ($badWords->containsBadWords($contenu)) {
            $result = $badWords->handleBadWord($user);
            return new JsonResponse([
                'success' => false,
                'warning' => true,
                'message' => $result['message'],
            ]);
        }

        $msg->setContenu($contenu);
        $msg->setModifie(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/messagerie/msg/{id}/delete', name: 'messagerie_delete_msg', methods: ['POST'])]
    public function deleteMsg(int $id, EntityManagerInterface $em): JsonResponse
    {
        $msg  = $em->getRepository(Message::class)->find($id);
        $user = $this->getUser();

        if (!$msg || $msg->getExpediteur()->getId() !== $user->getId()) {
            return new JsonResponse(['success' => false], 403);
        }

        $em->remove($msg);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    // ✅ Routes avec {id} APRÈS les routes spécifiques
    #[Route('/messagerie/{id}', name: 'messagerie_conversation')]
    public function conversation(
        int $id,
        MessageRepository $msgRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $user    = $this->getUser();
        $contact = $userRepo->find($id);

        if (!$contact) return $this->redirectToRoute('messagerie');

        $msgRepo->markAsRead($contact, $user);
        $messages    = $msgRepo->getConversation($user, $contact);
        $unreadCount = $msgRepo->countUnread($user);
        $role        = $user->getRole();

        if ($role === 'Patient') {
            $contacts = $userRepo->findBy(['role' => 'Coach']);
        } elseif ($role === 'Coach') {
            $contacts = $userRepo->findBy(['role' => 'Patient']);
        } else {
            $contacts = $userRepo->findAll();
        }

        return $this->render('messagerie/index.html.twig', [
            'contacts'    => $contacts,
            'contact'     => $contact,
            'messages'    => $messages,
            'unreadCount' => $unreadCount,
            'currentUser' => $user,
        ]);
    }

    #[Route('/messagerie/{id}/send', name: 'messagerie_send', methods: ['POST'])]
    public function send(
        int $id,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        BadWordsService $badWords
    ): JsonResponse {
        $user    = $this->getUser();
        $contact = $userRepo->find($id);
        $contenu = $request->request->get('contenu');

        if (!$contact || !$contenu) {
            return new JsonResponse(['success' => false], 400);
        }

        if ($user->isBanned()) {
            return new JsonResponse([
                'success' => false,
                'banned'  => true,
                'message' => '🚫 Votre compte est banni.',
            ], 403);
        }

        if ($badWords->containsBadWords($contenu)) {
            $result = $badWords->handleBadWord($user);
            if ($result['action'] === 'banned') {
                return new JsonResponse([
                    'success' => false,
                    'banned'  => true,
                    'message' => '🚫 ' . $result['message'],
                ], 403);
            }
            return new JsonResponse([
                'success' => false,
                'warning' => true,
                'message' => $result['message'],
                'count'   => $result['count'],
            ]);
        }

        $message = new Message();
        $message->setExpediteur($user);
        $message->setDestinataire($contact);
        $message->setContenu($contenu);
        $message->setDateEnvoi(new \DateTime());

        $em->persist($message);
        $em->flush();

        return new JsonResponse([
            'success'      => true,
            'id'           => $message->getId(),
            'contenu'      => $message->getContenu(),
            'dateEnvoi'    => $message->getDateEnvoi()->format('H:i'),
            'expediteurId' => $user->getId(),
        ]);
    }

    #[Route('/messagerie/{id}/messages', name: 'messagerie_get', methods: ['GET'])]
    public function getMessages(
        int $id,
        MessageRepository $msgRepo,
        UserRepository $userRepo
    ): JsonResponse {
        $user    = $this->getUser();
        $contact = $userRepo->find($id);

        if (!$contact) return new JsonResponse([], 404);

        $messages = $msgRepo->getConversation($user, $contact);
        $data     = [];

        foreach ($messages as $msg) {
            $data[] = [
                'id'           => $msg->getId(),
                'contenu'      => $msg->getContenu(),
                'dateEnvoi'    => $msg->getDateEnvoi()->format('H:i'),
                'expediteurId' => $msg->getExpediteur()->getId(),
                'lu'           => $msg->isLu(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/messagerie/{id}/delete-conversation', name: 'messagerie_delete_conv', methods: ['POST'])]
    public function deleteConversation(
        int $id,
        MessageRepository $msgRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user    = $this->getUser();
        $contact = $userRepo->find($id);

        if (!$contact) return new JsonResponse(['success' => false], 404);

        $messages = $msgRepo->getConversation($user, $contact);
        foreach ($messages as $msg) {
            $em->remove($msg);
        }
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/messagerie/{id}/status', name: 'messagerie_status', methods: ['GET'])]
    public function contactStatus(int $id, UserRepository $userRepo): JsonResponse
    {
        $contact = $userRepo->find($id);
        if (!$contact) return new JsonResponse(['online' => false]);

        $lastActivity = $contact->getUpdatedAt();
        $online       = $lastActivity && $lastActivity > new \DateTimeImmutable('-5 minutes');

        return new JsonResponse(['online' => $online]);
    }

    
}