<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;

class MailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        // Fix SSL pour XAMPP local
        $transport = Transport::fromDsn($_ENV['MAILER_DSN'] ?? 'null://null');
        
        if (method_exists($transport, 'getStream')) {
            $stream = $transport->getStream();
            if (method_exists($stream, 'setStreamOptions')) {
                $stream->setStreamOptions([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ]
                ]);
            }
        }
        
        $this->mailer = $mailer;
    }

    public function sendVerificationEmail(User $user, string $token): void
    {
        $email = (new Email())
            ->from('noreply@echocare.com')
            ->to($user->getEmail())
            ->subject('✅ Vérifiez votre compte EchoCare')
            ->html("
                <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;padding:30px;'>
                    <h2 style='color:#4A6FA5'>Bienvenue sur EchoCare 🧠</h2>
                    <p>Bonjour <strong>{$user->getNom()} {$user->getPrenom()}</strong>,</p>
                    <p>Cliquez sur le bouton ci-dessous pour vérifier votre compte :</p>
                    <a href='http://127.0.0.1:8000/verify/{$token}'
                       style='display:inline-block;margin:20px 0;padding:14px 28px;
                              background:#E8895A;color:white;border-radius:10px;
                              text-decoration:none;font-weight:600;'>
                        ✅ Vérifier mon compte
                    </a>
                    <p style='color:#999;font-size:12px;'>Ce lien expire dans 24h.</p>
                </div>
            ");
        $this->mailer->send($email);
    }

    public function sendTwoFactorCode(User $user, string $code): void
    {
        $email = (new Email())
            ->from('noreply@echocare.com')
            ->to($user->getEmail())
            ->subject('🔐 Votre code de connexion EchoCare')
            ->html("
                <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;padding:30px;'>
                    <h2 style='color:#4A6FA5'>Code de connexion 🔐</h2>
                    <p>Bonjour <strong>{$user->getNom()} {$user->getPrenom()}</strong>,</p>
                    <p>Votre code de connexion est :</p>
                    <div style='text-align:center;margin:30px 0;'>
                        <span style='font-size:40px;font-weight:700;letter-spacing:12px;
                                    color:#E8895A;background:#FDF8F3;padding:16px 30px;
                                    border-radius:12px;border:2px dashed #E8895A;'>
                            {$code}
                        </span>
                    </div>
                    <p style='color:#999;font-size:12px;'>Ce code expire dans 10 minutes.</p>
                    <p style='color:#999;font-size:12px;'>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                </div>
            ");
        $this->mailer->send($email);
    }

    public function sendResetPasswordEmail(User $user, string $token): void
    {
        $email = (new Email())
            ->from('noreply@echocare.com')
            ->to($user->getEmail())
            ->subject('🔑 Réinitialisation de mot de passe EchoCare')
            ->html("
                <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;padding:30px;'>
                    <h2 style='color:#4A6FA5'>Mot de passe oublié ? 🔑</h2>
                    <p>Bonjour <strong>{$user->getNom()} {$user->getPrenom()}</strong>,</p>
                    <p>Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :</p>
                    <a href='http://127.0.0.1:8000/reset-password/{$token}'
                       style='display:inline-block;margin:20px 0;padding:14px 28px;
                              background:#4A6FA5;color:white;border-radius:10px;
                              text-decoration:none;font-weight:600;'>
                        🔑 Réinitialiser mon mot de passe
                    </a>
                    <p style='color:#999;font-size:12px;'>Ce lien expire dans 1h.</p>
                </div>
            ");
        $this->mailer->send($email);
    }
    public function sendSecurityAlert(string $ip, string $email, int $attempts): void
    {
        $emailMessage = (new \Symfony\Component\Mime\Email())
            ->from('emnaboughoufa123@gmail.com')
            ->to($email)
            ->subject('🚨 EchoCare — Tentative de connexion suspecte !')
            ->html('
                <div style="font-family:Segoe UI,sans-serif;max-width:500px;margin:0 auto;background:#0D1B2A;border-radius:16px;overflow:hidden;">
                    <div style="background:#e74c3c;padding:24px;text-align:center;">
                        <h1 style="color:white;font-size:22px;margin:0;">🚨 Alerte Securite</h1>
                    </div>
                    <div style="padding:32px;background:#162232;">
                        <h2 style="color:white;font-size:18px;margin-bottom:16px;">Tentative de brute force detectee !</h2>
                        <div style="background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);border-radius:10px;padding:16px;margin-bottom:16px;">
                            <p style="color:#e74c3c;margin:0 0 8px;"><strong>IP :</strong> ' . $ip . '</p>
                            <p style="color:#e74c3c;margin:0 0 8px;"><strong>Email cible :</strong> ' . $email . '</p>
                            <p style="color:#e74c3c;margin:0;"><strong>Tentatives :</strong> ' . $attempts . ' fois</p>
                        </div>
                        <p style="color:#8899AA;font-size:13px;">L\'IP a ete automatiquement bloquee pendant 15 minutes.</p>
                        <a href="http://127.0.0.1:8000/admin/security" 
                        style="display:inline-block;margin-top:16px;padding:12px 24px;background:#e74c3c;color:white;text-decoration:none;border-radius:8px;font-weight:600;">
                            Voir les tentatives suspectes
                        </a>
                    </div>
                </div>
            ');

        $this->mailer->send($emailMessage);
    }
}