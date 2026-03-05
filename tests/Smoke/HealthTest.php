<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthTest extends WebTestCase
{
    public function testHealthEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseIsSuccessful();
    }
}