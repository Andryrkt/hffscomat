<?php

namespace App\Form\Atelier\CVA;

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Dto\Atelier\CVA\OrCsaATraiterDto;
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

class OrCsaATraiterSearchType extends AbstractType
{


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
                    'required' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'             => OrCsaATraiterDto::class,
            'allAgenceServices' => [],
        ]);
    }
}
