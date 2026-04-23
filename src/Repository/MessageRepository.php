<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.expediteur = :u1 AND m.destinataire = :u2)')
            ->orWhere('(m.expediteur = :u2 AND m.destinataire = :u1)')
            ->setParameter('u1', $user1)
            ->setParameter('u2', $user2)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.destinataire = :user')
            ->andWhere('m.lu = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAsRead(User $sender, User $receiver): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.lu', true)
            ->where('m.expediteur = :sender')
            ->andWhere('m.destinataire = :receiver')
            ->andWhere('m.lu = false')
            ->setParameter('sender', $sender)
            ->setParameter('receiver', $receiver)
            ->getQuery()
            ->execute();
    }

    public function getContacts(User $user): array
    {
        $sent = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.destinataire) as contact_id')
            ->where('m.expediteur = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $received = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.expediteur) as contact_id')
            ->where('m.destinataire = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $ids = array_unique(array_merge(
            array_column($sent, 'contact_id'),
            array_column($received, 'contact_id')
        ));

        return $ids;
    }
}