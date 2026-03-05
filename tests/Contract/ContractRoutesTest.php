<?php

declare(strict_types=1);

namespace App\Tests\Contract;

use PHPUnit\Framework\TestCase;

final class ContractRoutesTest extends TestCase
{
    public function testSwaggerContainsContractPaths(): void
    {
        $swaggerPath = dirname(__DIR__, 2) . '/docs/swagger.yaml';
        $swagger = (string) file_get_contents($swaggerPath);

        self::assertStringContainsString('/api/auth/register:', $swagger);
        self::assertStringContainsString('/api/admin/users/register:', $swagger);
        self::assertStringContainsString('/api/public/users/me:', $swagger);
    }
}