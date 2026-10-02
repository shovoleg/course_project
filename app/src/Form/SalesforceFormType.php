<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SalesforceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('company', TextType::class, ['label' => 'salesforce.company', 'constraints' => [new NotBlank(), new Length(['max' => 255])]])
            ->add('firstName', TextType::class, ['label' => 'salesforce.firstName', 'constraints' => [new NotBlank(), new Length(['max' => 100])]])
            ->add('lastName', TextType::class, ['label' => 'salesforce.lastName', 'constraints' => [new NotBlank(), new Length(['max' => 100])]])
            ->add('phone', TextType::class, ['label' => 'salesforce.phone', 'required' => false, 'constraints' => [new Length(['max' => 40])]])
            ->add('industry', ChoiceType::class, ['label' => 'salesforce.industry', 'choices' => ['salesforce.industry.tech' => 'Technology', 'salesforce.industry.finance' => 'Finance', 'salesforce.industry.education' => 'Education', 'salesforce.industry.health' => 'Healthcare', 'salesforce.industry.other' => 'Other'], 'expanded' => false])
            ->add('description', TextareaType::class, ['label' => 'salesforce.description', 'required' => false, 'attr' => ['rows' => 3], 'constraints' => [new Length(['max' => 2000])]])
            ->add('newsletter', CheckboxType::class, ['label' => 'salesforce.newsletter', 'required' => false]);
    }
}
