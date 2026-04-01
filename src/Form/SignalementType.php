<?php

namespace App\Form;

use App\Entity\BusStop;
use App\Entity\Signalement;
use App\Enum\SignalementMotif;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
            ])
            ->add('motif', ChoiceType::class, [
                'choices' => SignalementMotif::cases(),
                'choice_label' => static fn (SignalementMotif $motif): string => $motif->label(),
                'required' => true,
                'placeholder' => 'Selectionnez un motif',
            ])
            ->add('details', TextareaType::class, [
                'required' => true,
            ])
            ->add('stop', EntityType::class, [
                'class' => BusStop::class,
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Selectionnez un arret (optionnel)',
                'label' => 'Arret de bus',
            ])
            ->add('incident_date', DateTimeType::class, [
                'property_path' => 'incidentDate',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Date et heure de l\'incident',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
        ]);
    }
}
