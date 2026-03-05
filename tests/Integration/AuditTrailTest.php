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

final class AuditTrailTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initializeDatabase();
    }

    public function testSensitiveActionsArePersistedInAuditLogs(): void
    {
        $this->loginAs('admin@test.local', 'Admin123!');

        $this->client->request('GET', '/api/csrf');
        self::assertResponseStatusCodeSame(200);
        $csrf = $this->client->getCookieJar()->get('XSRF-TOKEN');
        self::assertNotNull($csrf);
        $csrfToken = $csrf->getValue();

        $email = sprintf('audit-user-%s@test.local', bin2hex(random_bytes(4)));
        $this->client->jsonRequest(
            'POST',
            '/api/admin/users/register',
            ['email' => $email, 'password' => 'Strong123!'],
            ['HTTP_X-CSRF-TOKEN' => $csrfToken]
        );
        self::assertResponseStatusCodeSame(201);
        $registered = json_decode((string) $this->client->getResponse()->getContent(), true);
        $userId = (int) ($registered['data']['id'] ?? 0);
        self::assertGreaterThan(0, $userId);

        $this->client->jsonRequest(
            'PATCH',
            "/api/admin/users/{$userId}",
            ['firstName' => 'Updated'],
            ['HTTP_X-CSRF-TOKEN' => $csrfToken]
        );
        self::assertResponseStatusCodeSame(200);

        $this->client->request('GET', '/api/admin/users/export');
        self::assertResponseStatusCodeSame(200);

        $this->client->request('GET', '/api/admin/audit-logs/export');
        self::assertResponseStatusCodeSame(200);

        $this->client->request('DELETE', "/api/admin/users/{$userId}", server: ['HTTP_X-CSRF-TOKEN' => $csrfToken]);
        self::assertResponseStatusCodeSame(200);

        self::assertGreaterThanOrEqual(1, $this->countAudit('user.login'));
        self::assertGreaterThanOrEqual(1, $this->countAudit('users.register'));
        self::assertGreaterThanOrEqual(1, $this->countAudit('users.update'));
        self::assertGreaterThanOrEqual(1, $this->countAudit('users.delete'));
        self::assertGreaterThanOrEqual(1, $this->countAudit('users.export'));
        self::assertGreaterThanOrEqual(1, $this->countAudit('audit.export'));
    }

    private function countAudit(string $action): int
    {
        $this->client->request('GET', '/api/admin/audit-logs?action=' . urlencode($action));
        self::assertResponseStatusCodeSame(200);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        return (int) ($payload['total'] ?? 0);
    }

    private function loginAs(string $email, string $password): void
    {
        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseStatusCodeSame(200);
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
