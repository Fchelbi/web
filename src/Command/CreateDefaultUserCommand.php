<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateDefaultUserCommand extends Command
{
    protected static $defaultName = 'app:create-default-user';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Create a default demo user for testing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        
        // Check if user already exists
        $user = $userRepo->findOneBy(['email' => 'demo@community.local']);
        
        if ($user) {
            $output->writeln('User already exists with ID: ' . $user->getId_user());
            return Command::SUCCESS;
        }

        // Create new user
        $user = new User();
        $user->setNom('User');
        $user->setPrenom('Demo');
        $user->setEmail('demo@community.local');
        $user->setNum_tel('1234567890');
        $user->setRole('ROLE_USER');
        
        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'demo123');
        $user->setMdp($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $output->writeln('Default user created successfully!');
        $output->writeln('ID: ' . $user->getId_user());
        $output->writeln('Email: demo@community.local');
        $output->writeln('Password: demo123');
        
        return Command::SUCCESS;
    }
}
