<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LengthValidator;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminLoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                "mapped" => false,
                "constraints" => [
                    new NotBlank(message: "Type something"),
                    new Email(message: "A actual email"),
                ],
            ])
            ->add('password', null, [
                "mapped" => false,
                "constraints" => [
                    new NotBlank(message: "Type something"),
                ]
            ])
            ->add('username', null, [
                "mapped" => false,
                "constraints" => [
                    new NotBlank(message: "Type something"),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
