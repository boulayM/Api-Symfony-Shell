<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Security\CsrfDoubleSubmitGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CsrfDoubleSubmitGuardTest extends TestCase
{
    public function testAllowsMatchingHeaderAndCookie(): void
    {
        $request = new Request();
        $request->headers->set('X-CSRF-TOKEN', 'token-1');
        $request->cookies->set('XSRF-TOKEN', 'token-1');

        $guard = new CsrfDoubleSubmitGuard('XSRF-TOKEN');
        $guard->assertRequestIsValid($request);

        self::assertTrue(true);
    }

    public function testRejectsMismatchedToken(): void
    {
        $request = new Request();
        $request->headers->set('X-CSRF-TOKEN', 'a');
        $request->cookies->set('XSRF-TOKEN', 'b');

        $guard = new CsrfDoubleSubmitGuard('XSRF-TOKEN');

        $this->expectException(AccessDeniedHttpException::class);
        $guard->assertRequestIsValid($request);
    }
}