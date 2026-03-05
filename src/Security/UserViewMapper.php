<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;

final class UserViewMapper
{
    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}