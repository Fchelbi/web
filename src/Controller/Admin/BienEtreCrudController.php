<?php

namespace App\Controller\Admin;

use App\Entity\BienEtre;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class BienEtreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BienEtre::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Evaluation')
            ->setEntityLabelInPlural('Evaluations Bien-etre')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user', 'Patient');
        yield TextField::new('mood', 'Humeur');
        yield IntegerField::new('sommeil', 'Sommeil %');
        yield IntegerField::new('stress', 'Stress %');
        yield IntegerField::new('humeur', 'Humeur %');
        
    }
}