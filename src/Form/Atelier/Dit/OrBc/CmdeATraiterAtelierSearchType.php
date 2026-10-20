<?php

namespace App\Form\Atelier\Dit\OrBc;

use App\Dto\Atelier\OrBc\CmdeATraitrerAtelierSearchDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CmdeATraiterAtelierSearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type_doc', ChoiceType::class, [
                'label' => 'Type de document',
                'placeholder' => '-- Choisir --',
                // 'choices' => $this->getDescriptionWorTypeDocument(),
                'required' => false,
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
            ])
            ->add('designation', TextType::class, [
                'label' => 'Désignation',
                'required' => false,
            ])
            ->add('constructeur', TextType::class, [
                'label' => 'Constructeur',
                'required' => false,
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date début',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date fin',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CmdeATraitrerAtelierSearchDto::class,
        ]);
    }
}
