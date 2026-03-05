<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Security\AppRoles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User('admin@test.local');
        $admin->setRoles([AppRoles::ADMIN]);
        $admin->setIsVerified(true);
        $admin->setFirstName('Admin');
        $admin->setLastName('Shell');
        $admin->setPasswordHash($this->passwordHasher->hashPassword($admin, 'Admin123!'));

        $user = new User('user@test.local');
        $user->setRoles([AppRoles::USER]);
        $user->setIsVerified(true);
        $user->setFirstName('User');
        $user->setLastName('Shell');
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, 'User123!'));

        $manager->persist($admin);
        $manager->persist($user);
        $manager->flush();
    }
}
