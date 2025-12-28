<?php

namespace App\Form;

use App\Entity\Administrateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class AdministrateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['data']->getId() !== null;

        $builder
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'attr' => [
                    'placeholder' => 'Ex: ADM001',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un matricule',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le matricule doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le matricule ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Nom de famille',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Prénom',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un prénom',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'exemple@aeromanager.com',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un email',
                    ]),
                    new Email([
                        'message' => 'L\'email {{ value }} n\'est pas valide.',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => !$isEdit,
                'first_options' => [
                    'label' => $isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe',
                    'attr' => [
                        'placeholder' => 'Mot de passe',
                        'class' => 'form-control'
                    ],
                    'constraints' => $isEdit ? [] : [
                        new NotBlank([
                            'message' => 'Veuillez entrer un mot de passe',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'placeholder' => 'Confirmer le mot de passe',
                        'class' => 'form-control'
                    ],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => '+216 XX XXX XXX',
                    'class' => 'form-control'
                ],
            ])
            ->add('niveauAcces', ChoiceType::class, [
                'label' => 'Niveau d\'accès',
                'choices' => [
                    'Super Administrateur (Niveau 1)' => 1,
                    'Administrateur Principal (Niveau 2)' => 2,
                    'Administrateur (Niveau 3)' => 3,
                    'Assistant Administrateur (Niveau 4)' => 4,
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un niveau d\'accès',
                    ]),
                ],
            ])
            ->add('departement', ChoiceType::class, [
                'label' => 'Département',
                'required' => false,
                'choices' => [
                    'Direction Générale' => 'Direction Générale',
                    'Opérations' => 'Opérations',
                    'Maintenance' => 'Maintenance',
                    'Service Client' => 'Service Client',
                    'Ressources Humaines' => 'Ressources Humaines',
                    'Finance' => 'Finance',
                    'IT' => 'IT',
                    'Sécurité' => 'Sécurité',
                ],
                'placeholder' => 'Sélectionner un département',
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                    'Gestionnaire Vols' => 'ROLE_VOL_MANAGER',
                    'Gestionnaire Avions' => 'ROLE_AVION_MANAGER',
                    'Gestionnaire Clients' => 'ROLE_CLIENT_MANAGER',
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'form-check'
                ],
            ])
            ->add('permissions', TextareaType::class, [
                'label' => 'Permissions spéciales',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Permissions additionnelles (une par ligne)',
                    'class' => 'form-control',
                    'rows' => 4
                ],
                'help' => 'Listez les permissions spéciales, une par ligne'
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Administrateur::class,
        ]);
    }
}
