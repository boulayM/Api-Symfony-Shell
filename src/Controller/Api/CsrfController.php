<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CsrfController
{
    public function __construct(private readonly string $cookieName)
    {
    }

    #[Route('/api/csrf', name: 'api_csrf', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $token = bin2hex(random_bytes(32));

        $response = new JsonResponse(['csrfToken' => $token]);
        $response->headers->setCookie(
            Cookie::create($this->cookieName, $token)
                ->withPath('/')
                ->withSecure(false)
                ->withHttpOnly(false)
                ->withSameSite('lax')
        );

        return $response;
    }
}
