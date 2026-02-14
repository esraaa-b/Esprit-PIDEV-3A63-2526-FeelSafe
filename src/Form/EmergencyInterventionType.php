<?php

namespace App\Form;

use App\Entity\Urgence;
use App\Entity\Intervention;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EmergencyInterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $urgence = $options['edit_urgence'] ?? null;
        $intervention = $options['edit_intervention'] ?? null;
        $formType = $options['form_type'] ?? 'emergency'; // emergency or intervention

        if ($formType === 'emergency') {
            // EMERGENCY FORM
            $builder
                ->add('emergencyType', TextType::class, [
                    'label' => 'Emergency Type',
                    'required' => true,
                    'mapped' => false,
                    'data' => $urgence ? $urgence->getTypeUrgence() : null,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('emergencyDescription', TextType::class, [
                    'label' => 'Emergency Description',
                    'required' => true,
                    'mapped' => false,
                    'data' => $urgence ? $urgence->getDescription() : null,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('severityLevel', NumberType::class, [
                    'label' => 'Severity Level (1-5)',
                    'required' => true,
                    'mapped' => false,
                    'data' => $urgence ? $urgence->getSeverityLevel() : null,
                    'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 5]
                ])
                ->add('emergencyStatus', ChoiceType::class, [
                    'label' => 'Emergency Status',
                    'required' => true,
                    'mapped' => false,
                    'data' => $urgence ? $urgence->getStatus() : null,
                    'choices' => [
                        'Pending' => 'Pending',
                        'In Progress' => 'In Progress',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed'
                    ],
                    'attr' => ['class' => 'form-control']
                ])
                ->add('location', TextType::class, [
                    'label' => 'Location',
                    'required' => true,
                    'mapped' => false,
                    'data' => $urgence ? $urgence->getLocation() : null,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('save', SubmitType::class, [
                    'label' => $urgence ? 'Update Emergency' : 'Create Emergency',
                    'attr' => ['class' => 'btn btn-primary']
                ]);
        } else {
            // INTERVENTION FORM
            $builder
                ->add('interventionType', TextType::class, [
                    'label' => 'Intervention Type',
                    'required' => true,
                    'mapped' => false,
                    'data' => $intervention ? $intervention->getInterventionType() : null,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('interventionNotes', TextType::class, [
                    'label' => 'Intervention Notes',
                    'required' => false,
                    'mapped' => false,
                    'data' => $intervention ? $intervention->getNotes() : null,
                    'attr' => ['class' => 'form-control']
                ])
                ->add('result', ChoiceType::class, [
                    'label' => 'Result',
                    'required' => true,
                    'mapped' => false,
                    'data' => $intervention ? $intervention->getResult() : null,
                    'choices' => [
                        'Success' => 'Success',
                        'Partial Success' => 'Partial Success',
                        'Failed' => 'Failed',
                        'Pending' => 'Pending'
                    ],
                    'attr' => ['class' => 'form-control']
                ])
                ->add('interventionDate', DateType::class, [
                    'label' => 'Intervention Date',
                    'widget' => 'single_text',
                    'required' => true,
                    'mapped' => false,
                    'data' => $intervention ? $intervention->getInterventionDate() : null,
                    'attr' => ['class' => 'form-control']
                ]);

            // Add emergency selection ONLY for creating new interventions (not editing)
            if (!$intervention) {
                $builder->add('urgence', EntityType::class, [
                    'label' => 'Select Emergency',
                    'class' => Urgence::class,
                    'choice_label' => function (Urgence $urgence) {
                        return sprintf(
                            '#%d - %s - %s',
                            $urgence->getId(),
                            $urgence->getTypeUrgence(),
                            $urgence->getLocation()
                        );
                    },
                    'required' => true,
                    'attr' => ['class' => 'form-control']
                ]);
            }

            $builder->add('save', SubmitType::class, [
                'label' => $intervention ? 'Update Intervention' : 'Create Intervention',
                'attr' => ['class' => 'btn btn-primary']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'edit_urgence' => null,
            'edit_intervention' => null,
            'form_type' => 'emergency', // Default to emergency form
        ]);
    }
}