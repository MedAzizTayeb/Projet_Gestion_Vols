<?php

namespace App\Form;

use App\Entity\Passager;
use App\Entity\Reservation;
use App\Entity\Ticket;
use App\Entity\Vol;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idTicket')
            ->add('numero')
            ->add('dateCreation')
            ->add('pdfPath')
            ->add('reservation', EntityType::class, [
                'class' => Reservation::class,
                'choice_label' => 'id',
            ])
            ->add('passager', EntityType::class, [
                'class' => Passager::class,
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
            'data_class' => Ticket::class,
        ]);
    }
}
