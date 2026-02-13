<?php

namespace App\Form;

use App\Entity\JournalEmotionnel;
use App\Enum\EmotionEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


class JournalEmotionnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
             ->add('emotion', ChoiceType::class, [
        'choices' => EmotionEnum::cases(),
        'choice_label' => fn (EmotionEnum $emotion) =>
            ucfirst(strtolower(str_replace('_', ' ', $emotion->name))),
        'choice_value' => fn (?EmotionEnum $emotion) =>
            $emotion?->value,
        'placeholder' => 'Choisissez une émotion',
    ])

            ->add('contenu', TextareaType::class, [
                'required' => false,
                'label' => ''
            ])

            // Image upload (NOT mapped)
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Nouvelle image'
            ])

            // Audio upload (NOT mapped)
            ->add('audioFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Nouvel audio'
            ])
            ->add('removeImage', CheckboxType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Supprimer l’image actuelle',
        ])

        ->add('removeAudio', CheckboxType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Supprimer l’audio actuel',
        ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JournalEmotionnel::class,
        ]);
    }
}
