<?php

namespace App\Form;

use App\Entity\Aeroport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AeroportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeIATA', TextType::class, [
                'label' => 'Code IATA',
                'attr' => [
                    'placeholder' => 'Ex: CDG, TUN, JFK',
                    'class' => 'form-control',
                    'maxlength' => 3,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le code IATA est requis',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 3,
                        'exactMessage' => 'Le code IATA doit contenir exactement {{ limit }} caractères',
                    ]),
                    new Regex([
                        'pattern' => '/^[A-Z]{3}$/',
                        'message' => 'Le code IATA doit contenir 3 lettres majuscules',
                    ]),
                ],
                'help' => 'Code à 3 lettres (ex: CDG pour Paris-Charles de Gaulle)',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'aéroport',
                'attr' => [
                    'placeholder' => 'Ex: Aéroport International de Paris-Charles de Gaulle',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom de l\'aéroport est requis',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'placeholder' => 'Ex: Paris, Tunis, New York',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La ville est requise',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'La ville doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr' => [
                    'placeholder' => 'Ex: France, Tunisie, États-Unis',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le pays est requis',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le pays doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le pays ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Aeroport::class,
        ]);
    }
}
