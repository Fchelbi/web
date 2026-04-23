<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\BienEtreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/coach')]
class CoachController extends AbstractController
{
    #[Route('/dashboard', name: 'coach_dashboard')]
    public function index(UserRepository $userRepo): Response
    {
        // Vérifie manuellement le rôle
        if (!$this->getUser() || !in_array('ROLE_COACH', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $patients = $userRepo->findBy(['role' => 'Patient']);

        return $this->render('coach/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/patient/{id}', name: 'coach_patient_detail')]
    public function patientDetail(
        int $id,
        UserRepository $userRepo,
        BienEtreRepository $bienEtreRepo
    ): Response {
        if (!$this->getUser() || !in_array('ROLE_COACH', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $patient = $userRepo->find($id);

        if (!$patient || $patient->getRole() !== 'Patient') {
            return $this->redirectToRoute('coach_dashboard');
        }

        $data = $bienEtreRepo->findBy(
            ['user' => $patient],
            ['createdAt' => 'ASC']
        );

        $today        = new \DateTime();
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
            'patient'         => $patient,
            'data'            => $data,
            'calendarData'    => $calendarData,
            'daysInMonth'     => (int)$today->format('t'),
            'firstDayOfMonth' => (int)(new \DateTime('first day of this month'))->format('N'),
            'avgSommeil'      => $avgSommeil,
            'avgStress'       => $avgStress,
            'avgHumeur'       => $avgHumeur,
            'totalJours'      => count($data),
        ]);
    }
}