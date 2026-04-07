<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Ex: Nutrition et bien-être',
                    'class' => 'form-input'
                ],
                'constraints' => [
                    new NotBlank(message: 'Le titre est obligatoire'),
                    new Length(min: 3, minMessage: 'Au moins 3 caractères'),
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez le contenu de la formation...',
                    'class' => 'form-input',
                    'rows' => 4
                ]
            ])
            ->add('videoUrl', UrlType::class, [
                'label' => 'URL de la vidéo',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://youtube.com/...',
                    'class' => 'form-input'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'placeholder' => '-- Choisir une catégorie --',
                'choices' => [
                    'Nutrition' => 'Nutrition',
                    'Sport & Fitness' => 'Sport & Fitness',
                    'Santé Mentale' => 'Santé Mentale',
                    'Méditation' => 'Méditation',
                    'Gestion du Stress' => 'Gestion du Stress',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
