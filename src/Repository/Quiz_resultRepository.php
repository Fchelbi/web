<?php

namespace App\Repository;

use App\Entity\Quiz_result;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Quiz_resultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz_result::class);
    }

    // Add custom methods as needed
}