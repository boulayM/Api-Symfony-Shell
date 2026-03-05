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

final class AuthFlowTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->initializeDatabase();
    }

    public function testFullAuthCookieCsrfLogoutFlow(): void
    {
        $this->client->request('GET', '/api/csrf');
        self::assertResponseIsSuccessful();

        $csrfCookie = $this->client->getCookieJar()->get('XSRF-TOKEN');
        self::assertNotNull($csrfCookie);

        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@test.local',
            'password' => 'Admin123!',
        ]);
        self::assertResponseStatusCodeSame(200);
        self::assertNotNull($this->client->getCookieJar()->get('BEARER'));
        self::assertNotNull($this->client->getCookieJar()->get('refresh_token'));

        $this->client->request('GET', '/api/auth/me');
        self::assertResponseStatusCodeSame(200);
        $me = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('admin@test.local', $me['user']['email'] ?? null);

        $this->client->request('POST', '/api/auth/refresh');
        self::assertResponseStatusCodeSame(200);

        $this->client->jsonRequest('PATCH', '/api/public/users/me', ['firstName' => 'NoCsrf']);
        self::assertResponseStatusCodeSame(403);

        $this->client->jsonRequest(
            'PATCH',
            '/api/public/users/me',
            ['firstName' => 'AdminUpdated', 'lastName' => 'ShellUpdated'],
            ['HTTP_X-CSRF-TOKEN' => $csrfCookie->getValue()]
        );
        self::assertResponseStatusCodeSame(200);

        $this->client->request('POST', '/api/auth/logout-all');
        self::assertResponseStatusCodeSame(200);

        $this->client->request('GET', '/api/auth/me');
        self::assertResponseStatusCodeSame(401);
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

        $admin = new User('admin@test.local');
        $admin->setRoles([AppRoles::ADMIN]);
        $admin->setIsVerified(true);
        $admin->setFirstName('Admin');
        $admin->setLastName('Shell');

        $hasher = $container->get(UserPasswordHasherInterface::class);
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
