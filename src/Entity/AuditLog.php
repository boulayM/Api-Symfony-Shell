<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $action;

    #[ORM\Column(type: 'string', length: 120)]
    private string $actor;

    #[ORM\Column(type: 'json')]
    private array $context = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $action, string $actor, array $context = [])
    {
        $this->action = $action;
        $this->actor = $actor;
        $this->context = $context;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getActor(): string
    {
        return $this->actor;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
