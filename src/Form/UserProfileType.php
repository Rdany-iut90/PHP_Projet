<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserProfileType extends AbstractType
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
                        

                                
                'label' => 'Email',
                

            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
