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
// App\Form\JournalEmotionnelType.php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    /** @var JournalEmotionnel $journal */
    $journal = $options['journal'];

    $builder
        ->add('emotion', ChoiceType::class, [
            'choices' => EmotionEnum::cases(),
            'choice_label' => fn (EmotionEnum $emotion) => $emotion->label(),
            'choice_value' => fn (?EmotionEnum $emotion) => $emotion?->value,
            'expanded' => true,        // ← crucial pour avoir des radios
            'multiple' => false,
            'placeholder' => false,
            'label' => false,          // ← on cache le label
            'attr' => [
                'class' => 'emotion-radio-hidden',  // ← pour cacher les radios
            ],
        ])
        ->add('contenu', TextareaType::class, [
            'required' => false,
        ])
        ->add('imageFile', FileType::class, [
            'mapped' => false,
            'required' => false,
        ])
        ->add('audioFile', FileType::class, [
            'mapped' => false,
            'required' => false,
        ]);

    if ($journal && $journal->getImage()) {
        $builder->add('removeImage', CheckboxType::class, [
            'mapped' => false,
            'required' => false,
        ]);
    }

    if ($journal && $journal->getAudio()) {
        $builder->add('removeAudio', CheckboxType::class, [
            'mapped' => false,
            'required' => false,
        ]);
    }
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JournalEmotionnel::class,
            'journal' => null, // 👈 custom option
        ]);
    }
}
