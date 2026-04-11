<?php

namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('optionText', TextType::class, [
                'label'       => 'Texte de la réponse',
                'empty_data'  => '',
                'attr'        => [
                    'placeholder' => 'Saisissez une réponse…',
                    'class'       => 'form-input',
                ],
            ])
            ->add('isCorrect', CheckboxType::class, [
                'label'    => 'Réponse correcte',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
        ]);
    }
}