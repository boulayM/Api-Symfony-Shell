# Release Checklist (Socle API Symfony)

- [ ] `composer check:encoding` passe
- [ ] `composer lint` passe
- [ ] `composer test` passe
- [ ] `composer check:openapi-routes` passe
- [ ] `docs/swagger.yaml` aligne avec les routes controleurs
- [ ] `README.md` aligne avec le contrat actuel
- [ ] `docs/CONTRACT.md` a jour
- [ ] `docs/playbook.md` a jour
- [ ] Verification roles socle: uniquement `USER` et `ADMIN` par defaut
- [ ] Verification env: aucune operation de test executee sur DB dev