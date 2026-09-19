# Avancement de l'implémentation

Suivi des étapes d'implémentation de l'application selon `docs/ARCHITECTURE.md` et `docs/ROADMAP.md`.

---

## Étape
Phase 0 — Fondations, initialisation du dépôt et structure monorepo

## Statut
Terminé (Poussé sur GitHub)

---

## Étape
Phase 1 — Base technique multi-tenant (Backend Laravel & Client Flutter)

## Statut
Terminé (Poussé sur GitHub)

---

## Étape
Phase 2 — Authentification, sessions d'appareils et permissions (RBAC & Périmètres)

## Statut
Terminé (Poussé sur GitHub)

---

## Étape
Phase 3 — Catalogue et référentiels (Unités, Catégories, Produits, Variantes, Fournisseurs, Clients)

## Statut
Terminé (Poussé sur GitHub)

---

## Étape
Phase 3 — Frontend du catalogue, module Stock et connexion Flutter aux APIs Laravel

## Statut
Terminé (Ajustements UX & Stock validés)

## Modifications
- **Client réseau et API** : Mise en place de `ApiClient` avec gestion dynamique de la `baseUrl`, ajout automatique des en-têtes obligatoires (`X-Request-Id` UUID v4, `X-Device-Id`, `Authorization: Bearer <token>`), et normalisation des erreurs.
- **Authentification & Session** :
  - `AuthService` et `UserSession` (stockage de session en mémoire, parsing de l'utilisateur, rôles, permissions et informations du tenant).
  - `LoginScreen` responsive Material 3 avec validation de formulaire, configuration de l'URL API, gestion d'erreur visuelle et pré-remplissage des identifiants de développement.
  - Intégration dans `app.dart` (`EurocasionApp` & `AppShell`) : bascule automatique vers `LoginScreen` si non connecté, affichage dynamique de l'utilisateur connecté et du tenant dans l'en-tête de bureau et la barre latérale, et action de déconnexion fonctionnelle (`AuthService.instance.logout()`).
- **Module Stock (`StockPage`) & Flux d'entrée corrigé** :
  - Déplacement de la création de produit et entrée d'article du catalogue vers le module **Stock** (`StockPage`).
  - **Génération automatique du code SKU** : le SKU n'est plus requis en saisie utilisateur, il est généré automatiquement côté backend avec préfixe fabricant unique (ex: `SKU-APP-9C308B`).
  - **Autocomplétion du nom du produit** : saisie assistée avec suggestions automatiques (modèles de smartphones, ordinateurs, accessoires) et détection intelligente du fabricant.
  - **Champ Fabricant** : remplacement de "référence fabricant" par "Fabricant" (`manufacturer`), avec autocomplétion des marques usuelles.
  - **Catégorie par autocomplétion et saisie libre** : suppression de la liste déroulante rigide, l'utilisateur tape la catégorie au clavier avec autocomplétion des catégories existantes et création dynamique si nouvelle.
  - **Quantité en stock** : suppression du seuil d'alerte inutile, ajout du champ "Quantité reçue en stock", avec enregistrement automatique de la balance de stock (`stock_balances`) et du mouvement de réception (`stock_movements`).
- **Écran Catalogue (`CatalogPage`)** :
  - Consultation des fiches produits avec affichage du Fabricant, de l'état, des variantes, et du stock physique disponible en direct (`withSum('stockBalances as total_on_hand')`).
  - Suppression du bouton d'ajout de produit (redirigé vers Stock).
  - Vues en tableau pour les catégories, unités avec précisions, fournisseurs et clients avec devises et limites de crédit.
- **Données de développement (`DatabaseSeeder.php`)** :
  - Tenant `eurocasion`, compte `admin@eurocasion.mg` (`SecretPass123!`).
  - Référentiel d'unités, catégories et produits avec fabricants et stocks initiaux.
  - Site central et Entrepôt principal (`Magasin Principal`) configurés par défaut.

## Tests
- `flutter analyze` : 0 problème détecté (aucune erreur, aucun avertissement, aucune dépréciation).
- `flutter test` : 4/4 tests passés avec succès (`widget_test.dart`, `catalog_page_test.dart`, `stock_page_test.dart`).
- `php artisan test` : 21/21 tests passés (126 assertions).
- Appels API end-to-end vérifiés via `curl` :
  - `POST /api/v1/products` : création sans SKU avec `manufacturer`, `category_name`, et `quantity` -> SKU généré `SKU-APP-XXXXXX`, stock initial créé (`total_on_hand: 12`).

---

## Étape
Phase 4 — Stock transactionnel connecté (Sites, Entrepôts, Emplacements, Balances, Mouvements atomiques, Séries & Lots)

## Statut
En cours — quatre tickets verticaux livrés

## Tickets livrés

1. **Mouvements atomiques de stock** :
   - Ajout de `POST /api/v1/stock-movements` pour les mouvements `receipt`, `issue` et `transfer`.
   - Transaction atomique avec verrouillage des balances (`lockForUpdate`), contrôle du disponible et respect de `allow_negative_stock`.
   - Validation tenant-aware des produits, variantes, entrepôts et emplacements.
   - Idempotence via `Idempotency-Key` ou `idempotency_key` et audit métier des mouvements créés (`stock.movement_created`).

2. **Contre-opération append-only (Inversion)** :
   - Ajout de `POST /api/v1/stock-movements/{id}/reverse` pour inverser un mouvement existant sans mise à jour ni suppression (ADR-005).
   - Inversion automatique source/destination, débit/crédit et contrôle du stock disponible non négatif.
   - Protection stricte : interdiction de contre-passer deux fois un même mouvement, interdiction d'inverser une contre-opération.
   - Lien direct `reversed_movement_id`, relation `reversal()` / `reversedMovement()`, support de l'idempotence et journalisation d'audit (`stock.movement_reversed`).

3. **Consultation des mouvements et projections de stock** :
   - `GET /api/v1/stock-movements` : liste paginée et filtrée (produit, entrepôt source ou destination, type, acteur, plage de dates). Eager loading ciblé des relations.
   - `GET /api/v1/stock-balances` : projection paginée (produit, entrepôt, variante, emplacement, `available_only`). Champ calculé `available = on_hand - reserved - damaged` inclus dans chaque ligne de réponse.
   - 14 nouveaux tests ciblés PHPUnit (70 assertions) ; suite complète API : 47/47 réussis, 258 assertions.

4. **Raccordement Flutter du stock** :
   - Ajout de `GET /api/v1/warehouses` pour sélectionner un entrepôt actif lors
     d'une réception.
   - `StockPage` crée désormais le produit sans stock initial puis envoie un
     mouvement `receipt` transactionnel avec une clé d'idempotence.
   - Les balances de `GET /api/v1/stock-balances` alimentent le total affiché
     par produit.
   - Ajout d'un onglet **Historique** alimenté par
     `GET /api/v1/stock-movements`.
   - Modèles et `StockService` Flutter ajoutés pour entrepôts, balances et
     mouvements.

## Tests du raccordement

- `flutter analyze` : aucun problème.
- `flutter test test/stock_page_test.dart` : 1/1 réussi.
- `php artisan test tests/Feature/StockQueryTest.php tests/Feature/StockMovementTest.php` : 26/26 réussis, 130 assertions.

## Prochain ticket

Opérations de stock avancées — Phase 4 : gérer les emplacements, lots et séries
dans le flux de réception, en commençant par la sélection d'un emplacement et
la validation des numéros de série lorsque `requires_serial_number` est actif.
