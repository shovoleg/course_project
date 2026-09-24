<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class UserRoleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidate', CheckboxType::class, ['label' => 'role.candidate', 'required' => false])
            ->add('recruiter', CheckboxType::class, ['label' => 'role.recruiter', 'required' => false])
            ->add('admin', CheckboxType::class, ['label' => 'role.admin', 'required' => false]);
    }
}
