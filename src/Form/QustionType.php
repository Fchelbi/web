<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('questionText', TextareaType::class, [
                'label'      => 'Question',
                'empty_data' => '',
                'attr'       => [
                    'placeholder' => 'Écrivez votre question ici…',
                    'rows'        => 2,
                    'class'       => 'form-input',
                ],
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Points',
                'data'  => 1,
                'attr'  => [
                    'min'   => 1,
                    'max'   => 10,
                    'class' => 'form-input',
                ],
            ])
            ->add('reponses', CollectionType::class, [
                'entry_type'    => ReponseType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,   // triggers addReponse / removeReponse
                'label'         => false,
                'prototype'     => true,
                'attr'          => ['class' => 'reponses-collection'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}