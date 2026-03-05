<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class ApiRequestLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private Security $security
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $start = $request->attributes->get(RequestIdSubscriber::ATTR_REQUEST_START);
        $durationMs = is_float($start) ? (int) round((microtime(true) - $start) * 1000) : null;
        $requestId = $request->attributes->getString(RequestIdSubscriber::ATTR_REQUEST_ID, '');

        $user = $this->security->getUser();
        $userId = $user instanceof UserInterface ? $user->getUserIdentifier() : null;

        $this->logger->info('api_request', [
            'request_id' => $requestId !== '' ? $requestId : null,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'route' => $request->attributes->get('_route'),
            'status' => $event->getResponse()->getStatusCode(),
            'duration_ms' => $durationMs,
            'user_id' => $userId,
        ]);
    }
}

