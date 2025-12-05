<?php

namespace App\Form;

use App\Entity\Passager;
use App\Entity\reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PassagerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numPassport')
            ->add('nationalite')
            ->add('dateNaissance')
            ->add('besoinsSpeciaux')
            ->add('poidsBagages')
            ->add('reservation', EntityType::class, [
                'class' => reservation::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Passager::class,
        ]);
    }
}
