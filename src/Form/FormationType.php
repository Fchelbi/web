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
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;  // ← Ajoute cette ligne si absente
use Symfony\Component\Validator\Constraints\Url;   // ← Ajoute cette ligne si absente

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Titre (inchangé)
            ->add('title', TextType::class, [
                'label' => 'Titre de la formation',
                'attr'  => [
                    'placeholder' => 'Ex: Nutrition et bien-être',
                    'maxlength'   => 255,
                ],
                'constraints' => [
                    new NotBlank(message: 'Le titre est obligatoire.'),
                    new Length(
                        min: 3, max: 255,
                        minMessage: 'Le titre doit faire au moins {{ limit }} caractères.',
                        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Regex(
                        pattern: '/^[\p{L}0-9\s\'\-\,\.\!\?\:\(\)\/]+$/u',
                        message: 'Le titre contient des caractères non autorisés.'
                    ),
                ],
            ])

            // Description (inchangé)
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Décrivez le contenu de la formation...',
                    'rows'        => 4,
                    'maxlength'   => 2000,
                ],
                'constraints' => [
                    new Length(
                        max: 2000,
                        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            // Catégorie (inchangé)
            ->add('category', ChoiceType::class, [
                'label'       => 'Catégorie',
                'required'    => true,
                'placeholder' => '-- Choisir une catégorie --',
                'choices'     => [
                    'Nutrition'         => 'Nutrition',
                    'Sport & Fitness'   => 'Sport & Fitness',
                    'Santé Mentale'     => 'Santé Mentale',
                    'Méditation'        => 'Méditation',
                    'Gestion du Stress' => 'Gestion du Stress',
                    'Autre'             => 'Autre',
                ],
                'constraints' => [
                    new NotNull(message: 'Veuillez choisir une catégorie.'),
                    new Choice(
                        choices: ['Nutrition','Sport & Fitness','Santé Mentale','Méditation','Gestion du Stress','Autre'],
                        message: 'Catégorie invalide.'
                    ),
                ],
            ])

            // ← ICI : Remplace ton ancien bloc 'videoUrl' par CELUI-CI
            ->add('videoUrl', UrlType::class, [
                'label'    => 'URL de la vidéo (YouTube)',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                    'id'          => 'videoUrlInput',
                ],
                'constraints' => [
                    new Url(message: 'Format d\'URL invalide.'),
                    new Regex(
                        pattern: '/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/',
                        message: 'Veuillez fournir une URL YouTube valide (ex: https://www.youtube.com/watch?v=...).'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Formation::class]);
    }
}