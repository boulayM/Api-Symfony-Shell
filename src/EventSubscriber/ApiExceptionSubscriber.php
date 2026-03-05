<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private bool $debug)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        [$status, $code, $message] = $this->resolveErrorMeta($exception);
        $requestId = $request->attributes->getString(RequestIdSubscriber::ATTR_REQUEST_ID, '');

        $payload = [
            'code' => $code,
            'message' => $message,
            'details' => $this->debug
                ? [
                    'message' => $exception->getMessage(),
                    'requestId' => $requestId !== '' ? $requestId : null,
                ]
                : null,
        ];

        $event->setResponse(new JsonResponse($payload, $status));
    }

    private function resolveErrorMeta(Throwable $exception): array
    {
        if ($exception instanceof ValidationFailedException) {
            return [400, 'validation_error', 'Invalid payload'];
        }

        if ($exception instanceof AuthenticationException) {
            return [401, 'unauthorized', 'Authentication required'];
        }

        if ($exception instanceof AccessDeniedException) {
            return [403, 'access_denied', $exception->getMessage() !== '' ? $exception->getMessage() : 'Access denied'];
        }

        if ($exception instanceof NotFoundHttpException || $exception instanceof ResourceNotFoundException) {
            return [404, 'not_found', 'Resource not found'];
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $code = match ($status) {
                400 => 'bad_request',
                401 => 'unauthorized',
                403 => 'access_denied',
                404 => 'not_found',
                409 => 'conflict',
                422 => 'validation_error',
                429 => 'rate_limited',
                default => 'http_error',
            };

            return [$status, $code, $exception->getMessage() !== '' ? $exception->getMessage() : 'HTTP error'];
        }

        return [500, 'internal_error', 'Internal server error'];
    }
}
