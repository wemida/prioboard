<?php

namespace App\Form;

use App\Entity\AppSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('skin', ChoiceType::class, [
                'choices' => [
                    'Color screen' => AppSettings::SKIN_COLOR,
                    'Monochrome screen' => AppSettings::SKIN_MONO,
                ],
            ])
            ->add('fontSize', ChoiceType::class, [
                'choices' => [
                    'Small' => AppSettings::FONT_SMALL,
                    'Medium' => AppSettings::FONT_MEDIUM,
                    'Large' => AppSettings::FONT_LARGE,
                ],
            ])
            ->add('refreshInterval', IntegerType::class, [
                'label' => 'Refresh check interval in seconds',
                'attr' => [
                    'min' => 10,
                    'max' => 600,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppSettings::class,
        ]);
    }
}
