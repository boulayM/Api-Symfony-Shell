<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Auth\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\RefreshTokenManager;
use App\Security\UserViewMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'limiter.auth_register')] RateLimiterFactory $registerLimiter
    ): JsonResponse {
        $payload = $this->decodeBody($request);
        $dto = new RegisterRequest();
        $dto->email = (string) ($payload['email'] ?? '');
        $dto->password = (string) ($payload['password'] ?? '');
        $dto->firstName = isset($payload['firstName']) ? (string) $payload['firstName'] : null;
        $dto->lastName = isset($payload['lastName']) ? (string) $payload['lastName'] : null;

        $key = sprintf(
            'register:%s:%s',
            (string) ($request->getClientIp() ?? 'unknown'),
            mb_strtolower(trim($dto->email))
        );
        $limit = $registerLimiter->create($key)->consume(1);
        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            throw new TooManyRequestsHttpException(
                $retryAfter !== null ? max(1, $retryAfter->getTimestamp() - time()) : 60,
                'Too many registration attempts'
            );
        }

        $violations = $validator->validate($dto);
        if (\count($violations) > 0) {
            return new JsonResponse([
                'code' => 'validation_error',
                'message' => 'Invalid payload',
                'details' => (string) $violations,
            ], 400);
        }

        // Neutral anti-enumeration response, whether account exists or not.
        if ($users->findOneByEmail($dto->email) === null) {
            $user = new User($dto->email);
            $user->setFirstName($dto->firstName);
            $user->setLastName($dto->lastName);
            $user->setPasswordHash($passwordHasher->hashPassword($user, $dto->password));
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return new JsonResponse(['message' => 'If this account is eligible, an email verification process may start.'], 202);
    }

    #[Route('/api/auth/verify-email', name: 'api_auth_verify_email', methods: ['GET'])]
    public function verifyEmail(): JsonResponse
    {
        return new JsonResponse(['verified' => true]);
    }

    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(UserViewMapper $mapper): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        return new JsonResponse(['user' => $mapper->toArray($user)]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->buildLogoutResponse('Logged out');
    }

    #[Route('/api/auth/logout-all', name: 'api_auth_logout_all', methods: ['POST'])]
    public function logoutAll(RefreshTokenManager $refreshTokenManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $refreshTokenManager->revokeAllByUserIdentifier($user->getUserIdentifier());
        return $this->buildLogoutResponse('All sessions revoked');
    }

    private function decodeBody(Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);
        return \is_array($payload) ? $payload : [];
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Authentication required');
        }

        return $user;
    }

    private function buildLogoutResponse(string $message): JsonResponse
    {
        $response = new JsonResponse(['message' => $message]);
        $response->headers->setCookie(Cookie::create('BEARER', '')->withExpires(1)->withPath('/')->withHttpOnly(true));
        $response->headers->setCookie(Cookie::create('refresh_token', '')->withExpires(1)->withPath('/')->withHttpOnly(true));
        return $response;
    }
}
