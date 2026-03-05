# Ticket Template - Adaptation Projet (MLD + Besoins)

## 1) Contexte

- Projet:
- Sponsor / equipe:
- Date:
- Version cible:
- Probleme metier a resoudre:
- Resultat attendu:

## 2) Expression des besoins

### 2.1 Cas d usage (user stories)

- US-1:
- US-2:
- US-3:

### 2.2 Regles metier

- R-1 (obligatoire):
- R-2 (obligatoire):
- R-3 (exception):

### 2.3 Criteres d acceptation metier

- CA-1:
- CA-2:
- CA-3:

## 3) Entrees techniques

### 3.1 MLD source

- Reference MLD (lien/fichier):
- Entites principales:
- Cardinalites critiques:
- Contraintes critiques (unicite, nullabilite, FK):

### 3.2 Contraintes non-fonctionnelles

- Securite:
- Performance:
- Traçabilite / audit:\r\n- Audit storage: Doctrine | Mongo (choix + justification):\r\n- Conformite / retention:

## 4) Matrice de traçabilite (obligatoire)

| Besoin/Regle | Service applicatif | Endpoint | Permission | DTO (in/out) | Tests |
|---|---|---|---|---|---|
| Ex: creer une commande | `OrderService::create` | `POST /api/public/orders` | `orders.create` | `CreateOrderInput` / `OrderOutput` | Integration + contrat + securite |
|  |  |  |  |  |  |
|  |  |  |  |  |  |

## 5) Contrat API cible

### 5.1 Endpoints a livrer

- `METHOD /api/...`:
- `METHOD /api/...`:
- `METHOD /api/...`:

### 5.2 Contrats payload

- DTO input par endpoint:
- DTO output par endpoint:
- Champs sensibles interdits en sortie:

### 5.3 Regles HTTP et erreurs

- Codes HTTP attendus par endpoint:
- Erreurs metier normalisees: `{code, message, details}`
- Cas de conflit (`409`), validation (`422/400`), auth (`401/403`), throttling (`429`):

## 6) Plan d implementation

### 6.1 ORM / persistence

- Entities Doctrine a creer/modifier:
- Repositories / requetes (filtres, tri, pagination):
- Migration(s) a produire:

### 6.2 Services applicatifs

- Service A:
- Service B:
- Transactions/metiers critiques:

### 6.3 Controllers

- Controllers a creer/modifier:
- Endpoints publics/admin:
- Export CSV (si requis):

### 6.4 Securite

- Permissions a ajouter:
- Voter/Policy impacte:
- CSRF sur mutations protegees:
- Rate limiting a appliquer:

### 6.5 Documentation

- `docs/swagger.yaml` a mettre a jour:
- `docs/CONTRACT.md` impacte (oui/non + details):

## 7) Plan de tests

- Tests integration (happy path):
- Tests integration (cas erreurs metier):
- Tests contrat payload (schemas/champs):
- Tests securite (RBAC/CSRF/throttling):
- Jeux de donnees/fixtures:

## 8) Definition of Done

- [ ] `composer check:encoding` OK
- [ ] `composer lint` OK
- [ ] `composer test` OK
- [ ] `composer test:contract` OK
- [ ] `composer check:openapi-routes` OK
- [ ] `composer check:security` OK
- [ ] `docs/swagger.yaml` aligne
- [ ] `docs/CONTRACT.md` aligne
- [ ] Aucun role metier hardcode dans le socle
- [ ] Aucun champ sensible expose
- [ ] Criteres d acceptation metier valides

## 9) Risques et decisions

- Risques identifies:
- Decisions d architecture:
- Points ouverts:

## 10) Validation finale

- Reviewer technique:
- Reviewer metier:
- Date de validation:
- Go/No-Go:

---

## Mode d emploi rapide

1. Remplir sections 1 a 3 a partir de l expression des besoins et du MLD.
2. Completer la matrice (section 4): aucun endpoint sans service et permission explicites.
3. Deriver l implementation (section 6) et les tests (section 7).
4. Valider la DoD (section 8) avant merge.
5. Garder ce ticket synchronise avec le playbook: `docs/playbook.md`.

