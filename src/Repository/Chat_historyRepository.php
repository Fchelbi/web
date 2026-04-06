<?php

namespace App\Repository;

use App\Entity\Chat_history;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Chat_historyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat_history::class);
    }

    // Add custom methods as needed
}