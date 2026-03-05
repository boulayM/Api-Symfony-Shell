<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Security\AppRoles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminListFiltersTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initializeDatabase();

        $this->loginAs('admin@test.local', 'Admin123!');
    }

    public function testUsersListSupportsFiltersAndSorting(): void
    {
        $this->client->request('GET', '/api/admin/users?q=admin&sort=email&order=ASC&page=1&limit=5');
        self::assertResponseStatusCodeSame(200);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('data', $payload);
        self::assertArrayHasKey('page', $payload);
        self::assertArrayHasKey('limit', $payload);
        self::assertArrayHasKey('total', $payload);
        self::assertGreaterThanOrEqual(1, $payload['total']);
        self::assertStringContainsString('admin', strtolower((string) $payload['data'][0]['email']));
    }

    public function testUsersListInvalidFilterReturns400(): void
    {
        $this->client->request('GET', '/api/admin/users?isVerified=not-bool');
        self::assertResponseStatusCodeSame(400);
        $this->assertBadRequestPayload();
    }

    public function testAuditLogsListSupportsFiltersAndDateRange(): void
    {
        $from = urlencode((new \DateTimeImmutable('-1 day'))->format(DATE_ATOM));
        $to = urlencode((new \DateTimeImmutable('+1 day'))->format(DATE_ATOM));

        $this->client->request('GET', "/api/admin/audit-logs?action=user.login&actor=admin&from={$from}&to={$to}&sort=createdAt&order=DESC&page=1&limit=10");
        self::assertResponseStatusCodeSame(200);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertGreaterThanOrEqual(1, $payload['total']);
        self::assertSame('user.login', $payload['data'][0]['action']);
    }

    public function testAuditLogsListInvalidDateReturns400(): void
    {
        $this->client->request('GET', '/api/admin/audit-logs?from=invalid-date');
        self::assertResponseStatusCodeSame(400);
        $this->assertBadRequestPayload();
    }

    private function loginAs(string $email, string $password): void
    {
        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseStatusCodeSame(200);
    }

    private function assertBadRequestPayload(): void
    {
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame('bad_request', $payload['code'] ?? null);
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

        $user = new User('user@test.local');
        $user->setRoles([AppRoles::USER]);
        $user->setIsVerified(false);
        $user->setFirstName('User');
        $user->setLastName('Shell');
        $user->setPasswordHash($hasher->hashPassword($user, 'User123!'));

        $audit1 = new AuditLog('user.login', 'admin@test.local', ['ip' => '127.0.0.1']);
        $audit2 = new AuditLog('users.export', 'admin@test.local', ['count' => 2]);

        $entityManager->persist($admin);
        $entityManager->persist($user);
        $entityManager->persist($audit1);
        $entityManager->persist($audit2);
        $entityManager->flush();
    }
}
