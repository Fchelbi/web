<?php

namespace App\Form;

use App\Entity\ConsultationEnLigne;
use App\Entity\Psychologue;
use App\Repository\PsychologueRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('psychologue', EntityType::class, [
                'class' => Psychologue::class,
                'label' => 'Psychologue',
                'placeholder' => 'Choisir un psychologue',
                'choice_label' => static function (Psychologue $psychologue): string {
                    return sprintf(
                        '%s - %s',
                        $psychologue->getNomComplet(),
                        $psychologue->getSpecialite()
                    );
                },
                'query_builder' => static function (PsychologueRepository $repository) {
                    return $repository->createQueryBuilder('p')
                        ->leftJoin('p.user', 'u')
                        ->addSelect('u')
                        ->orderBy('u.name', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('dateConsultation', DateTimeType::class, [
                'label' => 'Date de consultation',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'input' => 'datetime',
                'invalid_message' => 'Veuillez saisir une date et une heure valides.',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime('+1 minute'))->format('Y-m-d\TH:i'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConsultationEnLigne::class,
        ]);
    }
}
