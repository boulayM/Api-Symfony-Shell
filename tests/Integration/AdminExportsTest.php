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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminExportsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initializeDatabase();
    }

    public function testAdminCanExportUsersAsCsv(): void
    {
        $this->loginAs('admin@test.local', 'Admin123!');

        $this->client->request('GET', '/api/admin/users/export');
        self::assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        self::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));

        $csv = $this->captureResponseContent($response);
        self::assertStringContainsString('id,email,roles,isVerified,firstName,lastName,createdAt', $csv);
        self::assertStringContainsString('admin@test.local', $csv);
    }

    public function testAdminCanExportAuditLogsAsCsv(): void
    {
        $this->loginAs('admin@test.local', 'Admin123!');

        $this->client->request('GET', '/api/admin/audit-logs/export');
        self::assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        self::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));

        $csv = $this->captureResponseContent($response);
        self::assertStringContainsString('id,action,actor,context,createdAt', $csv);
        self::assertStringContainsString('user.login', $csv);
    }

    public function testUserCannotExportUsersCsv(): void
    {
        $this->loginAs('user@test.local', 'User123!');

        $this->client->request('GET', '/api/admin/users/export');
        self::assertResponseStatusCodeSame(403);
    }

    private function loginAs(string $email, string $password): void
    {
        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseStatusCodeSame(200);
    }

    private function captureResponseContent(Response $response): string
    {
        if ($response instanceof StreamedResponse) {
            $callback = $response->getCallback();
            if (is_callable($callback)) {
                ob_start();
                $callback();
                return (string) ob_get_clean();
            }
        }

        return (string) $response->getContent();
    }

    private function initializeDatabase(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();

        foreach ($schemaManager->listTableNames() as $tableName) {
            $connection->executeStatement(sprintf('DROP TABLE IF EXISTS "%s"', $tableName));
        }

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
        $user->setIsVerified(true);
        $user->setFirstName('User');
        $user->setLastName('Shell');
        $user->setPasswordHash($hasher->hashPassword($user, 'User123!'));

        $audit = new AuditLog('user.login', 'admin@test.local', ['ip' => '127.0.0.1']);

        $entityManager->persist($admin);
        $entityManager->persist($user);
        $entityManager->persist($audit);
        $entityManager->flush();
    }
}
