<?php
namespace App\Form;

use App\Entity\Quiz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du quiz',
                'attr'  => ['placeholder' => 'Ex : Quiz diabète de type 2', 'maxlength' => 255],
                'constraints' => [
                    new NotBlank(message: 'Le titre du quiz est obligatoire.'),
                    new Length(min: 3, max: 255,
                        minMessage: 'Au moins {{ limit }} caractères.',
                        maxMessage: 'Maximum {{ limit }} caractères.'
                    ),
                ],
            ])

            // FIX: was duplicated — added ONCE here
            ->add('passing_score', IntegerType::class, [
                'label' => 'Score minimum pour réussir (%)',
                'data'  => 60,   // default value so the field is never empty on load
                'attr'  => ['min' => 0, 'max' => 100],
                'constraints' => [
                    new NotBlank(message: 'Le score minimum est obligatoire.'),
                    // Test logique: a score outside 0-100 is meaningless
                    new Range(min: 0, max: 100,
                        notInRangeMessage: 'Le score doit être entre {{ min }} et {{ max }}%.'
                    ),
                ],
            ])

            ->add('questions', CollectionType::class, [
                'entry_type'   => QuestionType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,   // forces addQuestion/removeQuestion
                'label'        => false,
                'prototype'    => true,
                'constraints'  => [
                    // Test logique: a quiz with 0 questions is useless
                    new Count(min: 1, minMessage: 'Le quiz doit contenir au moins une question.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Quiz::class]);
    }
}