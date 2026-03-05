# API Contract (Socle Symfony)

## Endpoints garantis

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

## Invariants securite

- Access token en cookie HttpOnly.
- Refresh token stocke en base.
- Rotation refresh + invalidation.
- CSRF requis pour mutations protegees.
- Erreurs masquees en production.
- Controle d acces par permissions (voter) sur endpoints admin sensibles.

## Contrat register

### Public register

- Route: `POST /api/auth/register`
- Cible: onboarding public
- Reponse neutre anti-enumeration
- Interdiction de creer un role privilegie via endpoint public

### Admin register

- Route: `POST /api/admin/users/register`
- Cible: creation interne via RBAC
- Auth + permission `users.create` + CSRF
- Doublon email: `409 CONFLICT`

## Contrat payload (projets adaptes)

- DTO/serializer explicite obligatoire pour chaque entite exposee.
- Interdiction des sorties ORM brutes dans les reponses API.
- Politique whitelist: seuls les champs mappes sont exposes.
- Champs sensibles interdits par defaut (`passwordHash`, tokens, secrets, cles internes, flags techniques non metier).
- Toute evolution de payload doit inclure:
  - mise a jour `docs/swagger.yaml`,
  - mise a jour tests de contrat,
  - note de compatibilite (breaking/non-breaking).

## Versioning et compatibilite

- Non-breaking: ajout de champ optionnel documente.
- Breaking: suppression/renommage/changement de type d un champ.
- Tout breaking change requiert:
  - version API explicite (ou route de migration documentee),
  - plan de transition client,
  - validation contractuelle avant release.

## Format erreur

Reponse JSON normalisee:

- `code`
- `message`
- `details` (uniquement hors prod)
