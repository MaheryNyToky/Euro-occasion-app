# Avancement de l'implémentation

Suivi des étapes d'implémentation de l'application selon `docs/ARCHITECTURE.md` et `docs/ROADMAP.md`.

---

## Étape
Phase 0 — Fondations, initialisation du dépôt et structure monorepo

## Statut
Terminé

## Modifications
- Initialisation du dépôt Git local
- Création du `.gitignore` racine adapté (macOS, Laravel, Flutter, Docker, IDEs, logs, secrets)
- Création de l'arborescence des dossiers conforme à `docs/ARCHITECTURE.md` section 4 (`apps/api`, `apps/client`, `packages/`, `database/`, `infra/`, `tests/`, `.github/workflows/`)
- Configuration Docker Compose locale dans `infra/docker` (PostgreSQL 16, Redis 7, MinIO S3, Mailpit) avec `quay.io/minio/minio:latest`
- Validation locale des conteneurs démarrés avec succès
- Workflow CI minimal dans `.github/workflows/ci.yml`

## Tests
- Validation syntaxique et structurelle `docker compose config` : Succès
- Démarrage effectif des conteneurs via Docker : PostgreSQL (5432), Redis (6379), MinIO (9000/9001), Mailpit (1025/8025) tous sains et en cours d'exécution
- Vérification de l'arborescence des dossiers et git status : Propre

## Décisions
- MinIO configuré depuis `quay.io/minio/minio:latest` pour le stockage objet privé S3.
- PostgreSQL 16 et Redis 7 configurés avec healthchecks et volumes persistants.

## Problèmes
- Aucun.

---

## Étape
Phase 1 — Base technique multi-tenant (Backend Laravel & Client Flutter)

## Statut
Terminé

## Modifications
- **Backend Laravel (`apps/api`)** :
  - Installation de Laravel 12 avec PHP 8.4
  - Installation et configuration de Laravel Sanctum (`composer.json`, `config/sanctum.php`, `personal_access_tokens`)
  - Configuration de l'environnement pour PostgreSQL 16 (`DB_CONNECTION=pgsql`), Redis 7 (`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`), Mailpit SMTP et MinIO S3 (`apps/api/.env`)
  - Middleware de traçabilité globale `EnsureRequestId` (`apps/api/app/Http/Middleware/EnsureRequestId.php`)
  - Formatage JSON standardisé avec corrélation `request_id` dans `bootstrap/app.php`
  - Migrations PostgreSQL avec clés primaires UUID :
    - `0001_01_01_000000_create_tenants_table.php`
    - `0001_01_01_000001_create_users_table.php`
    - `0001_01_01_000002_create_devices_table.php`
    - `0001_01_01_000003_create_audit_events_table.php`
    - `2026_09_18_212659_create_personal_access_tokens_table.php`
  - Modèles Eloquent & Services (`Tenant`, `User`, `Device`, `AuditEvent`, `BelongsToTenant`, `TenantScope`, `TenantContext`, `AuditService`)
  - Endpoint `/api/v1/health` avec vérification de PostgreSQL et Redis
- **Client Flutter (`apps/client`)** :
  - Application multiplateforme Windows, Android, Web initialisée
  - Architecture feature-first configurée (`app_config.dart`)
- **Tests** :
  - `HealthCheckTest`, `TenantIsolationTest`, `AuditEventTest` (6 tests, 27 assertions : tous validés sur PostgreSQL)
  - `flutter test` et `flutter analyze` réussis sans avertissement

## Tests
- PHPUnit / PostgreSQL : 6/6 passés.
- Flutter : 1/1 passé, 0 analyse issue.

## Décisions
- Clés primaires UUID généralisées.
- Interdiction stricte de mise à jour et suppression des événements d'audit (append-only immuable).

## Problèmes
- Aucun.

---

## Étape
Phase 2 — Authentification, sessions d'appareils et permissions (RBAC & Périmètres)

## Statut
Terminé

## Modifications
- **Schéma RBAC PostgreSQL (`database/migrations/0001_01_01_000004_create_rbac_tables.php`)** :
  - Table `permissions` (UUID, nom, code unique, catégorie, description)
  - Table `roles` (UUID, tenant_id nullable, nom, code unique par tenant, is_system)
  - Table pivot `role_permissions` (role_id, permission_id)
  - Table `user_roles` (UUID, tenant_id, user_id, role_id, `scope_type`, `scope_id`) pour le support des portées de données
- **Modèles Eloquent & Autorisation** :
  - Modèles `Permission`, `Role`, `UserRole`
  - Méthodes sur `User` : `assignRole()`, `hasRole()`, `hasPermissionTo($permission, $scopeType, $scopeId)`, `getAllPermissions()`
- **Sécurité, Middleware & Rate Limiting** :
  - `LoginRequest` avec limitation de débit (5 tentatives par minute par IP/email)
  - `AuthenticateWithTenant` middleware validant l'état actif de l'utilisateur, l'état actif du tenant, initialisant le `TenantContext`, et bloquant tout appareil révoqué (`is_revoked === true`)
  - `CheckPermission` middleware pour protéger les routes avec vérification des permissions et portées de périmètre
  - Gestion des exceptions dans `bootstrap/app.php` (401, 403, 404, 422, 500 corrélées par `request_id`)
  - Résolution robuste et sécurisée de `device_id` en UUID dans `AuditService`
- **Contrôleurs et Routes API** :
  - `AuthController` (`login`, `refresh`, `logout`, `me`) avec journalisation d'audit automatique (`auth.login`, `auth.logout`) et enregistrement d'appareil
  - `DeviceController` (`index`, `destroy`) avec révocation d'appareil et invalidation de ses jetons d'accès
  - Routes exposées sous `/api/v1/auth/*` et `/api/v1/devices`
- **Tests** :
  - `AuthTest` : 7 tests (login réussi avec terminal, rejet mauvais identifiants/tenant/statut, me, refresh de token, révocation à la déconnexion)
  - `DeviceManagementTest` : 1 test complet (liste des appareils, révocation, blocage 403 d'un appareil révoqué avec audit)
  - `PermissionTest` : 2 tests complets (permission globale, protection permission sensible, permission restreinte à un scope entrepôt avec refus sur un autre entrepôt)

## Tests
- PHPUnit / PostgreSQL (`eurocasion_testing`) : **16 tests, 89 assertions, 100% passés**.
- Flutter (`apps/client`) : `flutter test` réussi, `flutter analyze` sans avertissement (`No issues found!`).

## Décisions
- Résolution du `X-Device-Id` textuel vers l'UUID `id` dans la table `devices` pour garantir l'intégrité référentielle stricte de PostgreSQL.
- Support des portées de données (`scope_type` et `scope_id`) dans `UserRole` pour restreindre facilement les rôles à un site, un entrepôt ou une caisse, comme requis dans `ARCHITECTURE.md`.
- Réinitialisation systématique du `TenantContext` dans `TestCase::tearDown()` pour garantir l'indépendance totale des tests unitaires.

## Problèmes
- Aucun problème bloquant.
