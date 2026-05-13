<?php

namespace App\Form;

use App\Entity\ConsultationEnLigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationGestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => ConsultationEnLigne::STATUT_EN_ATTENTE,
                    'Confirmée' => ConsultationEnLigne::STATUT_CONFIRMEE,
                    'Annulée' => ConsultationEnLigne::STATUT_ANNULEE,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('meetLink', UrlType::class, [
                'label' => 'Lien Google Meet',
                'required' => false,
                'default_protocol' => 'https',
                'invalid_message' => 'Veuillez saisir une URL valide.',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://meet.google.com/...',
                ],
            ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $consultation = $event->getData();

            if (!$consultation instanceof ConsultationEnLigne) {
                return;
            }

            if ($consultation->getStatut() !== ConsultationEnLigne::STATUT_CONFIRMEE) {
                $consultation->setMeetLink(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConsultationEnLigne::class,
        ]);
    }
}
