<?php

declare(strict_types=1);

namespace App\Security;

final class AppRoles
{
    public const USER = 'ROLE_USER';
    public const ADMIN = 'ROLE_ADMIN';

    private function __construct()
    {
    }
}