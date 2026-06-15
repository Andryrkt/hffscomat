<?php

namespace App\Form\Atelier\Dit\soumission\Devis;

use App\Dto\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

class DitDevisSoumisAValidationType extends AbstractType
{
    private const TACHE_VALIDATEUR = [
        'Vérif prix'              => 'Vérif prix',
        'Vérif prix & calcul DHL' => 'Vérif prix & calcul DHL'
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'numeroDit',
                TextType::class,
                [
                    'label' => 'Numéro DIT',
                    'attr' => [
                        'disabled' => true
                    ]
                ]
            )
            ->add(
                'numeroDevis',
                IntegerType::class,
                [
                    'label' => 'Numéro devis *',
                    'required' => false,
                    'constraints' => [
                        new Assert\Length([
                            'max' => 8,
                            'maxMessage' => 'Le numéro OR ne doit pas dépasser {{ limit }} caractères.',
                        ]),
                    ],
                    'attr' => [
                        'min' => 0,
                        'pattern' => '\d*', // Permet uniquement l'entrée de chiffres
                        'disabled' => true
                    ],
                ]
            )
            ->add('tacheValidateur', ChoiceType::class, [
                'label' => 'Tâche à faire par le Parts Manager *',
                'choices' => self::TACHE_VALIDATEUR,
                'expanded' => true,
                'multiple' => false,
                'required' => $options['data']->type === 'VP' ? true : false,
                'attr' => [
                    'data-error-message' => 'Veuillez sélectionner une tâche à faire par le Parts Manager.', // Message d'erreur personnalisé pour le champ
                ]

            ])
            ->add(
                'pieceJoint01',
                FileType::class,
                [
                    'label' => 'Upload File',
                    'required' => true,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuiller sélectionner le devis', // Message d'erreur si le champ est vide
                        ]),
                        new File([
                            'maxSize' => '5M',
                            'maxSizeMessage' => 'Le fichier ne doit pas dépasser 5 Mo.', // Message personnalisé pour la taille
                            'mimeTypes' => [
                                'application/pdf',
                            ],
                            'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
                        ])
                    ],
                    'attr' => [
                        'data-error-message' => 'le pièce jointe est obligatoire, veillez ajouter un fichier PDF.', // Message d'erreur personnalisé pour le champ
                    ]
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DitDevisSoumisAValidationDto::class,
        ]);
    }
}
