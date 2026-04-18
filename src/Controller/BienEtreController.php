<?php

namespace App\Controller;

use App\Entity\BienEtre;
use App\Repository\BienEtreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
class BienEtreController extends AbstractController
{
    #[Route('/patient/bien-etre', name: 'patient_bien_etre')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        BienEtreRepository $repo
    ): Response {
        $user = $this->getUser();

        // Vérifie si déjà rempli aujourd'hui
        $today = new \DateTime();
        $todayStr = $today->format('Y-m-d');

        $alreadyToday = $repo->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.createdAt >= :start')
            ->andWhere('b.createdAt <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', new \DateTimeImmutable($todayStr . ' 00:00:00'))
            ->setParameter('end', new \DateTimeImmutable($todayStr . ' 23:59:59'))
            ->getQuery()
            ->getOneOrNullResult();

        $success = $request->query->get('success') ? 'Humeur enregistree avec succes ! ✅' : null;
        $error = null;

        if ($request->isMethod('POST') && !$alreadyToday) {
            $bienEtre = new BienEtre();
            $bienEtre->setUser($user);
            $bienEtre->setSommeil((int) $request->request->get('sommeil'));
            $bienEtre->setStress((int) $request->request->get('stress'));
            $bienEtre->setHumeur((int) $request->request->get('humeur'));
            $bienEtre->setMood($request->request->get('mood'));
            $bienEtre->setCreatedAt(new \DateTimeImmutable());

            $em->persist($bienEtre);
            $em->flush();

            return $this->redirectToRoute('patient_bien_etre', ['success' => 1]);
        } elseif ($request->isMethod('POST') && $alreadyToday) {
            $error = 'Vous avez déjà enregistré votre humeur aujourd\'hui !';
        }

        // Données du mois pour le calendrier
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth   = new \DateTimeImmutable('last day of this month 23:59:59');

        $monthData = $repo->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.createdAt >= :start')
            ->andWhere('b.createdAt <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Organise par jour
        $calendarData = [];
        foreach ($monthData as $entry) {
            $day = $entry->getCreatedAt()->format('d');
            $calendarData[(int)$day] = $entry;
        }

        return $this->render('bien_etre/index.html.twig', [
            'alreadyToday' => $alreadyToday,
            'success'      => $success,
            'error'        => $error,
            'calendarData' => $calendarData,
            'currentMonth' => $today->format('m'),
            'currentYear'  => $today->format('Y'),
            'daysInMonth'  => (int)$today->format('t'),
            'firstDayOfMonth' => (int)(new \DateTime('first day of this month'))->format('N'),
        ]);
    }
}