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
                'label' => 'Title',
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'Keep the title short and readable',
                ],
            ])
            ->add('columnKey', ChoiceType::class, [
                'label' => 'Column',
                'choices' => [
                    'WIP' => Card::COLUMN_WIP,
                    'Prio 1' => Card::COLUMN_PRIO_1,
                    'Prio 2' => Card::COLUMN_PRIO_2,
                ],
            ])
            ->add('color', ChoiceType::class, [
                'label' => 'Card color',
                'choices' => [
                    'Neutral' => 'neutral',
                    'Red' => 'red',
                    'Orange' => 'orange',
                    'Yellow' => 'yellow',
                    'Green' => 'green',
                    'Blue' => 'blue',
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
