<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AttributeCategory;
use App\Entity\AttributeType;
use App\Entity\CvAttribute;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AttributeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builtin = $options['builtin'];
        $builder
            ->add('name', TextType::class, [
                'label' => 'attribute.name',
                'disabled' => $builtin,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'attribute.description',
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => AttributeCategory::class,
                'choice_label' => static fn (AttributeCategory $category): string => 'category.'.$category->getCode(),
                'label' => 'attribute.category',
            ])
            ->add('type', EnumType::class, [
                'class' => AttributeType::class,
                'label' => 'attribute.type',
                'disabled' => $builtin,
                'choice_label' => static fn (AttributeType $type): string => 'type.'.$type->value,
            ])
            ->add('optionsText', TextareaType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'attribute.options',
                'data' => $options['options_text'],
            ])
            ->add('version', HiddenType::class, [
                'mapped' => false,
                'data' => $options['version'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CvAttribute::class,
            'builtin' => false,
            'options_text' => '',
            'version' => 1,
        ]);
    }
}
