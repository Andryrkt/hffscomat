<?php

namespace App\Form\Atelier\Dit;

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Dto\Atelier\Dit\DitSearchDto;
use App\Entity\admin\StatutDemande;
use App\Model\admin\StatutDemande\StatutDemandeModel;
use App\Model\Atelier\Dit\CategorieAteAppModel;
use App\Model\Atelier\Dit\DitListeModel;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Model\Atelier\Dit\WorTypeDocumentModel;
use App\Repository\admin\StatutDemandeRepository;
use App\Traits\PrepareAgenceServiceTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DitSearchType extends AbstractType
{
    use PrepareAgenceServiceTrait;
    const INTERNE_EXTERNE = [
        'INTERNE' => 'INTERNE',
        'EXTERNE' => 'EXTERNE'
    ];

    const ETAT_FACTURE = [
        'Complètement facturé'     => 'Complètement facturé',
        'Partiellement facturé'    => 'Partiellement facturé',
        'A valider client interne' => 'A valider client interne'
    ];

    const REPARATION_REALISE = [
        'ATE TANA'     => 'ATE TANA',
        'ATE POL TANA' => 'ATE POL TANA',
        'ATE STAR'     => 'ATE STAR',
        'ATE MAS'      => 'ATE MAS',
        'ATE TMV'      => 'ATE TMV',
        'ATE FTU'      => 'ATE FTU',
        'ATE ABV'      => 'ATE ABV',
        'ATE LEV'      => 'ATE LEV',
        'ENERGIE MAN'  => 'ENERGIE MAN'
    ];

    private DitListeModel $ditListeModel;

    public function __construct(DitListeModel $ditListeModel)
    {
        $this->ditListeModel = $ditListeModel;
    }


    private function sectionAffectee()
    {
        $sectionAffecte = $this->ditListeModel->findSectionAffectee();
        $groupes = ['Chef section', 'Chef de section', 'Responsable section', 'Chef d\'équipe']; // Les groupes de mots à supprimer
        $sectionAffectee = str_replace($groupes, "", $sectionAffecte);
        return array_combine($sectionAffectee, $sectionAffectee);
    }

    private function sectionSupport1()
    {
        $sectionSupport1 = $this->ditListeModel->findSectionSupport1();
        $groupes = ['Chef section', 'Chef de section', 'Responsable section', 'Chef d\'équipe']; // Les groupes de mots à supprimer
        $sectionSupport1 = str_replace($groupes, "", $sectionSupport1);
        return array_combine($sectionSupport1, $sectionSupport1);
    }

    private function sectionSupport2()
    {
        $sectionSupport2 = $this->ditListeModel->findSectionSupport2();
        $groupes = ['Chef section', 'Chef de section', 'Responsable section', 'Chef d\'équipe']; // Les groupes de mots à supprimer
        $sectionSupport2 = str_replace($groupes, "", $sectionSupport2);
        return array_combine($sectionSupport2, $sectionSupport2);
    }

    private function sectionSupport3()
    {
        $sectionSupport3 = $this->ditListeModel->findSectionSupport3();
        $groupes = ['Chef section', 'Chef de section', 'Responsable section', 'Chef d\'équipe']; // Les groupes de mots à supprimer
        $sectionSupport3 = str_replace($groupes, "", $sectionSupport3);
        return array_combine($sectionSupport3, $sectionSupport3);
    }


    public function getDesctionCategorieAteApp()
    {
        $categorieAteAppModel = new CategorieAteAppModel();
        $descriptions = $categorieAteAppModel->getDescription();
        return array_combine($descriptions, $descriptions);
    }

    public function getDescriptionWorNiveauUrgence()
    {
        $worNiveauUrgenceModel = new WorNiveauUrgenceModel();
        $descriptions = $worNiveauUrgenceModel->getDescription();
        return array_combine($descriptions, $descriptions);
    }

    public function getDescriptionWorTypeDocument()
    {
        $worTypeDocumentModel = new WorTypeDocumentModel();
        $descriptions = $worTypeDocumentModel->getDescription();
        return array_combine($descriptions, $descriptions);
    }

    public function getDescrionStatutDemande()
    {
        $statutDemande = new StatutDemandeModel();
        $descriptions = $statutDemande->getAllDescriptionStatutDit();
        return array_combine($descriptions, $descriptions);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'niveauUrgence',
                ChoiceType::class,
                [
                    'label' => 'Niveau d\'urgence',
                    'label_html' => true,
                    'placeholder' => "-- Choisir un Niv d'urgence --",
                    'choices' => $this->getDescriptionWorNiveauUrgence(),
                    'required' => false,
                ]
            )
            ->add(
                'statut',
                ChoiceType::class,
                [
                    'label'         => 'Statut',
                    'choices' => $this->getDescrionStatutDemande(),
                    'placeholder'   => '-- Choisir un statut --',
                    'required'      => false,

                ]
            )
            ->add('idMateriel', NumberType::class, [
                'label'         => 'Id Matériel',
                'required'      => false,
            ])
            ->add(
                'typeDocument',
                ChoiceType::class,
                [
                    'label' => 'Type de document',
                    'placeholder' => '-- Choisir--',
                    'choices' => $this->getDescriptionWorTypeDocument(),
                    'required' => false
                ]
            )
            ->add(
                'internetExterne',
                ChoiceType::class,
                [
                    'label'         => "Interne - Externe",
                    'choices'       => self::INTERNE_EXTERNE,
                    'placeholder'   => '-- Choisir --',
                    'required'      => false,
                    'attr'          => ['class' => 'interneExterne']
                ]
            )
            ->add('dateDebut', DateType::class, [
                'widget'        => 'single_text',
                'label'         => 'Date début demande',
                'required'      => false,
            ])
            ->add('dateFin', DateType::class, [
                'widget'        => 'single_text',
                'label'         => 'Date fin demande',
                'required'      => false,
            ])
            ->add('numParc', TextType::class, [
                'label'         => "N° Parc",
                'required'      => false
            ])
            ->add('numSerie', TextType::class, [
                'label'         => "N° Série",
                'required'      => false
            ])

            ->add(
                'numDit',
                TextType::class,
                [
                    'label' => 'N° DIT',
                    'required' => false
                ]
            )
            ->add(
                'numOr',
                NumberType::class,
                [
                    'label' => 'N° OR',
                    'required' => false
                ]
            )
            ->add(
                'statutOr',
                ChoiceType::class,
                [
                    'label' => 'Statut OR',
                    'required' => false,
                    'choices' => ConstantStatutOr::STATUT_OR,
                    'placeholder' => '-- choisir une statut --'
                ]
            )
            ->add(
                'ditSansOr',
                CheckboxType::class,
                [
                    'label' => 'DIT sans OR',
                    'required' => false
                ]
            )

            ->add(
                'categorie',
                ChoiceType::class,
                [
                    'label' => 'Catégorie de demande *',
                    'placeholder' => '-- Choisir une catégorie --',
                    'choices' => $this->getDesctionCategorieAteApp(),
                    'required' => false,
                ]
            )
            ->add(
                'utilisateur',
                TextType::class,
                [
                    'label' => 'Utilisateur',
                    'required' => false
                ]
            )
            ->add(
                'sectionAffectee',
                ChoiceType::class,
                [
                    'label' => 'Section affectée',
                    'required' => false,
                    'choices' => $this->sectionAffectee(),
                    'placeholder' => '-- choisir une section --'
                ]
            )
            ->add(
                'sectionSupport1',
                ChoiceType::class,
                [
                    'label' => 'Section support 1',
                    'placeholder' => '-- choisir une section --',
                    'required' => false,
                    'choices' => $this->sectionSupport1(),

                ]
            )
            ->add(
                'sectionSupport2',
                ChoiceType::class,
                [
                    'label' => 'Section support 2',
                    'placeholder' => '-- choisir une section --',
                    'required' => false,
                    'choices' => $this->sectionSupport2(),

                ]
            )
            ->add(
                'sectionSupport3',
                ChoiceType::class,
                [
                    'label' => 'Section support 3',
                    'placeholder' => '-- choisir une section --',
                    'required' => false,
                    'choices' => $this->sectionSupport3(),

                ]
            )
            ->add(
                'etatFacture',
                ChoiceType::class,
                [
                    'label' => "Statut facture",
                    'choices' => self::ETAT_FACTURE,
                    'placeholder' => '-- Choisir --',
                    'required' => false,
                ]
            )
            ->add(
                'numDevis',
                TextType::class,
                [
                    'label' => 'N° devis',
                    'required' => false
                ]
            )
            ->add(
                'reparationRealise',
                ChoiceType::class,
                [
                    'label' => "Réalisé par",
                    'choices' => self::REPARATION_REALISE,
                    'placeholder' => '-- Choisir le répartion réalisé --',
                    'required' => false,
                ]
            )
        ;

        $choices = $this->prepareAgenceServiceChoices($options['allAgenceServices']);

        $agenceChoices = $choices['agenceChoices'];
        $serviceChoices = $choices['serviceChoices'];
        $serviceAttr = $choices['serviceAttr'];

        $builder
            // --- agenceEmetteur : ChoiceType ---
            ->add('agenceEmetteur', ChoiceType::class, [
                'label'       => 'Agence émetteur',
                'placeholder' => '-- Choisir une agence --',
                'required'    => false,
                'choices'     => $agenceChoices
            ])
            // --- serviceEmetteur : ChoiceType ---
            ->add('serviceEmetteur', ChoiceType::class, [
                'label'       => 'Service émetteur',
                'placeholder' => '-- Choisir une service --',
                'required'    => false,
                'choices'     => $serviceChoices,
                'choice_label' => function ($value) use ($options) {
                    // Retrouver le bon item et afficher service_code . ' ' . service_libelle
                    $item = $options['allAgenceServices'][$value];
                    return $item['service_code'] . ' ' . $item['service_libelle'];
                },
                'choice_attr' => function ($val) use ($serviceAttr) {
                    return $serviceAttr[$val] ?? [];
                }
            ])
            // --- agenceDebiteur : ChoiceType ---
            ->add('agenceDebiteur', ChoiceType::class, [
                'label'       => 'Agence débiteur',
                'placeholder' => '-- Choisir une agence --',
                'required'    => false,
                'choices'     => $agenceChoices
            ])
            // --- serviceDebiteur : ChoiceType ---
            ->add('serviceDebiteur', ChoiceType::class, [
                'label'       => 'Service débiteur',
                'placeholder' => '-- Choisir une service --',
                'required'    => false,
                'choices'     => $serviceChoices,
                'choice_label' => function ($value) use ($options) {
                    // Retrouver le bon item et afficher service_code . ' ' . service_libelle
                    $item = $options['allAgenceServices'][$value];
                    return $item['service_code'] . ' ' . $item['service_libelle'];
                },
                'choice_attr' => function ($val) use ($serviceAttr) {
                    return $serviceAttr[$val] ?? [];
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'             => DitSearchDto::class,
            'allAgenceServices' => [],
        ]);
    }
}
