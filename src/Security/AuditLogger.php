<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AuditLogger
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function log(string $action, UserInterface|string|null $actor = null, array $context = []): void
    {
        $actorIdentifier = $this->resolveActor($actor);
        $log = new AuditLog($action, $actorIdentifier, $context);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function resolveActor(UserInterface|string|null $actor): string
    {
        if ($actor instanceof UserInterface) {
            return $actor->getUserIdentifier();
        }

        if (is_string($actor) && $actor !== '') {
            return $actor;
        }

        return 'anonymous';
    }
}

