<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class CsrfDoubleSubmitGuard
{
    public function __construct(private string $cookieName)
    {
    }

    public function assertRequestIsValid(Request $request): void
    {
        $headerToken = $request->headers->get('X-CSRF-TOKEN');
        $cookieToken = $request->cookies->get($this->cookieName);

        if ($headerToken === null || $cookieToken === null || !hash_equals($cookieToken, $headerToken)) {
            throw new AccessDeniedHttpException('CSRF token mismatch');
        }
    }
}