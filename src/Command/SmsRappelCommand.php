<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\BienEtreRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:sms-rappel', description: 'Envoie email rappel aux patients')]
class SmsRappelCommand extends Command
{
    public function __construct(
        private UserRepository $userRepo,
        private BienEtreRepository $bienEtreRepo,
        private MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $patients = $this->userRepo->findBy(['role' => 'Patient']);
        $today    = new \DateTimeImmutable('today');
        $count    = 0;

        foreach ($patients as $patient) {
            if (!$patient->getEmail()) continue;

            $alreadyToday = $this->bienEtreRepo->createQueryBuilder('b')
                ->where('b.user = :user')
                ->andWhere('b.createdAt >= :start')
                ->andWhere('b.createdAt <= :end')
                ->setParameter('user', $patient)
                ->setParameter('start', $today)
                ->setParameter('end', $today->modify('+1 day'))
                ->getQuery()
                ->getOneOrNullResult();

            if (!$alreadyToday) {
                try {
                    $email = (new Email())
                        ->from('emnaboughoufa123@gmail.com')
                        ->to($patient->getEmail())
                        ->subject('🧠 EchoCare — Comment vous sentez-vous aujourd\'hui ?')
                        ->html('
                            <div style="font-family:Segoe UI,sans-serif;max-width:500px;margin:0 auto;background:#0D1B2A;border-radius:16px;overflow:hidden;">
                                <div style="background:linear-gradient(135deg,#162232,#0D1B2A);padding:40px;text-align:center;">
                                    <div style="font-size:60px;margin-bottom:16px;">🧠</div>
                                    <h1 style="color:white;font-size:24px;margin:0;">Echo<span style="color:#E8895A;">Care</span></h1>
                                    <p style="color:#8899AA;font-size:13px;margin-top:6px;">Votre espace bien-etre</p>
                                </div>
                                <div style="padding:32px;background:#162232;">
                                    <h2 style="color:white;font-size:20px;margin-bottom:12px;">
                                        Bonjour ' . $patient->getPrenom() . ' ! 👋
                                    </h2>
                                    <p style="color:#8899AA;font-size:15px;line-height:1.6;margin-bottom:24px;">
                                        Comment vous sentez-vous aujourd\'hui ? 😊<br>
                                        Prenez 1 minute pour enregistrer votre humeur du jour et suivre votre bien-etre.
                                    </p>
                                    <div style="text-align:center;margin-bottom:24px;">
                                        <a href="http://127.0.0.1:8000/patient/bien-etre"
                                           style="display:inline-block;padding:16px 32px;background:#E8895A;color:white;text-decoration:none;border-radius:10px;font-size:16px;font-weight:700;">
                                            ✏️ Enregistrer mon humeur
                                        </a>
                                    </div>
                                    <div style="display:flex;gap:12px;justify-content:center;margin-bottom:24px;">
                                        <div style="text-align:center;padding:12px 20px;background:rgba(255,255,255,0.05);border-radius:10px;">
                                            <div style="font-size:24px;">😄</div>
                                            <div style="color:#8899AA;font-size:11px;margin-top:4px;">Excellent</div>
                                        </div>
                                        <div style="text-align:center;padding:12px 20px;background:rgba(255,255,255,0.05);border-radius:10px;">
                                            <div style="font-size:24px;">🙂</div>
                                            <div style="color:#8899AA;font-size:11px;margin-top:4px;">Bien</div>
                                        </div>
                                        <div style="text-align:center;padding:12px 20px;background:rgba(255,255,255,0.05);border-radius:10px;">
                                            <div style="font-size:24px;">😐</div>
                                            <div style="color:#8899AA;font-size:11px;margin-top:4px;">Neutre</div>
                                        </div>
                                        <div style="text-align:center;padding:12px 20px;background:rgba(255,255,255,0.05);border-radius:10px;">
                                            <div style="font-size:24px;">😴</div>
                                            <div style="color:#8899AA;font-size:11px;margin-top:4px;">Fatigue</div>
                                        </div>
                                        <div style="text-align:center;padding:12px 20px;background:rgba(255,255,255,0.05);border-radius:10px;">
                                            <div style="font-size:24px;">😟</div>
                                            <div style="color:#8899AA;font-size:11px;margin-top:4px;">Triste</div>
                                        </div>
                                    </div>
                                    <p style="color:#4A5568;font-size:12px;text-align:center;">
                                        EchoCare — Votre bien-etre est notre priorite 💙
                                    </p>
                                </div>
                            </div>
                        ');

                    $this->mailer->send($email);
                    $count++;
                    $output->writeln("✅ Email envoye a " . $patient->getPrenom() . " (" . $patient->getEmail() . ")");

                } catch (\Exception $e) {
                    $output->writeln("❌ Erreur " . $patient->getPrenom() . ": " . $e->getMessage());
                }
            } else {
                $output->writeln("⏭️ " . $patient->getPrenom() . " a deja rempli aujourd'hui");
            }
        }

        $output->writeln("\n✅ Total emails envoyes : $count");
        return Command::SUCCESS;
    }
}