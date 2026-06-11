<?php

namespace App\Form\Atelier\Planning;

use App\Controller\Traits\Transformation;
use App\Dto\Atelier\Planning\PlanningAtelierSearchDto;
use App\Model\Atelier\Planning\PlanningAtelierModel;
use App\Model\Atelier\Planning\PlanningModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningAtelierSearchType extends AbstractType
{
    use Transformation;
    private PlanningAtelierModel $atelierModel;
    private PlanningModel $model;

    // L'injection de dépendances est obligatoire ici
    public function __construct()
    {
        $this->model = new PlanningModel();
        $this->atelierModel = new PlanningAtelierModel();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $section = $this->atelierModel->getSection('HF');
        $section = $this->transformeValeur($section, 'section', 'num');
        $ressource = $this->atelierModel->getResource('HF');
        $ressource = $this->transformEnSeulTableau($ressource);
        $agence = $this->model->getAgenceIrium();
        $agence = $this->transformEnSeulTableauAvecKey($agence);
        $agenceDebite = $this->model->getAgenceDebite();

        $builder
            ->add('numeroSemaine', ChoiceType::class, [
                'choices' => array_combine(range(1, 53), range(1, 53)),
                'label' => 'Numéro de semaine',
                'placeholder' => '-- Choisir une semaine --',
                'data' => (int) date('W'),
                'required' => false
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date Début',
                'required' => false,
                'input'  => 'datetime_immutable',
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date Fin',
                'required' => false,
                'input'  => 'datetime_immutable',
            ])
            ->add('numeroOr', TextType::class, [
                'label' => 'N° OR',
                'required' => false,
            ])
            ->add('ressource', ChoiceType::class, [
                'label' => 'Ressource',
                'choices' => array_combine($ressource, $ressource),
                'placeholder' => '-- Choisir une ressource --',
                'required' => false
            ])
            ->add('section', ChoiceType::class, [
                'label' => 'Section',
                'choices' => array_combine($section, $section),
                'placeholder' => '-- Choisir une section --',
                'required' => false // Ajouté pour correspondre à ton DTO nullable
            ])
            ->add('agenceEm', ChoiceType::class, [
                'label' =>  'Agence Travaux',
                'required' => false,
                'choices' => $agence,
                'placeholder' => ' -- Choisir une agence --',
            ])
            ->add('agenceDeb', ChoiceType::class, [
                'label' => 'Agence Débiteur',
                'required' => false,
                'choices' => $agenceDebite,
                'placeholder' => " -- Choisir une agence --",
            ])
            ->add('serviceDeb', ChoiceType::class, [
                'label' => 'Service Débiteur',
                'multiple' => true,
                'choices' => [],
                'placeholder' => " -- Choisir un service --",
                'expanded' => true,
                'required' => false
            ]);

        // Correction de la logique de formulaire dynamique
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $serviceDebite = [];

            if (isset($data['agenceDeb'])) {
                $serviceDebite = $this->model->getServiceDebiteByAgence($data['agenceDeb']);
                $serviceDebite = $this->transformEnSeulTableauAvecKeyService($serviceDebite);
            }
            $form->add('serviceDeb', ChoiceType::class, [
                'label' => 'Service Débiteur : ',
                'multiple' => true,
                'choices' => $serviceDebite,
                'placeholder' => " -- choisir service --",
                'expanded' => true,
                'required' => false
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PlanningAtelierSearchDto::class
        ]);
    }
}