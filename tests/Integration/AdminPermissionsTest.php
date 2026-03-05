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

final class AdminPermissionsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initializeDatabase();
    }

    public function testAdminCanAccessAdminUsersList(): void
    {
        $this->loginAs('admin@test.local', 'Admin123!');

        $this->client->request('GET', '/api/admin/users');
        self::assertResponseStatusCodeSame(200);
    }

    public function testUserCannotAccessAdminUsersList(): void
    {
        $this->loginAs('user@test.local', 'User123!');

        $this->client->request('GET', '/api/admin/users');
        self::assertResponseStatusCodeSame(403);
        $this->assertApiErrorPayload('access_denied');
    }

    public function testAdminRegisterRequiresCsrfToken(): void
    {
        $this->loginAs('admin@test.local', 'Admin123!');

        $this->client->jsonRequest('POST', '/api/admin/users/register', [
            'email' => 'new-user@test.local',
            'password' => 'Strong123!',
        ]);

        self::assertResponseStatusCodeSame(403);
        $this->assertApiErrorPayload('access_denied');
    }

    public function testAdminRegisterDuplicateEmailReturnsConflict(): void
    {
        $this->loginAs('admin@test.local', 'Admin123!');

        $this->client->request('GET', '/api/csrf');
        self::assertResponseStatusCodeSame(200);
        $csrf = $this->client->getCookieJar()->get('XSRF-TOKEN');
        self::assertNotNull($csrf);

        $this->client->jsonRequest(
            'POST',
            '/api/admin/users/register',
            [
                'email' => 'admin@test.local',
                'password' => 'Strong123!',
            ],
            ['HTTP_X-CSRF-TOKEN' => $csrf->getValue()]
        );

        self::assertResponseStatusCodeSame(409);
        $this->assertApiErrorPayload('conflict');
    }

    public function testUnknownApiRouteUsesNormalizedErrorPayload(): void
    {
        $this->client->request('GET', '/api/does-not-exist');
        self::assertResponseStatusCodeSame(404);
        $this->assertApiErrorPayload('not_found');
    }

    private function loginAs(string $email, string $password): void
    {
        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseStatusCodeSame(200);
    }

    private function assertApiErrorPayload(string $expectedCode): void
    {
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame($expectedCode, $payload['code'] ?? null);
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
        $user->setIsVerified(true);
        $user->setFirstName('User');
        $user->setLastName('Shell');
        $user->setPasswordHash($hasher->hashPassword($user, 'User123!'));

        $entityManager->persist($admin);
        $entityManager->persist($user);
        $entityManager->flush();
    }
}
