<?php

namespace App\Form;

use App\Entity\Passager;
use App\Entity\Reservation;
use App\Entity\Ticket;
use App\Entity\Vol;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idTicket', IntegerType::class, [
                'label' => 'ID Ticket',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Généré automatiquement',
                    'class' => 'form-control'
                ]
            ])
            ->add('numero', TextType::class, [
                'label' => 'Numéro de billet',
                'required' => false,
                'attr' => [
                    'placeholder' => 'TKT-XXXXXX (généré automatiquement)',
                    'class' => 'form-control'
                ]
            ])
            ->add('dateCreation', DateType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('pdfPath', TextType::class, [
                'label' => 'Chemin PDF',
                'required' => false,
                'attr' => [
                    'placeholder' => '/tickets/TKT-XXXXX.pdf',
                    'class' => 'form-control'
                ]
            ])
            ->add('reservation', EntityType::class, [
                'class' => Reservation::class,
                'choice_label' => function(Reservation $reservation) {
                    return $reservation->getReference() . ' - ' .
                        $reservation->getClient()->getNom() . ' ' .
                        $reservation->getClient()->getPrenom();
                },
                'label' => 'Réservation',
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Sélectionnez une réservation'
            ])
            ->add('passager', EntityType::class, [
                'class' => Passager::class,
                'choice_label' => function(?Passager $passager) {
                    if (!$passager) {
                        return '';
                    }
                    return $passager->getNom() . ' ' . $passager->getPrenom();
                },
                'label' => 'Passager',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Sélectionnez un passager (optionnel)'
            ])
            ->add('vol', EntityType::class, [
                'class' => Vol::class,
                'choice_label' => function(?Vol $vol) {
                    if (!$vol) {
                        return '';
                    }
                    return $vol->getNumVol() . ' - ' .
                        $vol->getDepart()->getVille() . ' → ' .
                        $vol->getArrivee()->getVille();
                },
                'label' => 'Vol',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Sélectionnez un vol (optionnel)'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
