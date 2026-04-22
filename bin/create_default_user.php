<?php
// bin/create_default_user.php

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env.local');
$dotenv->load(__DIR__ . '/../.env');

$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool)($_ENV['APP_DEBUG'] ?? 1));
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine.orm.entity_manager');

/** @var UserPasswordHasherInterface $hasher */
$hasher = $container->get('security.password_hasher');

// Check if user exists
$userRepo = $em->getRepository(User::class);
$user = $userRepo->findOneBy(['email' => 'demo@community.local']);

if (!$user) {
    $user = new User();
    $user->setNom('User');
    $user->setPrenom('Demo');
    $user->setEmail('demo@community.local');
    $user->setNum_tel('1234567890');
    $user->setRole('ROLE_USER');
    
    // Hash password "demo123"
    $hashedPassword = $hasher->hashPassword($user, 'demo123');
    $user->setMdp($hashedPassword);
    
    $em->persist($user);
    $em->flush();
    
    echo "Default user created with id_user: " . $user->getId_user() . "\n";
    echo "Email: demo@community.local\n";
    echo "Password: demo123\n";
} else {
    echo "User already exists with id_user: " . $user->getId_user() . "\n";
}
