<?php

namespace App\Form\magasin\Commande\Traiter;

use App\Controller\Traits\Transformation;
use App\Dto\Magasin\Commande\Traiter\CommandeTraiterSearchDto;
use App\Model\magasin\Commande\Traiter\CommandeTraiterModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeTraiterSearchType extends AbstractType
{
    use Transformation;

    private CommandeTraiterModel $commandeTraiterModel;

    public function __construct()
    {
        $this->commandeTraiterModel = new CommandeTraiterModel();
    }


    private function agence(string $codeSociete)
    {
        return array_combine($this->commandeTraiterModel->agence($codeSociete), $this->commandeTraiterModel->agence($codeSociete));
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $services = [];
        $services = $this->transformEnSeulTableauAvecKeyService($this->commandeTraiterModel->service($options['data']->codeSociete));

        $builder

            ->add('referencePiece', TextType::class, [
                'label' => 'Référence pièce',
                'required' => false
            ])


            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de création Cmde (début)',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de création Cmde (fin)',
                'required' => false,
            ])

            // ->add(
            //     'agence',
            //     ChoiceType::class,
            //     [
            //         'label' => 'Agence ',
            //         'required' => false,
            //         'choices' => $this->agence($options['data']->codeSociete) ?? [],

            //     ]
            // )

            ->add('service', ChoiceType::class, [
                'label' => 'Services ',
                'label_attr' => [
                    'class' => 'fw-bold'
                ],
                'multiple' => true,
                'choices' => $services,
                'placeholder' => " -- choisir service--",
                'expanded' => true,
                'data' => array_keys($services),
            ])


            ->add('agenceUserHidden', HiddenType::class, [
                'data' => $options['data']->agenceUser ?? null,
            ])

            ->add('constructeur', TextType::class, [
                'label' => 'Constructeur',
                'required' => false
            ])
            ->add('numCommande', TextType::class, [
                'label' => 'N° Commande',
                'required' => false
            ])

            ->add('numDevis', TextType::class, [
                'label' => 'N° Devis',
                'required' => false
            ])

            // Auto complete
            ->add('codeClient', TextType::class, [
                'label' => 'Client',
                'required' => false,
            ])

            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
                $data = $event->getData();
                $event->setData($data);
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CommandeTraiterSearchDto::class
        ]);
    }
}
