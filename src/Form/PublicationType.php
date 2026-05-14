<?php

namespace App\Form;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => false,
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'placeholder' => 'Sélectionnez une catégorie',
                'choices' => [
                    'Anxiété' => 'Anxiété',
                    'Dépression' => 'Dépression',
                    'Stress' => 'Stress',
                    'Angoisse' => 'Angoisse',
                    'Addiction' => 'Addiction',
                    'Solitude' => 'Solitude',
                ],
                'attr' => ['class' => 'rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition-all']
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPG, PNG, WEBP)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP, GIF)',
                    ])
                ],
            ])
            ->add('gifUrl', HiddenType::class, [
                'required' => false,
            ])
        ;

        if ($options['include_pinned']) {
            $builder->add('isPinned', CheckboxType::class, [
                'label' => 'Épingler en haut de la liste',
                'mapped' => false,
                'required' => false,
                'data' => $options['data']->getPinnedAt() !== null,
                'attr' => ['class' => 'rounded text-blue-600 focus:ring-blue-500']
            ]);
        }
        // 🔥 ON NE MET PAS datePublication et user dans le formulaire
        // Ils seront définis automatiquement dans le controller
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
            'include_pinned' => true,
        ]);
    }
}