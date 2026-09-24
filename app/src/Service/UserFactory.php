<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CandidateAttributeValue;
use App\Entity\User;
use App\Repository\CvAttributeRepository;

final class UserFactory
{
    public function __construct(private CvAttributeRepository $attributes)
    {
    }

    public function create(string $email, array $roles, ?string $passwordHash): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($passwordHash);
        foreach ($this->attributes->builtins() as $attribute) {
            new CandidateAttributeValue($user, $attribute);
        }

        return $user;
    }
}
