<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Paiement;
use App\Entity\Reservation;
use App\Entity\Vol;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Reference')
            ->add('DateRes')
            ->add('Satut')
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'id',
            ])
            ->add('paiement', EntityType::class, [
                'class' => Paiement::class,
                'choice_label' => 'id',
            ])
            ->add('vol', EntityType::class, [
                'class' => Vol::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
