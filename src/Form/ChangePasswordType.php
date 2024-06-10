<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                        new Assert\Length(['min' => 8, 'minMessage' => 'Le mot de passe doit être d\'au moins 8 caractères.']),
                        new Assert\Regex(['pattern' => '/[a-zA-Z]/', 'message' => 'Le mot de passe doit contenir des lettres.']),
                        new Assert\Regex(['pattern' => '/[0-9]/', 'message' => 'Le mot de passe doit contenir des chiffres.']),
                    ],
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                ],
                'invalid_message' => 'Les champs du mot de passe doivent correspondre.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
