<?php

namespace App\Form\Atelier\Planning;

use App\Controller\Traits\Transformation;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Model\Atelier\Dit\WorTypeDocumentModel;
use App\Model\Atelier\Planning\PlanningModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningSearchType extends AbstractType
{
    use Transformation;

    private PlanningModel $planningModel;
    private WorTypeDocumentModel $worTypeDocumentModel;
    private WorNiveauUrgenceModel $worNiveauUrgenceModel;

    const INTERNE_EXTERNE = [
        'TOUS'          => 'TOUS',
        'INTERNE'       => 'INTERNE',
        'EXTERNE'       => 'EXTERNE'
    ];
    const FACTURE = [
        'TOUS'          => 'TOUS',
        'DEJA FACTURE'  => 'FACTURE',
        'ENCOURS'       => 'ENCOURS'
    ];
    const PLANIFIER = [
        'PLANIFIE'     => 'PLANIFIE',
        'NON PLANIFIE' => 'NON_PLANIFIE',
    ];
    const TYPELIGNE = [
        'TOUTES'         => 'TOUTES',
        'PIECES MAGASIN' => 'PIECES_MAGASIN',
        'ACHATS LOCAUX'  => 'ACHAT_LOCAUX',
        'LUBRIFIANTS'    => 'LUBRIFIANTS',
        'PNEUMATIQUES'   => 'PNEUMATIQUES',
    ];
    
    const REPARATION_REALISE = [
        "WS SCOMAT" => "WS SCOMAT",
        "WS AGRI TRUCK" => "WS AGRI TRUCK",
        "WS MACHINE" => "WS MACHINE",
        "WS PSSR" => "WS PSSR",
        "WS UPS" => "WS UPS",
    ];

    public function __construct()
    {
        $this->planningModel = new PlanningModel();
        $this->worTypeDocumentModel = new WorTypeDocumentModel();
        $this->worNiveauUrgenceModel = new WorNiveauUrgenceModel();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $codeSociete = $options['codeSociete'];
        $agence = $this->transformEnSeulTableauAvecKey($this->planningModel->getAgences($codeSociete));
        $agenceDebite = $this->planningModel->getAgenceDebite($codeSociete);
        $sections = $this->planningModel->getSections();
        $documents = $this->getTypeDocuments();
        $urgences = $this->getNiveauUrgences();

        $builder
            ->add('agence', ChoiceType::class, [
                'label' => 'Agence Travaux',
                'required' => false,
                'choices' => $agence,
                'placeholder' => ' -- Choisir une agence --',
            ])
            ->add('niveauUrgence', ChoiceType::class, [
                'label' => 'Niveau d\'urgence',
                'required' => false,
                'choices' => $urgences,
                'placeholder' => "-- Choisir un niveau --",
            ])
            ->add('interneExterne', ChoiceType::class, [
                'label' => 'Interne / Externe',
                'required' => true,
                'choices' => self::INTERNE_EXTERNE,
                'attr' => ['class' => 'interneExterne'],
            ])
            ->add('typeLigne', ChoiceType::class, [
                'label' => 'Type de ligne',
                'required' => False,
                'choices' => self::TYPELIGNE,
                'attr' => ['class' => 'typeligne'],
                'data' => 'TOUTES',
                'placeholder' => False
            ])
            ->add('facture', ChoiceType::class, [
                'label' => 'Facturation',
                'required' => true,
                'choices' => self::FACTURE,
                'attr' => ['class' => 'facture'],
                'data' => 'TOUS'
            ])
            ->add('planning', ChoiceType::class, [
                'label' => 'Planification',
                'required' => true,
                'choices' => self::PLANIFIER,
                'attr' => ['class' => 'plan'],
                'data' => 'PLANIFIE'
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => $options['planningDetaille'] ? 'Date Début Planning' : 'Date Début',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => $options['planningDetaille'] ? 'Date Fin Planning' : 'Date Fin',
                'required' => false,
            ])
            ->add('numOr', TextType::class, [
                'label' => "N° OR",
                'required' => false
            ])
            ->add('numSerie', TextType::class, [
                'label' => "N° Série",
                'required' => false
            ])
            ->add('idMat', TextType::class, [
                'label' => "Id Matériel",
                'required' => false
            ])
            ->add('numParc', TextType::class, [
                'label' => "N° Parc",
                'required' => false
            ])
            ->add('casier', TextType::class, [
                'label' => "Casier",
                'required' => false
            ])
            ->add('agenceDebite', ChoiceType::class, [
                'label' => 'Agence Débiteur',
                'required' => false,
                'choices' => $agenceDebite,
                'placeholder' => " -- Choisir une agence --",

            ])
            ->add(
                'section',
                ChoiceType::class,
                [
                    'label' => 'Section',
                    'required' => false,
                    'choices' => $sections,
                    'placeholder' => "-- Choisir une section --"
                ]

            )
            ->add(
                'orBackOrder',
                CheckboxType::class,
                [
                    'label' => 'OR avec Back Order',
                    'required' => false
                ]
            )
            ->add('serviceDebite', ChoiceType::class, [
                'label' => 'Service Débiteur',
                'multiple' => true,
                'choices' => [],
                'placeholder' => " -- Choisir un service--",
                'expanded' => true,
            ])
            ->add('typeDocument', ChoiceType::class, [
                'label' => 'Type de document',
                'required' => false,
                'choices' => $documents,
                'placeholder' => '-- Choisir un type de document --',
            ])
            ->add(
                'reparationRealise',
                ChoiceType::class,
                [
                    'label' => "Réparation réalisé par *",
                    'choices' => self::REPARATION_REALISE,
                    'placeholder' => '-- Choisir le répartion réalisé --',
                    'required' => false,

                ]
            )
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                $serviceDebite = [];
                if (!empty($data['agenceDebite'])) {
                    $serviceDebite = $this->transformEnSeulTableauAvecKeyService($this->planningModel->getServiceDebiteByAgence($data['agenceDebite']));
                }

                $form->add('serviceDebite', ChoiceType::class, [
                    'label' => 'Service Débiteur : ',
                    'multiple' => true,
                    'choices' => $serviceDebite,
                    'placeholder' => " -- choisir service--",
                    'expanded' => true,
                ]);
            })
            ->add('months', ChoiceType::class, [
                'choices' => [
                    '3 mois suivant'    => 3,
                    '6 mois suivant'    => 6,
                    '12 mois suivant'   => 12,
                    '12 mois précédent' => 13,
                    'Année encours'     => 9,
                    'Année suivante'    => 11,
                    'Année précédente'  => 14,
                ],
                'expanded' => false, // Utiliser une liste déroulante
                'multiple' => false, // Sélectionner une seule valeur
                'label'    => 'Nombre de mois',
                'data'     => 3
            ])
            ->add(
                'orNonValiderDw',
                CheckboxType::class,
                [
                    'label' => 'OR non valider DW',
                    'required' => false
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PlanningSearchDto::class,
            'planningDetaille' => false
        ]);

        $resolver->setRequired('codeSociete');
        $resolver->setAllowedTypes('codeSociete', 'string');
    }

    private function getTypeDocuments(): array
    {
        $descriptions = $this->worTypeDocumentModel->getDescription();
        $docs = array_map(function ($description) {
            return [$description => $this->worTypeDocumentModel->getIdSelonDescription($description)];
        }, $descriptions);

        return $this->transformEnSeulTableauAvecKey($docs);
    }

    private function getNiveauUrgences(): array
    {
        $niveauUrgences = $this->worNiveauUrgenceModel->getDescription();
        $urgences = array_map(function ($niveauUrgence) {
            return [$niveauUrgence => $this->worNiveauUrgenceModel->getIdSelonDescription($niveauUrgence)];
        }, $niveauUrgences);

        return $this->transformEnSeulTableauAvecKey($urgences);
    }
}
