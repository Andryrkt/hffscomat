<?php

namespace App\Form\Atelier\CVA;

use App\Dto\Atelier\CVA\OrCsaATraiterDto;
use App\Dto\Atelier\CVA\OrCsaATraiterSearchDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrCsaATraiterSearchType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('leadTimeDu', DateType::class, [
                'label'    => 'Lead time du',
                'widget'   => 'single_text',
                'html5'    => false,
                'format'   => 'dd/MM/yyyy',
                'required' => false,
                'attr'     => ['class' => 'datepicker', 'placeholder' => 'jj/mm/aaaa'],
            ])
            ->add('leadTimeAu', DateType::class, [
                'label'    => 'Lead time au',
                'widget'   => 'single_text',
                'html5'    => false,
                'format'   => 'dd/MM/yyyy',
                'required' => false,
                'attr'     => ['class' => 'datepicker', 'placeholder' => 'jj/mm/aaaa'],
            ])
            ->add('client', TextType::class, [
                'label'    => 'Client (N° ou libellé)',
                'required' => false,
            ])
            ->add('referenceClient', TextType::class, [
                'label'    => 'Référence client',
                'required' => false,
            ])
            ->add('fenetreAllocation', ChoiceType::class, [
                'label'    => 'Fenêtre d\'allocation',
                'required' => false,
                'choices'  => [
                    'Tous'                        => 'TOUS',
                    'En retard (< 90 j)'          => 'RETARD',
                    'Autour de J-90 (80 à 100 j)' => 'AUTOUR_J90',
                    'À venir (> 100 j)'           => 'A_VENIR',
                ],
            ])
            ->add('numOr', TextType::class, [
                'label'    => 'N° OR',
                'required' => false,
            ])
            ->add('typeOr', ChoiceType::class, [
                'label'       => 'Type OR',
                'required'    => false,
                'placeholder' => '-- Tous --',
                'choices' => $options['types_or_choices'] ?? [],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'             => OrCsaATraiterSearchDto::class,
            'allAgenceServices' => [],
        ]);
    }
}
