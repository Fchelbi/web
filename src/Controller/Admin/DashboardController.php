<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\BienEtre;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractDashboardController
{
    #[Route('/easyadmin', name: 'easyadmin')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('admin/easyadmin_dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<span style="color:#E8895A">Echo</span>Care Admin')
            ->setFaviconPath('favicon.ico')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('🏠 Dashboard', 'fa fa-home');
        yield MenuItem::section('👥 Utilisateurs');
        yield MenuItem::linkToCrud('Tous les users', 'fa fa-users', User::class);
        yield MenuItem::section('🌿 Bien-etre');
        yield MenuItem::linkToCrud('Evaluations', 'fa fa-heart', BienEtre::class);
        yield MenuItem::section('🔗 Liens rapides');
        yield MenuItem::linkToUrl('Mon Dashboard', 'fa fa-tachometer', '/admin/dashboard');
        yield MenuItem::linkToUrl('Analytics', 'fa fa-chart-bar', '/admin/analytics');
        yield MenuItem::linkToUrl('Deconnexion', 'fa fa-sign-out', '/logout');
    }
}