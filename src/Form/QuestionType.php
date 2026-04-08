<?php
namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // FIX: was 'question_text' (snake_case) — must be 'questionText' (camelCase)
            // Symfony maps 'questionText' → getQuestionText() / setQuestionText()
            ->add('questionText', TextareaType::class, [
                'label' => 'Énoncé de la question',
                'attr'  => [
                    'rows'        => 2,
                    'placeholder' => 'Ex : Quel est le rôle de l\'insuline ?',
                    'maxlength'   => 1000,
                ],
                'constraints' => [
                    new NotBlank(message: 'L\'énoncé de la question est obligatoire.'),
                    new Length(min: 5, max: 1000,
                        minMessage: 'La question doit faire au moins {{ limit }} caractères.',
                        maxMessage: 'La question ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('points', IntegerType::class, [
                'label' => 'Points',
                'data'  => 1,
                'attr'  => ['min' => 1, 'max' => 100],
                'constraints' => [
                    new NotBlank(message: 'Les points sont obligatoires.'),
                    // Test logique: 0 or negative points make no sense
                    new Range(min: 1, max: 100,
                        notInRangeMessage: 'Les points doivent être entre {{ min }} et {{ max }}.'
                    ),
                ],
            ])

            ->add('reponses', CollectionType::class, [
                'entry_type'   => ReponseType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,   // forces addReponse/removeReponse
                'label'        => false,
                'prototype'    => true,
                'constraints'  => [
                    // Test logique: a question needs at least 2 choices
                    new Count(min: 2, minMessage: 'Chaque question doit avoir au moins 2 réponses.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Question::class]);
    }
}