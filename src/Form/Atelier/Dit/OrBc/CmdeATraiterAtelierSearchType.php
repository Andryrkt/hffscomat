<?php

namespace App\Form\Atelier\Dit\OrBc;

use App\Dto\Atelier\OrBc\CmdeATraitrerAtelierSearchDto;
use App\Model\Atelier\OrBc\CmdeATraiterAtelierModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CmdeATraiterAtelierSearchType extends AbstractType
{
    // private function constructeurChoices(): array
    // {
    //     $model        = new CmdeATraiterAtelierModel();
    //     $constructeurs = $model->findConstructeurs();

    //     // Fallback si la requête ne renvoie rien : on garde au moins les 2 connus
    //     if (empty($constructeurs)) {
    //         $constructeurs = ['ATC', 'CAT'];
    //     }

    //     return array_combine($constructeurs, $constructeurs);
    // }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type_doc', ChoiceType::class, [
                'label'       => 'Type',
                'placeholder' => '-- Tous --',
                'choices'     => ['OR' => 'OR', 'BC' => 'BC négoce'],
                'required'    => false,
            ])
            ->add('reference', TextType::class, [
                'label'    => 'N°, Reférence ou Désignation',
                'required' => false,
            ])

            ->add('constructeur', ChoiceType::class, [
                'label'       => 'Constructeur',
                'placeholder' => '-- Tous --',
                'choices'     =>  ['ATC' => 'ATC', 'CAT' => 'CAT'],
                'required'    => false,
            ])
            ->add('dateDebut', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Du',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Au',
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
