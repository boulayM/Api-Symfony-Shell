<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestIdSubscriber implements EventSubscriberInterface
{
    public const ATTR_REQUEST_ID = '_request_id';
    public const ATTR_REQUEST_START = '_request_start';
    public const HEADER_NAME = 'X-Request-Id';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 200],
            KernelEvents::RESPONSE => ['onKernelResponse', -200],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $incoming = $request->headers->get(self::HEADER_NAME);
        $requestId = (is_string($incoming) && $incoming !== '') ? $incoming : bin2hex(random_bytes(16));

        $request->attributes->set(self::ATTR_REQUEST_ID, $requestId);
        $request->attributes->set(self::ATTR_REQUEST_START, microtime(true));
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

        $requestId = $request->attributes->getString(self::ATTR_REQUEST_ID, '');
        if ($requestId !== '') {
            $event->getResponse()->headers->set(self::HEADER_NAME, $requestId);
        }
    }
}

