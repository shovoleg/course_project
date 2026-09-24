<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Position;
use App\Entity\PositionLevel;
use App\Entity\PositionVisibility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PositionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'position.title'])
            ->add('shortDescription', TextareaType::class, ['label' => 'position.description'])
            ->add('company', TextType::class, ['label' => 'position.company', 'required' => false])
            ->add('level', EnumType::class, [
                'class' => PositionLevel::class,
                'label' => 'position.level',
                'required' => false,
                'placeholder' => 'position.all_levels',
                'choice_label' => static fn (PositionLevel $level): string => 'position.level.'.$level->value,
            ])
            ->add('visibility', EnumType::class, [
                'class' => PositionVisibility::class,
                'label' => 'position.visibility',
                'choice_label' => static fn (PositionVisibility $visibility): string => 'position.'.$visibility->value,
            ])
            ->add('maxProjects', IntegerType::class, ['label' => 'position.max_projects'])
            ->add('version', HiddenType::class, [
                'mapped' => false,
                'data' => $options['version'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
            'version' => 1,
        ]);
    }
}
