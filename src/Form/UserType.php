<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['placeholder' => 'Votre nom'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr'  => ['placeholder' => 'Votre prénom'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['placeholder' => 'exemple@email.com'],
            ])
            ->add('numTel', TelType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'attr'     => ['placeholder' => '12345678'],
            ])
            ->add('role', ChoiceType::class, [
                'label'   => 'Rôle',
                'choices' => [
                    'Patient' => 'Patient',
                    'Coach'   => 'Coach',
                    'Admin'   => 'Admin',
                ],
            ]);
    if (!$isEdit) {
            $builder->add('password', PasswordType::class, [
                'label'       => 'Mot de passe',
                'mapped'      => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le mot de passe est requis']),
                    new Assert\Length([
                        'min'        => 8,
                        'minMessage' => 'Minimum 8 caractères',
                        'max'        => 4096,
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Doit contenir au moins 1 majuscule et 1 chiffre',
                    ]),
                ],
            ]);
        }
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
    }
}