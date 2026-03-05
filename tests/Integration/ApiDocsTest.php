<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiDocsTest extends WebTestCase
{
    public function testApiDocsUiIsPublicAndReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
        self::assertStringContainsString('SwaggerUIBundle', (string) $client->getResponse()->getContent());
    }

    public function testApiDocsYamlIsPublicAndReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs/swagger.yaml');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/yaml; charset=UTF-8');
        self::assertResponseHeaderSame('content-disposition', 'inline; filename=swagger.yaml');
    }
}
