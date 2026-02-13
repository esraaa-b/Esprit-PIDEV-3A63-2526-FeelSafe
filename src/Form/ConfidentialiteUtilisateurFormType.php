<?php

namespace App\Form;

use App\Entity\ConfidentialiteUtilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfidentialiteUtilisateurFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('partageDonnees', CheckboxType::class, [
                'label' => 'Autoriser le partage de mes données anonymisées pour la recherche',
                'required' => false,
                'attr' => [
                    'class' => 'w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary'
                ],
            ])
            ->add('notificationsEmail', CheckboxType::class, [
                'label' => 'Recevoir des notifications par email',
                'required' => false,
                'attr' => [
                    'class' => 'w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary'
                ],
            ])
            ->add('visibiliteProfil', ChoiceType::class, [
                'label' => 'Visibilité de mon profil',
                'choices' => [
                    'Privé (visible uniquement par moi)' => 'prive',
                    'Professionnel (visible par les professionnels)' => 'professionnel',
                    'Public (visible par tous)' => 'public',
                ],
                'attr' => [
                    'class' => 'w-full h-11 px-3 py-2 border rounded-md'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfidentialiteUtilisateur::class,
        ]);
    }
}
