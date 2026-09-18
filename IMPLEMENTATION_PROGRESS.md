# Avancement de l'implémentation

Suivi des étapes d'implémentation de l'application selon `docs/ARCHITECTURE.md` et `docs/ROADMAP.md`.

---

## Étape
Phase 0 — Fondations, initialisation du dépôt et structure monorepo

## Statut
Terminé

## Modifications
- Initialisation du dépôt Git local et push sur GitHub
- Création du `.gitignore` racine adapté
- Création de l'arborescence des dossiers conforme à `docs/ARCHITECTURE.md`
- Configuration Docker Compose locale dans `infra/docker` (PostgreSQL 16, Redis 7, MinIO S3, Mailpit)
- Workflow CI minimal dans `.github/workflows/ci.yml`

## Tests
- `docker compose config` et démarrage sain des 4 conteneurs.

## Problèmes
- Aucun.

---

## Étape
Phase 1 — Base technique multi-tenant (Backend Laravel & Client Flutter)

## Statut
Terminé

## Modifications
- Backend Laravel 12 configuré avec PostgreSQL 16 et Redis 7
- Middleware `EnsureRequestId` pour traçabilité HTTP avec en-tête `X-Request-Id`
- Migrations PostgreSQL UUID pour `tenants`, `users`, `devices`, `audit_events`
- Modèles `Tenant`, `User`, `Device`, `AuditEvent` avec `BelongsToTenant` et `TenantScope`
- Enregistrement append-only strict pour l'audit
- Healthcheck `GET /api/v1/health`
- Client Flutter multiplateforme initialisé

## Tests
- PHPUnit / PostgreSQL : 6/6 passés.
- Flutter : tests et analyse sans avertissement.

## Problèmes
- Aucun.

---

## Étape
Phase 2 — Authentification, sessions d'appareils et permissions (RBAC & Périmètres)

## Statut
Terminé

## Modifications
- Migration RBAC `0001_01_01_000004_create_rbac_tables.php` (`permissions`, `roles`, `role_permissions`, `user_roles`) avec support des portées (`scope_type`, `scope_id`)
- Modèles `Role`, `Permission`, `UserRole` et méthodes d'autorisation enrichies sur `User`
- Middleware `AuthenticateWithTenant` avec gestion des terminaux révoqués et scoping automatique
- Middleware `CheckPermission` pour vérification fine des droits et portées
- Contrôleurs `AuthController` et `DeviceController`
- Routes d'authentification et de gestion d'appareils sous `/api/v1`

## Tests
- PHPUnit / PostgreSQL : 16/16 passés.

## Problèmes
- Aucun.

---

## Étape
Phase 3 — Catalogue et référentiels (Unités, Catégories, Produits, Variantes, Fournisseurs, Clients)

## Statut
Terminé

## Modifications
- **Migrations PostgreSQL avec clés UUID & contraintes par tenant** :
  - `0001_01_01_000005_create_units_and_conversions_tables.php` (`units`, `unit_conversions`)
  - `0001_01_01_000006_create_catalog_tables.php` (`categories` hiérarchiques avec clé étrangère auto-référencée, `attributes`, `attribute_values`, `products`, `product_variants` avec attributs JSONB)
  - `0001_01_01_000007_create_partners_tables.php` (`suppliers`, `customers`)
- **Modèles Eloquent & Services** :
  - Modèles `Unit`, `UnitConversion`, `Category`, `Attribute`, `AttributeValue`, `Product`, `ProductVariant`, `Supplier`, `Customer`
  - Service mathématique précis `UnitConversionService` (conversions directes et inverses avec respect des précisions décimales)
  - Méthode `$product->archive()` pour archivage logique sécurisé (soft delete sans perte d'historique)
- **Contrôleurs et FormRequests** :
  - `UnitController` : listing et création d'unités et de règles de conversion
  - `CategoryController` : gestion arborescente parent/enfant
  - `ProductController` : listing filtré (catégorie, état, recherche plein texte), création transactionnelle avec variantes, détail, mise à jour et archivage
  - `SupplierController` et `CustomerController` : référentiels partenaires avec suivi des conditions de règlement et limites de crédit
  - Validation fine de l'unicité SKU/Code par tenant (`StoreProductRequest`, `Rule::unique()->where('tenant_id')`)
  - Réinitialisation automatique du `TenantContext` au début de chaque requête dans `EnsureRequestId`
- **Tests** :
  - `UnitConversionTest` : conversion d'unités directes, inverses et gestion des erreurs de conversion manquante
  - `CatalogTest` : arborescence de catégories, création de produit avec variantes, unicité de SKU par tenant (autorisant le même SKU dans deux tenants différents), et archivage
  - `PartnerTest` : CRUD et étanchéité stricte des données partenaires entre tenants

## Tests
- PHPUnit / PostgreSQL (`eurocasion_testing`) : **20 tests, 117 assertions, 100% passés**.
- Flutter (`apps/client`) : `flutter test` réussi, `flutter analyze` sans avertissement (`No issues found!`).

## Décisions
- Réinitialisation systématique de `TenantContext::clear()` dans le middleware `EnsureRequestId` au début de chaque requête HTTP pour garantir une étanchéité parfaite lors des appels séquentiels.
- Intégration de l'état du produit (`state`: `new`, `used`, `refurbished`, `damaged`) directement dans le modèle `Product` pour répondre aux spécificités de gestion d'Eurocasion (occasion / reconditionné / neuf).

## Problèmes
- Aucun.
