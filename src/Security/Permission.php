<?php

declare(strict_types=1);

namespace App\Security;

final class Permission
{
    public const USERS_READ = 'users.read';
    public const USERS_CREATE = 'users.create';
    public const USERS_UPDATE = 'users.update';
    public const USERS_DELETE = 'users.delete';
    public const AUDIT_READ = 'audit.read';
    public const AUDIT_EXPORT = 'audit.export';

    private function __construct()
    {
    }
}

