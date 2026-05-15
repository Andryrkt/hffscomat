<?php

namespace App\Form\magasin\devis;

use App\Dto\Magasin\Devis\PointageRelanceDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class PointageRelanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        return $builder
            ->add('numeroDevis', null, [
                'label' => 'Numéro de devis',
                'attr' => [
                    'readonly' => true,
                ]
            ])
            ->add('dateDeRelance', DateType::class, [
                'label' => 'Date de relance *',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d')
                ],
                'constraints' => [
                    new LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date ne peut pas être supérieure à la date du jour.'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PointageRelanceDto::class,
            'csrf_protection' => false,
        ]);
    }
}
