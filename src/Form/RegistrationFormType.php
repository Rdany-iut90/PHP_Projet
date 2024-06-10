<?php

namespace App\Form;

use App\Entity\User;
use App\Validator\Constraints\PasswordConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer votre nom.']),
                ],
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer votre prénom.']),
                ],
                'label' => 'Prénom',
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer un email.']),
                    new Assert\Email(['message' => 'Veuillez entrer un email valide.']),
                    new Assert\Regex([
                        'pattern' => '/^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/',
                        'message' => 'L\'email doit avoir un format valide : xxx@yyy.zz',
                    ]),
                ],
                'label' => 'Email',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                    ],
                    'label' => 'Mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                ],
                'invalid_message' => 'Les champs du mot de passe doivent correspondre.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
