<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RefreshTokenManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function revokeAllByUserIdentifier(string $userIdentifier): void
    {
        $tokens = $this->entityManager->getRepository(RefreshToken::class)->findBy(['username' => $userIdentifier]);

        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        $this->entityManager->flush();
    }
}