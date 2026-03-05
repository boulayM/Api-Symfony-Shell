<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ObservabilityTest extends WebTestCase
{
    public function testApiResponsesExposeRequestIdHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(200);

        $requestId = $client->getResponse()->headers->get('X-Request-Id');
        self::assertIsString($requestId);
        self::assertNotSame('', $requestId);
    }

    public function testApiErrorDetailsContainMatchingRequestIdInDebug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/does-not-exist');

        self::assertResponseStatusCodeSame(404);

        $requestId = (string) $client->getResponse()->headers->get('X-Request-Id');
        self::assertNotSame('', $requestId);

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame('not_found', $payload['code'] ?? null);
        self::assertIsArray($payload['details'] ?? null);
        self::assertSame($requestId, $payload['details']['requestId'] ?? null);
    }
}