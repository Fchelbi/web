<?php

namespace App\Form;

use App\Entity\Quiz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'      => 'Titre du Quiz',
                'empty_data' => '',
                'attr'       => [
                    'placeholder' => 'Ex: Quiz Nutrition avancée',
                    'class'       => 'form-input',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le titre du quiz est obligatoire.'),
                ],
            ])
            ->add('passingScore', IntegerType::class, [
                'label' => 'Score de passage (%)',
                'attr'  => [
                    'min'         => 0,
                    'max'         => 100,
                    'placeholder' => '60',
                    'class'       => 'form-input',
                ],
                'constraints' => [
                    new Range(
                        min: 0, max: 100,
                        notInRangeMessage: 'Le score doit être entre {{ min }} et {{ max }}.'
                    ),
                ],
            ])
            ->add('questions', CollectionType::class, [
                'entry_type'    => QuestionType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,   // triggers addQuestion / removeQuestion
                'label'         => false,
                'prototype'     => true,
                'attr'          => ['class' => 'questions-collection'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
        ]);
    }
}