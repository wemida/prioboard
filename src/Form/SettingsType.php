<?php

namespace App\Form;

use App\Entity\AppSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
                'label' => 'form.settings.display_mode',
                'choices' => [
                    'form.settings.color_screen' => AppSettings::SKIN_COLOR,
                    'form.settings.monochrome_screen' => AppSettings::SKIN_MONO,
                ],
            ])
            ->add('fontSize', ChoiceType::class, [
                'label' => 'form.settings.font_size',
                'choices' => [
                    'common.small' => AppSettings::FONT_SMALL,
                    'common.medium' => AppSettings::FONT_MEDIUM,
                    'common.large' => AppSettings::FONT_LARGE,
                ],
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'form.settings.language',
                'choices' => [
                    'language.english' => AppSettings::LANGUAGE_ENGLISH,
                    'language.german' => AppSettings::LANGUAGE_GERMAN,
                ],
            ])
            ->add('refreshInterval', IntegerType::class, [
                'label' => 'form.settings.refresh_interval',
                'attr' => [
                    'min' => 10,
                    'max' => 600,
                ],
            ])
            ->add('deleteConfirmationEnabled', ChoiceType::class, [
                'label' => 'form.settings.delete_confirmation',
                'choices' => [
                    'common.yes' => true,
                    'common.no' => false,
                ],
                'multiple' => false,
                'required' => true,   // key part
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppSettings::class,
        ]);
    }
}
