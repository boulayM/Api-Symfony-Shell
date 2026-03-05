<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\User;
use App\Security\UserViewMapper;
use PHPUnit\Framework\TestCase;

final class UserViewMapperTest extends TestCase
{
    public function testMapsExpectedFields(): void
    {
        $user = new User('user@test.local');
        $user->setFirstName('User');
        $user->setLastName('Test');

        $mapped = (new UserViewMapper())->toArray($user);

        self::assertSame('user@test.local', $mapped['email']);
        self::assertSame('User', $mapped['firstName']);
        self::assertSame('Test', $mapped['lastName']);
        self::assertArrayHasKey('createdAt', $mapped);
    }
}