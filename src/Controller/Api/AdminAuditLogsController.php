<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\AuditLog;
use App\Repository\AuditLogRepository;
use App\Security\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AdminAuditLogsController extends AbstractController
{
    #[Route('/api/admin/audit-logs', name: 'api_admin_audit_logs_list', methods: ['GET'])]
    public function list(Request $request, AuditLogRepository $auditLogs): JsonResponse
    {
        $this->denyAccessUnlessGranted(Permission::AUDIT_READ);

        $page = $this->parsePositiveInt($request->query->get('page', '1'), 'page');
        $limit = min(100, $this->parsePositiveInt($request->query->get('limit', '10'), 'limit'));
        $action = $this->normalizeNullableString($request->query->get('action'));
        $actor = $this->normalizeNullableString($request->query->get('actor'));
        $from = $this->parseNullableDateTime($request->query->get('from'), 'from');
        $to = $this->parseNullableDateTime($request->query->get('to'), 'to');
        $sort = $this->parseAllowedValue(
            $request->query->get('sort', 'id'),
            ['id', 'createdAt', 'action', 'actor'],
            'sort'
        );
        $order = $this->parseAllowedValue(
            strtoupper((string) $request->query->get('order', 'DESC')),
            ['ASC', 'DESC'],
            'order'
        );

        $result = $auditLogs->searchPaginated($action, $actor, $from, $to, $page, $limit, $sort, $order);
        $data = array_map(
            static fn (AuditLog $log): array => [
                'id' => $log->getId(),
                'action' => $log->getAction(),
                'actor' => $log->getActor(),
                'context' => $log->getContext(),
                'createdAt' => $log->getCreatedAt()->format(DATE_ATOM),
            ],
            $result['items']
        );

        return new JsonResponse([
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
        ]);
    }

    #[Route('/api/admin/audit-logs/export', name: 'api_admin_audit_logs_export', methods: ['GET'])]
    public function export(EntityManagerInterface $entityManager, AuditLogger $auditLogger): StreamedResponse
    {
        $this->denyAccessUnlessGranted(Permission::AUDIT_EXPORT);
        $auditLogger->log(
            'audit.export',
            $this->getUser(),
            ['route' => 'api_admin_audit_logs_export']
        );

        $logs = $entityManager->getRepository(AuditLog::class)->findBy([], ['id' => 'ASC']);

        $response = new StreamedResponse(function () use ($logs): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['id', 'action', 'actor', 'context', 'createdAt'], ',', '"', '\\');

            foreach ($logs as $log) {
                \assert($log instanceof AuditLog);
                fputcsv($output, [
                    (string) ($log->getId() ?? ''),
                    $log->getAction(),
                    $log->getActor(),
                    json_encode($log->getContext(), JSON_UNESCAPED_SLASHES),
                    $log->getCreatedAt()->format(DATE_ATOM),
                ], ',', '"', '\\');
            }

            fclose($output);
        });

        $filename = sprintf('audit-logs-export-%s.csv', (new \DateTimeImmutable())->format('Ymd-His'));
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    #[Route('/api/admin/audit-logs/{id}', name: 'api_admin_audit_logs_get', methods: ['GET'])]
    public function getOne(int $id, AuditLogRepository $auditLogs): JsonResponse
    {
        $this->denyAccessUnlessGranted(Permission::AUDIT_READ);
        $log = $auditLogs->find($id);
        if (!$log instanceof AuditLog) {
            return new JsonResponse(['code' => 'not_found', 'message' => 'Audit log not found', 'details' => null], 404);
        }

        return new JsonResponse([
            'id' => $log->getId(),
            'action' => $log->getAction(),
            'actor' => $log->getActor(),
            'context' => $log->getContext(),
            'createdAt' => $log->getCreatedAt()->format(DATE_ATOM),
        ]);
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

    private function parseNullableDateTime(mixed $value, string $name): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new BadRequestHttpException(sprintf('Invalid query parameter "%s"', $name));
        }
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
