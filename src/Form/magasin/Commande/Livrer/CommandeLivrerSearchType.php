<?php

namespace App\Form\magasin\Commande\Livrer;

use App\Dto\Magasin\Commande\Livrer\CommandeLivrerSearchDto;
use App\Model\magasin\Ors\Livrer\OrLivrerModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeLivrerSearchType extends AbstractType
{

    private OrLivrerModel $OrLivrerModel;

    public function __construct()
    {
        $this->OrLivrerModel = new OrLivrerModel();
    }


    private function agence(string $codeSociete)
    {
        return array_combine($this->OrLivrerModel->agence($codeSociete), $this->OrLivrerModel->agence($codeSociete));
    }

    private function agenceAutoriserUser(string $codeAgence, string $codeSociete)
    {
        return array_combine($this->OrLivrerModel->agenceUser($codeAgence, $codeSociete), $this->OrLivrerModel->agenceUser($codeAgence, $codeSociete));
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('referencePiece', TextType::class, [
                'label' => 'Référence pièce',
                'required' => false
            ])


            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de création OR (début)',
                'required' => false,
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de création OR (fin)',
                'required' => false,
            ])

            ->add(
                'agence',
                ChoiceType::class,
                [
                    'label' => 'Agence débiteur',
                    'required' => false,
                    'choices' => $this->agence($options['data']->codeSociete) ?? [],
                    'placeholder' => ' -- Choisir une agence --'
                ]
            )
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $form->add(
                    'service',
                    ChoiceType::class,
                    [
                        'label' => 'Service débiteur',
                        'required' => false,
                        'choices' => [],
                        'placeholder' => ' -- Choisir un service --'
                    ]
                );
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                $service = [];
                if ($data['agence'] !== "") {
                    $services = $this->OrLivrerModel->service($data['agence']);

                    foreach ($services as $value) {
                        $service[$value['text']] = $value['text'];
                    }
                }


                $form->add(
                    'service',
                    ChoiceType::class,
                    [
                        'label' => 'Service débiteur',
                        'required' => false,
                        'choices' => $service,
                        'placeholder' => ' -- Choisir un service --'
                    ]
                );
            })

            ->add('agenceUser', ChoiceType::class, [
                'label' => 'Agence Emetteur',
                'required' => false,
                'choices' => $this->agenceAutoriserUser($options['data']->agenceUser, $options['data']->codeSociete) ?? [],
                'placeholder' => ' -- Choisir une agence --',
            ])

            ->add('agenceUserHidden', HiddenType::class, [
                'data' => $options['data']->agenceUser ?? null,
            ])

            ->add('constructeur', TextType::class, [
                'label' => 'Constructeur',
                'required' => false
            ])
            ->add('numCommande', TextType::class, [
                'label' => 'n° Commande',
                'required' => false
            ])

            ->add('numDevis', TextType::class, [
                'label' => 'n° Devis',
                'required' => false
            ])

            // Auto complete
            ->add('codeClient', TextType::class, [
                'label' => 'code Client',
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
            'data_class' => CommandeLivrerSearchDto::class
        ]);
    }
}
