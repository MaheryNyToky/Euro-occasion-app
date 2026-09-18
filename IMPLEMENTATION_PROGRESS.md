# Avancement de l'implémentation

Suivi des étapes d'implémentation de l'application selon `docs/ARCHITECTURE.md` et `docs/ROADMAP.md`.

---

## Étape
Phase 0 — Fondations, initialisation du dépôt et structure monorepo

## Statut
En cours

## Modifications
- Initialisation de git en local
- Création du `.gitignore` racine monorepo (support macOS, IDEs, PHP/Composer/Laravel, Dart/Flutter, Docker, logs, secrets)
- Création de l'arborescence des dossiers conforme à `docs/ARCHITECTURE.md` section 4 :
  - `apps/api`
  - `apps/client`
  - `packages/api_contract`
  - `packages/design_system`
  - `packages/tooling`
  - `database/migrations`, `database/seeders`, `database/reference`
  - `infra/docker`, `infra/deploy`, `infra/monitoring`
  - `tests/contract`, `tests/e2e`, `tests/fixtures`
  - `.github/workflows`
- Configuration Docker Compose locale dans `infra/docker` (PostgreSQL 16, Redis 7, MinIO S3, Mailpit) avec variables d'environnement d'exemple
- Configuration CI minimale dans `.github/workflows/ci.yml`

## Tests
- Validation de la syntaxe de `infra/docker/docker-compose.yml` (`docker compose config`)
- Vérification de l'intégrité de l'arborescence des dossiers
- Vérification du statut git

## Décisions
- Respect strict de l'arborescence racine spécifiée dans `ARCHITECTURE.md`.
- Inclusion de MinIO pour émuler le stockage objet compatible S3 requis pour la gestion privée des fichiers et pièces jointes.

## Problèmes
Aucun problème bloquant à ce stade.
