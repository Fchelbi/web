<?php
namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // FIX: was 'option_text' (snake_case) — must be 'optionText' (camelCase)
            // Symfony maps 'optionText' → getOptionText() / setOptionText()
            ->add('optionText', TextType::class, [
                'label' => 'Texte de la réponse',
                'attr'  => [
                    'placeholder' => 'Saisir une réponse…',
                    'maxlength'   => 255,
                ],
                'constraints' => [
                    new NotBlank(message: 'Le texte de la réponse est obligatoire.'),
                    new Length(max: 255,
                        maxMessage: 'La réponse ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            // FIX: was 'is_correct' (snake_case) — must be 'isCorrect' (camelCase)
            ->add('isCorrect', CheckboxType::class, [
                'label'    => 'Correcte ?',
                'required' => false,  // unchecked = false, which is valid
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Reponse::class]);
    }
}