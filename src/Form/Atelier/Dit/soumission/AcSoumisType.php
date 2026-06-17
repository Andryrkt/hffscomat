<?php

namespace App\Form\Atelier\Dit\soumission;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
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
                'attr'     => [
                    'class'        => 'autocomplete',
                    'autocomplete' => 'off',
                ]
            ])
            ->add('numeroBc', TextType::class, [
                'label'    => 'N° de bon de commande *'
            ])
            ->add('dateBc', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Date du bon de commande *',
            ])
            ->add('descriptionBc', TextareaType::class, [
                'label'    => 'Déscription du bon de commande *',
                'attr'     => [
                    'rows'  => 7
                ],
            ])
            ->add('emailClient', EmailType::class, [
                'label'    => 'Adresse email client *'
            ])
            ->add('dateCreation', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Date',
                'attr'     => [
                    'class' => 'div-disabled'
                ]
            ])
            ->add('numeroDevis', TextType::class, [
                'label'    => 'N° devis',
                'attr'     => [
                    'class' => 'div-disabled'
                ]
            ])
            ->add('statutDevis', TextType::class, [
                'label'    => 'Statut devis',
                'attr'     => [
                    'class' => 'div-disabled'
                ]
            ])
            ->add('numeroDit', TextType::class, [
                'label'    => 'N° DIT',
                'attr'     => [
                    'class' => 'div-disabled'
                ]
            ])
            ->add('dateDevis', DateType::class, [
                'widget'   => 'single_text',
                'label'    => 'Date de soumission du devis',
                'attr'     => [
                    'class' => 'div-disabled'
                ]
            ])
            ->add('montantDevis', TextType::class, [
                'label'    => 'Montant du devis',
                'attr'     => [
                    'class' => 'div-disabled'
                ]
            ])
            ->add('pieceJoint01', FileType::class, [
                'constraints' => [
                    new File([
                        'maxSize'   => '5M',
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
