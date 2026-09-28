<?php

namespace App\Controller\Admin;

use App\Security\Voter\AdminVoter;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Override;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin-trackyourtime', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    #[IsGranted(
        new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')"),
    )]
    public function index(): Response
    {
        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // return $this->redirectToRoute('admin_user_index');

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');

        return $this->render("admin/index.html.twig");
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Track Your Time Admin');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Home', 'fa fa-dashboard'),
        
            MenuItem::section("Manufactures"),
            MenuItem::linkTo(ManufactureCrudController::class, "Manufacture", "fa fa-tags"),

            MenuItem::section("Cars"),
            MenuItem::linkTo(CarCrudController::class, "Car", "fa fa-car"),

            MenuItem::section("Circuit User Time"),
            MenuItem::linkTo(CircuitTimeCrudController::class, "Circut Time", "fa fa-clock-o"),

            MenuItem::section("Friendships"),
            MenuItem::linkTo(FriendshipCrudController::class, "Friendship", "fa fa-users"),

            MenuItem::section("Users"),
            MenuItem::linkTo(UserCrudController::class, "User", "fa fa-user")->setPermission(AdminVoter::EDIT),

            MenuItem::section("Circuits"),
            MenuItem::linkTo(CircuitCrudController::class, "Circuit", "fa fa-flag-checkered"),

            MenuItem::section("Statuses"),
            MenuItem::linkTo(StatusCrudController::class, "Status", "fa fa-thumbtack"),

            MenuItem::section("Grades"),
            MenuItem::linkTo(GradeCrudController::class, "Grade", "fa fa-tags"),

            MenuItem::section("Types"),
            MenuItem::linkTo(TypeCrudController::class, "Type", "fa fa-tags"),

            MenuItem::section("Countries"),
            MenuItem::linkTo(CountryCrudController::class, "Country", "fa fa-tags"),

            MenuItem::section("Models"),
            MenuItem::linkTo(ModelCrudController::class, "Model", "fa fa-tags"),
        ];
        // yield MenuItem::linkTo(SomeCrudController::class, 'The Label', 'fas fa-list');
    }

    #[Override]
    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    #[Override]
    public function configureAssets(): Assets
    {
        return parent::configureAssets();
    }
}
