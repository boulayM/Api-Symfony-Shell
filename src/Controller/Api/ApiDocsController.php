<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ApiDocsController
{
    #[Route('/api/docs', name: 'api_docs_ui', methods: ['GET'])]
    public function ui(): Response
    {
        $html = <<<'HTML'
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <style>
      body { margin: 0; background: #fafafa; }
      #swagger-ui { max-width: 1200px; margin: 0 auto; }
    </style>
  </head>
  <body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
      window.ui = SwaggerUIBundle({
        url: '/api/docs/swagger.yaml',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [SwaggerUIBundle.presets.apis],
      });
    </script>
  </body>
</html>
HTML;

        return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route('/api/docs/swagger.yaml', name: 'api_docs_spec', methods: ['GET'])]
    public function spec(): Response
    {
        $path = dirname(__DIR__, 3) . '/docs/swagger.yaml';
        if (!is_file($path)) {
            throw new NotFoundHttpException('OpenAPI spec not found');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'application/yaml; charset=UTF-8');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'swagger.yaml');

        return $response;
    }
}
