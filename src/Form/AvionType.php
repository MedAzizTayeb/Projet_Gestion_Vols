<?php

namespace App\Form;

use App\Entity\Avion;
use App\Entity\CategorieAvion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Positive;

class AvionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modele', TextType::class, [
                'label' => 'Modèle',
                'attr' => [
                    'placeholder' => 'Ex: Boeing 737-800, Airbus A320',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le modèle est requis',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le modèle doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le modèle ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('immatriculation', TextType::class, [
                'label' => 'Immatriculation',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: F-GKXY, N12345',
                    'class' => 'form-control',
                ],
                'help' => 'Numéro d\'immatriculation de l\'avion (optionnel)',
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité',
                'attr' => [
                    'placeholder' => 'Nombre de passagers',
                    'class' => 'form-control',
                    'min' => 1,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La capacité est requise',
                    ]),
                    new Positive([
                        'message' => 'La capacité doit être supérieure à 0',
                    ]),
                ],
                'help' => 'Nombre total de passagers',
            ])
            ->add('heuresVol', IntegerType::class, [
                'label' => 'Heures de vol',
                'required' => false,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'constraints' => [
                    new PositiveOrZero([
                        'message' => 'Les heures de vol doivent être positives ou nulles',
                    ]),
                ],
                'help' => 'Nombre total d\'heures de vol',
            ])
            ->add('derniereMaintenance', DateType::class, [
                'label' => 'Dernière maintenance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Date de la dernière opération de maintenance',
            ])
            ->add('categorie', EntityType::class, [
                'class' => CategorieAvion::class,
                'choice_label' => 'nomCat',
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionner une catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Type ou classe de l\'avion',
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible pour les vols',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Cocher si l\'avion est opérationnel et disponible',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avion::class,
        ]);
    }
}
