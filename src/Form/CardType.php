<?php

namespace App\Form;

use App\Entity\Card;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'form.card.title',
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'form.card.title_placeholder',
                ],
            ])
            ->add('columnKey', ChoiceType::class, [
                'label' => 'form.card.column',
                'choices' => [
                    'WIP' => Card::COLUMN_WIP,
                    'Prio 1' => Card::COLUMN_PRIO_1,
                    'Prio 2' => Card::COLUMN_PRIO_2,
                ],
            ])
            ->add('color', ChoiceType::class, [
                'label' => 'form.card.color',
                'choices' => [
                    'color.neutral' => 'neutral',
                    'color.red' => 'red',
                    'color.orange' => 'orange',
                    'color.yellow' => 'yellow',
                    'color.green' => 'green',
                    'color.blue' => 'blue',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}
