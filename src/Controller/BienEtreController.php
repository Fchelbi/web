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
        $success = null;

        // Récupère la dernière entrée
        $last = $repo->findOneBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        if ($request->isMethod('POST')) {
            $bienEtre = new BienEtre();
            $bienEtre->setUser($user);
            $bienEtre->setSommeil((int) $request->request->get('sommeil'));
            $bienEtre->setStress((int) $request->request->get('stress'));
            $bienEtre->setHumeur((int) $request->request->get('humeur'));
            $bienEtre->setCreatedAt(new \DateTimeImmutable());

            $em->persist($bienEtre);
            $em->flush();

            $success = 'Données enregistrées avec succès !';
            $last = $bienEtre;
        }

        return $this->render('bien_etre/index.html.twig', [
            'last' => $last,
            'success' => $success,
        ]);
    }
}