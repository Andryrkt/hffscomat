<?php

namespace App\Form\magasin\Ors\Traiter;


use App\Dto\Magasin\Ors\Traiter\OrATraiterSearchDto;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Model\magasin\Ors\Traiter\OrTraiterModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrATraiterSearchType extends AbstractType
{
    private OrTraiterModel $OrTraiterModel;

    public function __construct()
    {
        $this->OrTraiterModel = new OrTraiterModel();
    }

    private function agence(string $codeSociete)
    {
        return array_combine($this->OrTraiterModel->agence($codeSociete), $this->OrTraiterModel->agence($codeSociete));
    }

    private function agenceAutoriserUser(string $codeAgence, string $codeSociete)
    {
        return array_combine($this->OrTraiterModel->agenceUser($codeAgence, $codeSociete), $this->OrTraiterModel->agenceUser($codeAgence, $codeSociete));
    }

    public function getDescriptionWorNiveauUrgence()
    {
        $worNiveauUrgenceModel = new WorNiveauUrgenceModel();
        $descriptions = $worNiveauUrgenceModel->getDescription();
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
                    'placeholder' => "-- Niveau d'urgence --",
                    'choices' => $this->getDescriptionWorNiveauUrgence(),
                    'required' => false,
                ]
            )
            ->add('numDit', TextType::class, [
                'label' => 'n° DIT',
                'required' => false
            ])
            ->add('numOr', NumberType::class, [
                'label' => 'n° OR',
                'required' => false
            ])
            ->add('referencePiece', TextType::class, [
                'label' => 'Référence pièce',
                'required' => false
            ])
            ->add('designation', TextType::class, [
                'label' => 'Désignation',
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
                    $services = $this->OrTraiterModel->service($data['agence']);

                    foreach ($services as $value) {
                        $service[$value['text']] = $value['text'];
                    }
                } else {
                    $service = [];
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

            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $event->setData($data);
            });
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrATraiterSearchDto::class
        ]);
    }
}
