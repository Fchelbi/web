<?php

namespace App\Form;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('psychologue', EntityType::class, [
                'class' => User::class,
                'label' => 'Psychologue',
                'placeholder' => 'Choisir un psychologue',
                'choice_label' => static function (User $psychologue): string {
                    return sprintf('%s - %s', $psychologue->getNomComplet(), $psychologue->getEmail());
                },
                'query_builder' => static function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('u')
                        ->andWhere('u.role = :role')
                        ->setParameter('role', User::ROLE_COACH)
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif de consultation',
                'required' => true,
                'help' => 'Expliquez brievement la raison de votre demande de consultation.',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Exemple : Je souhaite parler de mon stress, de mon anxiete ou de mes difficultes de sommeil.',
                ],
            ])
            ->add('dateConsultation', DateTimeType::class, [
                'label' => 'Date de consultation',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'input' => 'datetime',
                'invalid_message' => 'Veuillez saisir une date et une heure valides.',
                'help' => 'Choisissez une date dans le futur, au moins 1 heure a l avance, entre 08:00 et 20:00.',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime('+1 hour'))->format('Y-m-d\TH:i'),
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
