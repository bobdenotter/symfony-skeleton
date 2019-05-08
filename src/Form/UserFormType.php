<?php

declare(strict_types=1);

namespace Bolt\Form;

use Bolt\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'label.username',
                'disabled' => true,
            ])
            ->add('displayName', TextType::class, [
                'label' => 'label.display_name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'label.email',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'user.not_valid_password',
                'required' => false,
                'first_options' => [
                    'label' => 'Password',
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
            ->add('locale', ChoiceType::class, [
                'label' => 'label.locale',
                'choices' => [
                    'English (en)' => 'en',
                    'Nederlands (dutch, nl)' => 'nl',
                    'Español (Spanish, es)' => 'es',
                    'français (French, fr)' => 'fr',
                    'Deutsch (German, de)' => 'de',
                    'Polski (Polish, pl)' => 'pl',
                    'Brasilian Portuguese (Brasilian Portuguese, pt_BR)' => 'pt_BR',
                    'Italiano (Italian, it)' => 'it',
                ],
            ])
            ->add('backendTheme', ChoiceType::class, [
                'label' => 'label.backend_theme',
                'choices' => [
                    'Default Theme' => 'default',
                    'Dark Theme' => 'dark',
                    'Light Theme' => 'light',
                    'WoordPers: Kinda looks like that other CMS' => 'woordpers',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
