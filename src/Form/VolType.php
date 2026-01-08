<?php

namespace App\Form;

use App\Entity\Aeroport;
use App\Entity\Avion;
use App\Entity\Vol;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class VolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('NumVol', TextType::class, [
                'label' => 'Numéro de vol',
                'attr' => [
                    'placeholder' => 'Ex: AF1234, TU456',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le numéro de vol est requis',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 10,
                        'minMessage' => 'Le numéro de vol doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le numéro de vol ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'help' => 'Code unique identifiant le vol',
            ])
            ->add('depart', EntityType::class, [
                'class' => Aeroport::class,
                'choice_label' => function(Aeroport $aeroport) {
                    return $aeroport->getCodeIATA() . ' - ' . $aeroport->getVille() . ', ' . $aeroport->getPays();
                },
                'label' => 'Aéroport de départ',
                'placeholder' => 'Sélectionner un aéroport',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'aéroport de départ est requis',
                    ]),
                ],
            ])
            ->add('arrivee', EntityType::class, [
                'class' => Aeroport::class,
                'choice_label' => function(Aeroport $aeroport) {
                    return $aeroport->getCodeIATA() . ' - ' . $aeroport->getVille() . ', ' . $aeroport->getPays();
                },
                'label' => 'Aéroport d\'arrivée',
                'placeholder' => 'Sélectionner un aéroport',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'aéroport d\'arrivée est requis',
                    ]),
                ],
            ])
            ->add('DateDepart', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de départ est requise',
                    ]),
                ],
                'help' => 'Date et heure de départ en temps local',
            ])
            ->add('DateArrive', DateTimeType::class, [
                'label' => 'Date et heure d\'arrivée',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date d\'arrivée est requise',
                    ]),
                ],
                'help' => 'Date et heure d\'arrivée en temps local',
            ])
            ->add('avion', EntityType::class, [
                'class' => Avion::class,
                'choice_label' => function(Avion $avion) {
                    return $avion->getModele() . ' (' . $avion->getImmatriculation() . ') - ' . $avion->getCapacite() . ' places';
                },
                'label' => 'Avion',
                'placeholder' => 'Sélectionner un avion',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'avion est requis',
                    ]),
                ],
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('a')
                        ->where('a.disponibilite = :disponible')
                        ->setParameter('disponible', true)
                        ->orderBy('a.modele', 'ASC');
                },
            ])
            ->add('port', TextType::class, [
                'label' => 'Porte d\'embarquement',
                'attr' => [
                    'placeholder' => 'Ex: A12, B5, Terminal 2E',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La porte d\'embarquement est requise',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'La porte ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('escale', TextType::class, [
                'label' => 'Escale(s)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: CDG, LHR (optionnel)',
                    'class' => 'form-control',
                ],
                'help' => 'Codes IATA des escales, séparés par des virgules',
            ])
            ->add('placesDisponibles', IntegerType::class, [
                'label' => 'Places disponibles',
                'attr' => [
                    'placeholder' => 'Nombre de places',
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nombre de places est requis',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de places doit être supérieur à 0',
                    ]),
                ],
                'help' => 'Nombre de sièges disponibles à la vente',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vol::class,
            'constraints' => [
                new Callback([$this, 'validateVol']),
            ],
        ]);
    }

    public function validateVol(Vol $vol, ExecutionContextInterface $context): void
    {
        // Validate that arrival is after departure
        if ($vol->getDateArrive() && $vol->getDateDepart()) {
            if ($vol->getDateArrive() <= $vol->getDateDepart()) {
                $context->buildViolation('La date d\'arrivée doit être après la date de départ')
                    ->atPath('DateArrive')
                    ->addViolation();
            }
        }

        // Validate that departure and arrival airports are different
        if ($vol->getDepart() && $vol->getArrivee()) {
            if ($vol->getDepart()->getId() === $vol->getArrivee()->getId()) {
                $context->buildViolation('L\'aéroport d\'arrivée doit être différent de l\'aéroport de départ')
                    ->atPath('arrivee')
                    ->addViolation();
            }
        }
    }
}
