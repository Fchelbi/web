<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\BienEtreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COACH')]
class CoachController extends AbstractController
{
    #[Route('/coach/dashboard', name: 'coach_dashboard')]
    public function index(UserRepository $userRepo): Response
    {
        $patients = $userRepo->findBy(['role' => 'Patient']);

        return $this->render('coach/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/coach/patient/{id}', name: 'coach_patient_detail')]
    public function patientDetail(
        int $id,
        UserRepository $userRepo,
        BienEtreRepository $bienEtreRepo
    ): Response {
        $patient = $userRepo->find($id);

        if (!$patient || $patient->getRole() !== 'Patient') {
            return $this->redirectToRoute('coach_dashboard');
        }

        // Toutes les données bien-être du patient
        $data = $bienEtreRepo->findBy(
            ['user' => $patient],
            ['createdAt' => 'ASC']
        );

        // Calendrier du mois
        $today = new \DateTime();
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth   = new \DateTimeImmutable('last day of this month 23:59:59');

        $monthData = $bienEtreRepo->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.createdAt >= :start')
            ->andWhere('b.createdAt <= :end')
            ->setParameter('user', $patient)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $calendarData = [];
        foreach ($monthData as $entry) {
            $day = $entry->getCreatedAt()->format('d');
            $calendarData[(int)$day] = $entry;
        }

        // Moyennes
        $avgSommeil = 0; $avgStress = 0; $avgHumeur = 0;
        if (count($data) > 0) {
            foreach ($data as $d) {
                $avgSommeil += $d->getSommeil();
                $avgStress  += $d->getStress();
                $avgHumeur  += $d->getHumeur();
            }
            $avgSommeil = round($avgSommeil / count($data));
            $avgStress  = round($avgStress  / count($data));
            $avgHumeur  = round($avgHumeur  / count($data));
        }

        return $this->render('coach/patient_detail.html.twig', [
            'patient'      => $patient,
            'data'         => $data,
            'calendarData' => $calendarData,
            'daysInMonth'  => (int)$today->format('t'),
            'firstDayOfMonth' => (int)(new \DateTime('first day of this month'))->format('N'),
            'avgSommeil'   => $avgSommeil,
            'avgStress'    => $avgStress,
            'avgHumeur'    => $avgHumeur,
            'totalJours'   => count($data),
        ]);
    }
}