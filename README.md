# Api-Symfony-Shell (Socle Core)

Socle API abstrait base sur Symfony 7.3, conçu pour être adapte a des projets metier sans durcir le coeur.

## Stack

- Symfony 7.3 (HttpKernel, Security, Validator, Serializer)
- Doctrine ORM + Migrations + PostgreSQL
- JWT HttpOnly cookie (LexikJWTAuthenticationBundle)
- Refresh token (GesdinetJWTRefreshTokenBundle)
- CSRF double submit cookie
- CORS (NelmioCorsBundle)
- PHPUnit

## prérequis

- PHP 8.2+
- Composer 2.8+
- PostgreSQL

## Env

Le projet utilise deux fichiers:

- `.env` (dev)
- `.env.test` (tests)

## Installation

```bash
composer install
php bin/console lexik:jwt:generate-keypair --overwrite
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
```

## Run

```bash
symfony server:start
```

## Routes core

- `GET /api/health`
- `GET /api/docs`
- `GET /api/docs/swagger.yaml`
- `GET /api/csrf`
- `POST /api/auth/login`
- `POST /api/auth/register`
- `GET /api/auth/verify-email`
- `GET /api/auth/me`
- `POST /api/auth/refresh`
- `POST /api/auth/logout`
- `POST /api/auth/logout-all`
- `GET /api/admin/users`
- `GET /api/admin/users/export`
- `POST /api/admin/users/register`
- `PATCH /api/admin/users/{id}`
- `DELETE /api/admin/users/{id}`
- `GET /api/admin/users/me`
- `GET /api/admin/audit-logs`
- `GET /api/admin/audit-logs/export`
- `GET /api/admin/audit-logs/{id}`
- `GET /api/public/health`
- `GET /api/public/users/me`
- `PATCH /api/public/users/me`

## Scripts

- `composer check:encoding`
- `composer preflight:dev`
- `composer preflight:test`
- `composer lint`
- `composer test`
- `composer test:contract`
- `composer check:openapi-routes`
- `composer check:security`
- `composer release:check`

## Comptes fixtures

- `admin@test.local` / `Admin123!`
- `user@test.local` / `User123!`

## Documentation

- `docs/playbook.md`
- `docs/CONTRACT.md`
- `docs/release-checklist.md`
- `docs/swagger.yaml`\r\n- `docs/adaptation-ticket-template.md`

## CI

- .github/workflows/ci.yml: qualité complete (composer release:check).
- .github/workflows/security.yml: audit sécurité des dépendances (composer check:security).

## Docker

démarrage stack locale:

```bash
docker compose up -d --build
```

Bootstrap depuis le conteneur PHP:

```bash
docker compose exec php composer install
docker compose exec php php bin/console lexik:jwt:generate-keypair --overwrite
docker compose exec php php bin/console doctrine:database:create --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console doctrine:fixtures:load -n
```

API disponible sur `http://localhost:8080`.

Tests:

```bash
docker compose exec php composer test
```

## PowerShell

- `composer ps:dev:start`
- `composer ps:db:reset`
- `composer ps:test:http`
