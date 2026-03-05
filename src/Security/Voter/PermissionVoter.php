<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\AppRoles;
use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PermissionVoter extends Voter
{
    private const SUPPORTED = [
        Permission::USERS_READ,
        Permission::USERS_CREATE,
        Permission::USERS_UPDATE,
        Permission::USERS_DELETE,
        Permission::AUDIT_READ,
        Permission::AUDIT_EXPORT,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Shell default: admin has all admin permissions.
        if (in_array(AppRoles::ADMIN, $user->getRoles(), true)) {
            return true;
        }

        return false;
    }
}

