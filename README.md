# EchoCare — Plateforme de Santé & Bien-être

## Description
EchoCare is a comprehensive health and wellness platform built with Symfony 6.4 (Web) and JavaFX (Desktop), sharing a single MySQL database for full integration.

## Modules
- **Formation & Quiz** — Health formations with AI quiz generation, chatbot, sentiment analysis, PDF certificates, QR codes
- **Gestion User** — User management with Face ID, Touch ID WebAuthn, 2FA, brute force protection
- **Forum** — Community forum with posts, comments, categories, moderation
- **Consultation** — Patient-coach consultation with reports and messaging

## Technologies
- PHP 8.2 / Symfony 6.4 / Doctrine ORM
- JavaFX 21 / Maven / Java 17
- MySQL 8.0 (shared database)
- OpenRouter AI API

## Installation
```bash
composer install
cp .env.example .env
php bin/console doctrine:schema:update --force
php -S 0.0.0.0:8000 -t public
```

## How to Use
1. Open http://localhost:8000/login
2. Login as Admin — access dashboard, manage users, formations, forum
3. Login as Coach — view patients, create formations, generate AI quiz
4. Login as Patient — enroll in formations, take quizzes, earn certificates
5. Java app — run from IntelliJ, connects to same MySQL database

## Topics
symfony php mysql ai healthcare formation quiz javafx
