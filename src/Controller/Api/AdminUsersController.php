<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Auth\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\AuditLogger;
use App\Security\CsrfDoubleSubmitGuard;
use App\Security\Permission;
use App\Security\UserViewMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AdminUsersController extends AbstractController
{
    #[Route('/api/admin/users', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(Request $request, UserRepository $users, UserViewMapper $mapper): JsonResponse
    {
        $this->denyAccessUnlessGranted(Permission::USERS_READ);

        $page = $this->parsePositiveInt($request->query->get('page', '1'), 'page');
        $limit = min(100, $this->parsePositiveInt($request->query->get('limit', '10'), 'limit'));
        $q = $this->normalizeNullableString($request->query->get('q'));
        $role = $this->normalizeNullableString($request->query->get('role'));
        $isVerified = $this->parseNullableBool($request->query->get('isVerified'));
        $sort = $this->parseAllowedValue(
            $request->query->get('sort', 'id'),
            ['id', 'email', 'createdAt', 'firstName', 'lastName', 'isVerified'],
            'sort'
        );
        $order = $this->parseAllowedValue(
            strtoupper((string) $request->query->get('order', 'DESC')),
            ['ASC', 'DESC'],
            'order'
        );

        $result = $users->searchPaginated($q, $role, $isVerified, $page, $limit, $sort, $order);
        $data = $result['items'];
        $total = $result['total'];

        return new JsonResponse([
            'data' => array_map(fn (User $user): array => $mapper->toArray($user), $data),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ]);
    }

    #[Route('/api/admin/users/export', name: 'api_admin_users_export', methods: ['GET'])]
    public function export(UserRepository $users, AuditLogger $auditLogger): StreamedResponse
    {
        $this->denyAccessUnlessGranted(Permission::USERS_READ);
        $auditLogger->log(
            'users.export',
            $this->getUser(),
            ['route' => 'api_admin_users_export']
        );

        $response = new StreamedResponse(function () use ($users): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['id', 'email', 'roles', 'isVerified', 'firstName', 'lastName', 'createdAt'], ',', '"', '\\');

            foreach ($users->findBy([], ['id' => 'ASC']) as $user) {
                \assert($user instanceof User);
                fputcsv($output, [
                    (string) ($user->getId() ?? ''),
                    $user->getEmail(),
                    json_encode($user->getRoles(), JSON_UNESCAPED_SLASHES),
                    $user->isVerified() ? '1' : '0',
                    $user->getFirstName() ?? '',
                    $user->getLastName() ?? '',
                    $user->getCreatedAt()->format(DATE_ATOM),
                ], ',', '"', '\\');
            }

            fclose($output);
        });

        $filename = sprintf('users-export-%s.csv', (new \DateTimeImmutable())->format('Ymd-His'));
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    #[Route('/api/admin/users/register', name: 'api_admin_users_register', methods: ['POST'])]
    public function register(
        Request $request,
        CsrfDoubleSubmitGuard $csrf,
        ValidatorInterface $validator,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserViewMapper $mapper,
        AuditLogger $auditLogger
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Permission::USERS_CREATE);
        $csrf->assertRequestIsValid($request);

        $payload = $this->decodeBody($request);
        $dto = new RegisterRequest();
        $dto->email = (string) ($payload['email'] ?? '');
        $dto->password = (string) ($payload['password'] ?? '');
        $dto->firstName = isset($payload['firstName']) ? (string) $payload['firstName'] : null;
        $dto->lastName = isset($payload['lastName']) ? (string) $payload['lastName'] : null;

        $violations = $validator->validate($dto);
        if (\count($violations) > 0) {
            return new JsonResponse(['code' => 'validation_error', 'message' => 'Invalid payload', 'details' => (string) $violations], 400);
        }

        if ($users->findOneByEmail($dto->email) !== null) {
            return new JsonResponse(['code' => 'conflict', 'message' => 'Email already exists', 'details' => null], 409);
        }

        $user = new User($dto->email);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setPasswordHash($passwordHasher->hashPassword($user, $dto->password));
        $entityManager->persist($user);
        $entityManager->flush();
        $auditLogger->log(
            'users.register',
            $this->getUser(),
            ['userId' => $user->getId(), 'email' => $user->getEmail()]
        );

        return new JsonResponse(['message' => 'User created', 'data' => $mapper->toArray($user)], 201);
    }

    #[Route('/api/admin/users/{id}', name: 'api_admin_users_patch', methods: ['PATCH'])]
    public function patch(
        int $id,
        Request $request,
        CsrfDoubleSubmitGuard $csrf,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        UserViewMapper $mapper,
        AuditLogger $auditLogger
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Permission::USERS_UPDATE);
        $csrf->assertRequestIsValid($request);
        $user = $users->find($id);
        if (!$user instanceof User) {
            return new JsonResponse(['code' => 'not_found', 'message' => 'User not found', 'details' => null], 404);
        }

        $payload = $this->decodeBody($request);
        if (\array_key_exists('firstName', $payload)) {
            $user->setFirstName($payload['firstName'] !== null ? (string) $payload['firstName'] : null);
        }
        if (\array_key_exists('lastName', $payload)) {
            $user->setLastName($payload['lastName'] !== null ? (string) $payload['lastName'] : null);
        }
        if (isset($payload['roles']) && \is_array($payload['roles'])) {
            $user->setRoles(array_values(array_filter(array_map('strval', $payload['roles']))));
        }
        if (\array_key_exists('isVerified', $payload)) {
            $user->setIsVerified((bool) $payload['isVerified']);
        }

        $entityManager->flush();
        $auditLogger->log(
            'users.update',
            $this->getUser(),
            ['userId' => $user->getId()]
        );

        return new JsonResponse(['updated' => true, 'data' => $mapper->toArray($user)]);
    }

    #[Route('/api/admin/users/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        Request $request,
        CsrfDoubleSubmitGuard $csrf,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        AuditLogger $auditLogger
    ): JsonResponse {
        $this->denyAccessUnlessGranted(Permission::USERS_DELETE);
        $csrf->assertRequestIsValid($request);
        $user = $users->find($id);
        if (!$user instanceof User) {
            return new JsonResponse(['code' => 'not_found', 'message' => 'User not found', 'details' => null], 404);
        }

        $deletedUserId = $user->getId();
        $entityManager->remove($user);
        $entityManager->flush();
        $auditLogger->log(
            'users.delete',
            $this->getUser(),
            ['userId' => $deletedUserId]
        );
        return new JsonResponse(['id' => $id, 'deleted' => true]);
    }

    #[Route('/api/admin/users/me', name: 'api_admin_users_me', methods: ['GET'])]
    public function me(UserViewMapper $mapper): JsonResponse
    {
        $this->denyAccessUnlessGranted(Permission::USERS_READ);
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['code' => 'unauthorized', 'message' => 'Authentication required', 'details' => null], 401);
        }

        return new JsonResponse(['user' => $mapper->toArray($user)]);
    }

    private function decodeBody(Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);
        return \is_array($payload) ? $payload : [];
    }

    private function parsePositiveInt(mixed $value, string $name): int
    {
        if (!is_numeric($value) || (int) $value < 1) {
            throw new BadRequestHttpException(sprintf('Invalid query parameter "%s"', $name));
        }

        return (int) $value;
    }

    private function parseAllowedValue(mixed $value, array $allowed, string $name): string
    {
        $stringValue = (string) $value;
        if (!in_array($stringValue, $allowed, true)) {
            throw new BadRequestHttpException(sprintf('Invalid query parameter "%s"', $name));
        }

        return $stringValue;
    }

    private function parseNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtolower((string) $value);
        return match ($normalized) {
            '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => throw new BadRequestHttpException('Invalid query parameter "isVerified"'),
        };
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }
}
