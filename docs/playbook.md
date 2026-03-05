# playbook

## Objectif

Ce document guide l'adaptation d un nouveau projet metier au socle API Symfony a partir d un MLD.
Le socle reste générique; le projet adapte porte les choix metier.

## Entrees attendues

- MLD valide (entités, cardinalités, contraintes, statuts metier)
- règles metier prioritaires (MVP)
- matrice d accès (role -> permissions)
- exigences de payload (lecture/écriture, champs sensibles)

## Sorties attendues

- modèle Doctrine (Entity + Repository)
- migrations SQL appliquées
- DTO d entree/sortie + validation Symfony
- endpoints controllers (`/api/public/*`, `/api/admin/*`, `/api/auth/*`)
- sécurité (auth JWT cookie, CSRF, voters/permissions)
- contrat OpenAPI a jour
- tests integration + contrat

## Principes

- Le socle ne contient aucun role metier spécifique; seulement la base (`USER`, `ADMIN`) et permissions abstraites.
- Aucune réponse ORM brute: exposition via DTO/serializer explicites.
- Toute mutation protegee impose auth + CSRF + verification permission.
- Les docs contractuelles et les tests sont la source de verite de l API.

## Workflow d adaptation (MLD -> API)

### Phase 1 - Cadrage MLD

1. Lister les entités du MLD et classer: reference, transactionnelle, sécurité, audit.
2. Identifier les contraintes fortes: unicité, non-null, relation obligatoire, suppression logique/physique.
3. Deriver les cas d usage API MVP: create/read/update/delete/search/export.
4. Deriver les permissions par endpoint (`users.read`, `users.update`, etc.).

Livrables:

- tableau entité -> endpoint -> permission
- liste des champs sensibles non exposables

### Phase 2 - ORM Doctrine

1. créer/adapter les `Entity` avec types, relations et contraintes.
2. Ajouter les `Repository` avec requêtes de recherche/pagination/tri.
3. générer migration puis appliquer.

Commandes type:

```bash
php bin/console make:entity
php bin/console make:migration
php bin/console doctrine:migrations:migrate -n
```

règles:

- modeler les contraintes metier au niveau DB (indexes uniques, FK, nullable).
- éviter la logique metier complexe dans les controllers.

### Phase 3 - DTO + Validation + Serialization

1. créer DTO d input (create/update/filter) et DTO d output.
2. Appliquer la validation Symfony (`Assert\*`) au bon niveau.
3. Mapper explicitement Entity <-> DTO (pas de sortie brute).
4. Verifier la politique whitelist des champs exposes.

règles:

- interdire en sortie: hash, tokens, secrets, flags techniques non metier.
- versionner toute evolution de payload (breaking/non-breaking).

### Phase 4 - Controllers (endpoints)

1. créer endpoints publics/admin selon le besoin metier.
2. Normaliser pagination/filtres/tri (`page`, `limit`, `sort`, `order`).
3. Utiliser format erreur standard `{code, message, details}`.
4. Ajouter export CSV si besoin metier.

règles:

- endpoints admin proteges par permission explicite.
- endpoints publics limites aux operations autorisées par contrat.

### Phase 5 - sécurité

1. Conserver la base: JWT cookie HttpOnly + refresh + CSRF.
2. étendre permissions via voter (`PermissionVoter`).
3. Configurer rate limiting pour login/register/actions sensibles.
4. Verifier que les erreurs prod ne divulguent pas d infos internes.

règles:

- toute mutation authentifiée requiert token CSRF valide.
- aucune elevation de privilege via endpoint public.

### Phase 6 - OpenAPI et contrat

1. Mettre a jour `docs/swagger.yaml` a chaque endpoint/payload.
2. Verifier la coherence via `composer check:openapi-routes`.
3. Aligner `docs/CONTRACT.md` si un invariant évolue.

### Phase 7 - Tests et preuve

1. Ajouter tests integration auth/permissions/erreurs/filtres.
2. Ajouter tests contrat payloads critiques.
3. Ajouter tests de non regression sécurité (RBAC, CSRF, throttling).

Commandes:

```bash
composer test
composer test:contract
composer release:check
```

## Mapping standard MLD -> artefacts Symfony

- table MLD -> `src/Entity/*`
- relation MLD -> attribut Doctrine (`ManyToOne`, `OneToMany`, etc.)
- contrainte MLD -> validation + contrainte DB
- processus metier -> service applicatif (si logique multi-étapes)
- endpoint metier -> controller + DTO + test integration
- permission metier -> constante permission + voter + tests RBAC

## Checklist Definition of Done

- `composer check:encoding` OK
- `composer lint` OK
- `composer test` OK
- `composer test:contract` OK
- `composer check:openapi-routes` OK
- `composer check:security` OK
- `docs/swagger.yaml` aligne avec les routes réelles
- `docs/CONTRACT.md` aligne avec invariants de sécurité/payload
- aucun role metier hardcode dans le socle
- aucun champ sensible expose dans les payloads

## Gouvernance adaptation

- Une adaptation MLD est acceptee seulement si code + docs + tests évoluent dans la meme PR.
- Toute divergence entre playbook et implementation est corrigée dans la PR courante.
- En cas de doute, le contrat et les tests priment sur l interpretation informelle.
