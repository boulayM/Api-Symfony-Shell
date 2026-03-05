<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Security\CsrfDoubleSubmitGuard;
use App\Security\UserViewMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    #[Route('/api/public/health', name: 'api_public_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/api/public/users/me', name: 'api_public_users_me', methods: ['GET'])]
    public function me(UserViewMapper $mapper): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['code' => 'unauthorized', 'message' => 'Authentication required', 'details' => null], 401);
        }

        return new JsonResponse(['user' => $mapper->toArray($user)]);
    }

    #[Route('/api/public/users/me', name: 'api_public_users_me_patch', methods: ['PATCH'])]
    public function patchMe(
        Request $request,
        CsrfDoubleSubmitGuard $csrf,
        EntityManagerInterface $entityManager,
        UserViewMapper $mapper
    ): JsonResponse {
        $csrf->assertRequestIsValid($request);
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['code' => 'unauthorized', 'message' => 'Authentication required', 'details' => null], 401);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (is_array($payload)) {
            if (array_key_exists('firstName', $payload)) {
                $user->setFirstName($payload['firstName'] !== null ? (string) $payload['firstName'] : null);
            }
            if (array_key_exists('lastName', $payload)) {
                $user->setLastName($payload['lastName'] !== null ? (string) $payload['lastName'] : null);
            }
        }

        $entityManager->flush();

        return new JsonResponse(['updated' => true, 'user' => $mapper->toArray($user)]);
    }
}
