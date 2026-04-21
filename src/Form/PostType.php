<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('content', null, [
                'attr' => ['rows' => 5]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Post Photo',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*'
                ]
            ])
            ->add('category', null, [
                'choice_label' => 'name',
                'placeholder' => 'Select a category'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
