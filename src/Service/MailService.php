<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService
{
    private string $from = 'emnaboughoufa123@gmail.com';
    private string $password = 'ccrbfyrztwyomzct';

    public function __construct(private MailerInterface $mailer) {}

    private function sendViaCurl(string $to, string $subject, string $body): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://smtp.gmail.com:587',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USE_SSL        => CURLUSESSL_ALL,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_MAIL_FROM      => '<' . $this->from . '>',
            CURLOPT_MAIL_RCPT      => ['<' . $to . '>'],
            CURLOPT_USERNAME       => $this->from,
            CURLOPT_PASSWORD       => $this->password,
        ]);
        curl_close($ch);

        // Utilise Symfony Mailer avec SSL désactivé
        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject($subject)
            ->html($body);

        $this->mailer->send($email);
    }

    public function sendVerificationEmail(User $user, string $token): void
    {
        $body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;background:#0D1B2A;border-radius:16px;overflow:hidden;'>
                <div style='background:linear-gradient(135deg,#162232,#0D1B2A);padding:40px;text-align:center;'>
                    <div style='font-size:60px;margin-bottom:16px;'>🧠</div>
                    <h1 style='color:white;font-size:24px;margin:0;'>Echo<span style='color:#E8895A;'>Care</span></h1>
                </div>
                <div style='padding:32px;background:#162232;'>
                    <h2 style='color:white;font-size:18px;margin-bottom:12px;'>Bonjour {$user->getPrenom()} ! 👋</h2>
                    <p style='color:#8899AA;font-size:15px;margin-bottom:24px;'>Cliquez sur le bouton ci-dessous pour verifier votre compte :</p>
                    <div style='text-align:center;margin-bottom:24px;'>
                        <a href='http://127.0.0.1:8000/verify/{$token}'
                           style='display:inline-block;padding:16px 32px;background:#E8895A;color:white;text-decoration:none;border-radius:10px;font-size:16px;font-weight:700;'>
                            ✅ Verifier mon compte
                        </a>
                    </div>
                    <p style='color:#4A5568;font-size:12px;text-align:center;'>Ce lien expire dans 24h.</p>
                </div>
            </div>
        ";
        $this->sendViaCurl($user->getEmail(), '✅ Verifiez votre compte EchoCare', $body);
    }

    public function sendTwoFactorCode(User $user, string $code): void
    {
        $body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;background:#0D1B2A;border-radius:16px;overflow:hidden;'>
                <div style='background:linear-gradient(135deg,#162232,#0D1B2A);padding:40px;text-align:center;'>
                    <div style='font-size:60px;margin-bottom:16px;'>🔐</div>
                    <h1 style='color:white;font-size:24px;margin:0;'>Echo<span style='color:#E8895A;'>Care</span></h1>
                </div>
                <div style='padding:32px;background:#162232;'>
                    <h2 style='color:white;font-size:18px;margin-bottom:12px;'>Bonjour {$user->getPrenom()} ! 👋</h2>
                    <p style='color:#8899AA;font-size:15px;margin-bottom:24px;'>Votre code de connexion est :</p>
                    <div style='text-align:center;margin-bottom:24px;'>
                        <span style='font-size:40px;font-weight:700;letter-spacing:12px;color:#E8895A;background:rgba(232,137,90,0.1);padding:16px 30px;border-radius:12px;border:2px dashed #E8895A;'>
                            {$code}
                        </span>
                    </div>
                    <p style='color:#4A5568;font-size:12px;text-align:center;'>Ce code expire dans 10 minutes.</p>
                </div>
            </div>
        ";
        $this->sendViaCurl($user->getEmail(), '🔐 Votre code EchoCare', $body);
    }

    public function sendResetPasswordEmail(User $user, string $token): void
    {
        $body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;background:#0D1B2A;border-radius:16px;overflow:hidden;'>
                <div style='background:linear-gradient(135deg,#162232,#0D1B2A);padding:40px;text-align:center;'>
                    <div style='font-size:60px;margin-bottom:16px;'>🔑</div>
                    <h1 style='color:white;font-size:24px;margin:0;'>Echo<span style='color:#E8895A;'>Care</span></h1>
                </div>
                <div style='padding:32px;background:#162232;'>
                    <h2 style='color:white;font-size:18px;margin-bottom:12px;'>Bonjour {$user->getPrenom()} ! 👋</h2>
                    <p style='color:#8899AA;font-size:15px;margin-bottom:24px;'>Cliquez ci-dessous pour reinitialiser votre mot de passe :</p>
                    <div style='text-align:center;margin-bottom:24px;'>
                        <a href='http://127.0.0.1:8000/reset-password/{$token}'
                           style='display:inline-block;padding:16px 32px;background:#4A6FA5;color:white;text-decoration:none;border-radius:10px;font-size:16px;font-weight:700;'>
                            🔑 Reinitialiser mon mot de passe
                        </a>
                    </div>
                    <p style='color:#4A5568;font-size:12px;text-align:center;'>Ce lien expire dans 1h.</p>
                </div>
            </div>
        ";
        $this->sendViaCurl($user->getEmail(), '🔑 Reinitialisation mot de passe EchoCare', $body);
    }

    public function sendSecurityAlert(string $ip, string $email, int $attempts): void
    {
        $body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;background:#0D1B2A;border-radius:16px;overflow:hidden;'>
                <div style='background:#e74c3c;padding:24px;text-align:center;'>
                    <h1 style='color:white;font-size:22px;margin:0;'>🚨 Alerte Securite EchoCare</h1>
                </div>
                <div style='padding:32px;background:#162232;'>
                    <h2 style='color:white;font-size:18px;margin-bottom:16px;'>Tentative de brute force detectee !</h2>
                    <div style='background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);border-radius:10px;padding:16px;margin-bottom:16px;'>
                        <p style='color:#e74c3c;margin:0 0 8px;'><strong>IP :</strong> {$ip}</p>
                        <p style='color:#e74c3c;margin:0 0 8px;'><strong>Email cible :</strong> {$email}</p>
                        <p style='color:#e74c3c;margin:0;'><strong>Tentatives :</strong> {$attempts} fois</p>
                    </div>
                    <p style='color:#8899AA;font-size:13px;'>Le compte a ete automatiquement bloque pendant 5 minutes.</p>
                    <div style='text-align:center;margin-top:16px;'>
                        <a href='http://127.0.0.1:8000/admin/security'
                           style='display:inline-block;padding:12px 24px;background:#e74c3c;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>
                            Voir les tentatives suspectes
                        </a>
                    </div>
                </div>
            </div>
        ";

        // Envoie à l'email ciblé ET à l'admin
        $this->sendViaCurl($email, '🚨 Alerte Securite — Votre compte EchoCare', $body);
        $this->sendViaCurl($this->from, '🚨 Alerte Admin — Brute Force detecte', $body);
    }
    public function sendBanAlert(User $user): void
    {
        $body = "
            <div style='font-family:Segoe UI,sans-serif;max-width:500px;margin:auto;background:#0D1B2A;border-radius:16px;overflow:hidden;'>
                <div style='background:#e74c3c;padding:24px;text-align:center;'>
                    <h1 style='color:white;font-size:22px;margin:0;'>🚫 Compte Banni</h1>
                </div>
                <div style='padding:32px;background:#162232;'>
                    <h2 style='color:white;font-size:18px;margin-bottom:16px;'>Utilisateur banni automatiquement !</h2>
                    <div style='background:rgba(231,76,60,0.1);border:1px solid rgba(231,76,60,0.3);border-radius:10px;padding:16px;margin-bottom:16px;'>
                        <p style='color:#e74c3c;margin:0 0 8px;'><strong>Nom :</strong> {$user->getPrenom()} {$user->getNom()}</p>
                        <p style='color:#e74c3c;margin:0 0 8px;'><strong>Email :</strong> {$user->getEmail()}</p>
                        <p style='color:#e74c3c;margin:0;'><strong>Raison :</strong> 3 gros mots detectes</p>
                    </div>
                    <p style='color:#8899AA;font-size:13px;'>Le compte a ete automatiquement banni par le systeme IA.</p>
                    <div style='text-align:center;margin-top:16px;'>
                        <a href='http://127.0.0.1:8000/admin/dashboard'
                        style='display:inline-block;padding:12px 24px;background:#e74c3c;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>
                            Voir le dashboard Admin
                        </a>
                    </div>
                </div>
            </div>
        ";

        $this->sendViaCurl($this->from, '🚫 Alerte Admin — Utilisateur banni automatiquement', $body);
    }
}