<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'class' => 'w-full h-11 px-3 py-2 border rounded-md'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Votre prénom',
                    'class' => 'w-full h-11 px-3 py-2 border rounded-md'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre prénom',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'votre@email.com',
                    'class' => 'w-full h-11 px-3 py-2 border rounded-md'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre email',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer un email valide',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => '+216 XX XXX XXX',
                    'class' => 'w-full h-11 px-3 py-2 border rounded-md'
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Je suis',
                'mapped' => false,
                'choices' => [
                    'Client' => 'ROLE_CLIENT',
                    'Professionnel de santé' => 'ROLE_PROFESSIONNEL',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'attr' => [
                    'class' => 'w-full h-11 px-3 py-2 border rounded-md'
                ],
                'data' => 'ROLE_CLIENT',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'class' => 'w-full h-11 px-3 py-2 border rounded-md',
                        'autocomplete' => 'new-password'
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'class' => 'w-full h-11 px-3 py-2 border rounded-md',
                        'autocomplete' => 'new-password'
                    ],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre',
                    ]),
                ],
            ])
            
            // Paramètres de confidentialité
            ->add('visibiliteProfil', ChoiceType::class, [
                'label' => 'Visibilité de mon profil',
                'mapped' => false,
                'choices' => [
                    'Privé (visible uniquement par moi)' => 'prive',
                    'Professionnel (visible par les professionnels de santé)' => 'professionnel',
                    'Public (visible par tous les membres)' => 'public',
                ],
                'data' => 'prive',
                'attr' => [
                    'class' => 'w-full h-11 px-3 py-2 border rounded-md'
                ],
                'help' => 'Choisissez qui peut voir votre profil',
            ])
            
            ->add('partageDonnees', CheckboxType::class, [
                'label' => 'J\'autorise le partage anonyme de mes données pour la recherche médicale',
                'mapped' => false,
                'required' => false,
                'data' => false,
                'attr' => [
                    'class' => 'w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary'
                ],
            ])
            
            ->add('notificationsEmail', CheckboxType::class, [
                'label' => 'Je souhaite recevoir des notifications par email',
                'mapped' => false,
                'required' => false,
                'data' => true,
                'attr' => [
                    'class' => 'w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary'
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