<?php

namespace App\Form;

use App\Entity\ConsultationEnLigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('statut', ChoiceType::class, [
            'label' => false,
            'required' => false,
            'placeholder' => 'Tous les statuts',
            'choices' => [
                'En attente' => ConsultationEnLigne::STATUT_EN_ATTENTE,
                'Confirmée' => ConsultationEnLigne::STATUT_CONFIRMEE,
                'Annulée' => ConsultationEnLigne::STATUT_ANNULEE,
            ],
            'attr' => [
                'class' => 'form-select',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
