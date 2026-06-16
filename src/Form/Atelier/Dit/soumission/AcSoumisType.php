<?php

namespace App\Form\Atelier\Dit\soumission;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\soumission\AccuseReceptionDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class AcSoumisType extends AbstractType
{
    use FormatageTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomClient', TextType::class, [
                'label'    => 'Client *',
                'required' => true,
                'attr'     => [
                    'class'        => 'autocomplete',
                    'autocomplete' => 'off',
                ]
            ])
            ->add('numeroBc', TextType::class, [
                'label'    => 'N° de bon de commande *',
                'required' => true,
            ])
            ->add('dateBc', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Date du bon de commande *',
                'required' => true,
            ])
            ->add('descriptionBc', TextareaType::class, [
                'label'    => 'Description bon de commande *',
                'required' => true,
                'attr'     => [
                    'rows'  => 5,
                    'class' => 'detailDemande'
                ],
            ])
            ->add('emailClient', EmailType::class, [
                'label'    => 'Adress email client *',
                'required' => true
            ])
            ->add('dateCreation', DateType::class, [
                'label'    => 'Date',
                'required' => false,
                'attr'     => [
                    'disabled' => true
                ]
            ])
            ->add('numeroDevis', TextType::class, [
                'label'    => 'N° devis',
                'required' => false,
                'attr'     => [
                    'disabled' => true
                ]
            ])
            ->add('statutDevis', TextType::class, [
                'label'    => 'Statut devis',
                'required' => false,
                'attr'     => [
                    'disabled' => true
                ]
            ])
            ->add('numeroDit', TextType::class, [
                'label'    => 'N° DIT',
                'required' => false,
                'attr'     => [
                    'disabled' => true
                ]
            ])
            ->add('dateDevis', TextType::class, [
                'label'    => 'Date devis',
                'required' => false,
                'attr'     => [
                    'disabled' => true
                ]
            ])
            ->add('montantDevis', TextType::class, [
                'label'    => 'Montant devis',
                'required' => false,
                'attr'     => [
                    'disabled' => false
                ]
            ])
            ->add('pieceJoint01', FileType::class, [
                'label' => 'Bon de commande (PDF) *',
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF file.',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AccuseReceptionDto::class,
        ]);
    }
}
