<?php

namespace App\Form;

use App\Entity\Aeroport;
use App\Entity\Avion;
use App\Entity\Vol;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('NumVol')
            ->add('DateDepart')
            ->add('DateArrive')
            ->add('port')
            ->add('escale')
            ->add('placesDisponibles')
            ->add('depart', EntityType::class, [
                'class' => Aeroport::class,
                'choice_label' => 'id',
            ])
            ->add('arrivee', EntityType::class, [
                'class' => Aeroport::class,
                'choice_label' => 'id',
            ])
            ->add('avion', EntityType::class, [
                'class' => Avion::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vol::class,
        ]);
    }
}
