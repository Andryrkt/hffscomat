<?php

namespace App\Form\magasin\OrBc;

use App\Dto\Magasin\OrBc\CmdeATraitrerMagasinSearchDto;
use App\Model\magasin\OrBc\CmdeATraiterMagasinModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CmdeATraiterMagasinSearchType extends AbstractType
{
    private CmdeATraiterMagasinModel $cmdeAtraiterAtelierModel;

    public function __construct(CmdeATraiterMagasinModel $cmdeAtraiterAtelierModel)
    {
        $this->cmdeAtraiterAtelierModel = $cmdeAtraiterAtelierModel;
    }

    private function constructeurChoices(): array
    {
        $constructeurs = $this->cmdeAtraiterAtelierModel->findConstructeurs();

        // Fallback si la requête ne renvoie rien (ex: base de dev vide)
        if (empty($constructeurs)) {
            $constructeurs = [];
        }

        return array_combine($constructeurs, $constructeurs);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDebut', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Période Du',
                'required' => false,
                'data'     => $options['data']->dateDebut ?? new \DateTime('-30 days'),

            ])
            ->add('dateFin', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Au période de',
                'required' => false,
                'data'     => $options['data']->dateFin ?? new \DateTime(),
            ])
            ->add('type_doc', ChoiceType::class, [
                'label'       => 'Type',
                'placeholder' => '-- Tous --',
                'choices'     => ['OR' => 'OR', 'BC' => 'BC négoce'],
                'required'    => false,
            ])
            ->add('constructeur', ChoiceType::class, [
                'label'       => 'Constructeur',
                'placeholder' => '-- Tous --',
                'choices'     =>  $this->constructeurChoices(),
                'required'    => false,
            ])
            ->add('reference', TextType::class, [
                'label'    => 'Ref ou Désignation',
                'required' => false,
            ])
            ->add('numero_doc', TextType::class, [
                'label'    => 'N° OR/BC',
                'required' => false,
            ])



        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CmdeATraitrerMagasinSearchDto::class,
        ]);
    }
}
