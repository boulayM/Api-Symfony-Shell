<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Security\AuditLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final readonly class LoginAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getPathInfo() !== '/api/auth/login') {
            return;
        }

        $this->auditLogger->log(
            'user.login',
            $event->getUser(),
            [
                'ip' => $request->getClientIp(),
                'route' => $request->attributes->get('_route'),
            ]
        );
    }
}

