<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use App\Security\AppRoles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RateLimitingTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initializeDatabase();
    }

    public function testLoginIsRateLimitedAfterTooManyFailures(): void
    {
        $email = sprintf('throttle-login-%s@test.local', bin2hex(random_bytes(4)));
        $throttled = false;

        for ($i = 0; $i < 30; $i++) {
            $this->client->jsonRequest('POST', '/api/auth/login', [
                'email' => $email,
                'password' => 'WrongPassword!'
            ]);
            self::assertResponseStatusCodeSame(401);

            $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
            if (is_array($payload) && str_contains((string) ($payload['message'] ?? ''), 'Too many failed login attempts')) {
                $throttled = true;
                break;
            }
        }

        self::assertTrue($throttled, 'Expected login throttling message was not triggered');
    }

    public function testRegisterIsRateLimitedAfterTooManyAttempts(): void
    {
        $email = sprintf('throttle-register-%s@test.local', bin2hex(random_bytes(4)));

        for ($i = 0; $i < 5; $i++) {
            $this->client->jsonRequest('POST', '/api/auth/register', [
                'email' => $email,
                'password' => 'Strong123!',
                'firstName' => 'Rate',
                'lastName' => 'Limit'
            ]);
            self::assertResponseStatusCodeSame(202);
        }

        $this->client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => 'Strong123!',
            'firstName' => 'Rate',
            'lastName' => 'Limit'
        ]);

        self::assertResponseStatusCodeSame(429);
        $this->assertRateLimitedPayload();
    }

    private function assertRateLimitedPayload(): void
    {
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame('rate_limited', $payload['code'] ?? null);
        self::assertIsString($payload['message'] ?? null);
        self::assertArrayHasKey('details', $payload);
    }

    private function initializeDatabase(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if ($metadata !== []) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        $hasher = $container->get(UserPasswordHasherInterface::class);

        $admin = new User('admin@test.local');
        $admin->setRoles([AppRoles::ADMIN]);
        $admin->setIsVerified(true);
        $admin->setFirstName('Admin');
        $admin->setLastName('Shell');
        $admin->setPasswordHash($hasher->hashPassword($admin, 'Admin123!'));

        $entityManager->persist($admin);
        $entityManager->flush();
    }
}
