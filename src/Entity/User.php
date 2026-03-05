<?php

declare(strict_types=1);

namespace App\Entity;

use App\Security\AppRoles;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [AppRoles::USER];

    #[ORM\Column(type: 'string')]
    private string $passwordHash;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email)
    {
        $this->email = mb_strtolower($email);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = mb_strtolower(trim($email));
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = AppRoles::USER;

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName !== null ? trim($firstName) : null;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName !== null ? trim($lastName) : null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function eraseCredentials(): void
    {
    }
}
