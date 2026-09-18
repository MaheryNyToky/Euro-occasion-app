# Architecture de l'application de gestion d'entreprise

## Statut du document

Version initiale proposée pour validation avant implémentation. Ce document décrit la cible technique et fonctionnelle ; il ne constitue pas encore un plan de code généré automatiquement.

## 1. Compréhension du besoin

L'application centralise l'inventaire, les achats et importations, le calcul du coût rendu, les ventes, la facturation, les paiements, la caisse, une comptabilité opérationnelle et les rapports d'une entreprise d'import/export. Elle doit remplacer des fichiers Excel dispersés par un système traçable, multi-sites, multi-utilisateurs et accessible depuis Windows, Android et un navigateur.

Les utilisateurs principaux sont l'administrateur, le responsable stock, l'employé, le comptable et la direction. Les permissions sont configurables par action et par périmètre de données ; les rôles standards sont des profils initiaux, pas des règles codées en dur.

Les workflows critiques sont : réception et mise en stock, transfert ou sortie, inventaire et validation des écarts, commande et importation, allocation des frais et calcul du coût rendu, devis vers commande puis facture, paiement et caisse, génération comptable, synchronisation hors ligne et résolution des conflits.

Les données manipulées comprennent les tenants, utilisateurs, produits, variantes, unités, lots, numéros de série, emplacements, stocks, mouvements, inventaires, fournisseurs, clients, commandes, importations, frais, devises, ventes, factures, paiements, caisses, écritures comptables, pièces jointes, notifications et journaux d'audit.

### Hypothèses et informations à valider

- Le produit commence pour une société, mais chaque donnée métier porte déjà un `tenant_id`.
- La devise comptable initiale est MGA ; les règles fiscales et comptables exactes du pays doivent être confirmées avec le comptable.
- Le serveur central est l'autorité finale. Un appareil hors ligne crée des opérations signées et rejouables ; il ne réécrit pas silencieusement un état central.
- Le mode hors ligne couvre les opérations essentielles, pas nécessairement toute l'administration ni tous les rapports lourds.
- La comptabilité initiale est un sous-système structuré et exportable. La conformité légale finale devra être validée avant production.
- Le premier déploiement vise Windows, Android et navigateur moderne ; iOS et une place de marché SaaS pourront venir plus tard.
- Les volumes cibles, les pays de facturation, les formats fiscaux officiels, les fournisseurs de taux de change, les canaux de notification et les imprimantes à supporter restent à préciser.

## 2. Architecture générale

```text
                 Windows / Android / Web
                         |
              Flutter + présentation commune
          (SQLite/Drift sur appareils installés)
                         |
       HTTPS JSON API + synchronisation idempotente
                         |
          Laravel modulaire : domaine / application
                         |
        PostgreSQL central + stockage objet privé
              |             |              |
        Redis/queue     Worker rapports     Sauvegardes
                         |
          Email / taux de change / monitoring
```

Le système est un monolithe modulaire déployable simplement, avec des workers séparés lorsque les traitements le justifient. Il n'y a pas de microservices au départ : les transactions de stock, de facturation et de comptabilité doivent rester faciles à coordonner. Les frontières de modules et les événements internes permettront une extraction ultérieure si un domaine grossit réellement.

Le client Flutter partage les écrans, modèles de présentation et cas d'usage indépendants de la plateforme. Les adaptateurs pour caméra, fichiers, impression, QR, stockage local et périphériques restent isolés derrière des interfaces. Le Web peut fonctionner comme application connectée ; Windows et Android embarquent une base locale et une file de synchronisation.

Le backend expose une API versionnée. Les contrôleurs valident et autorisent ; les services applicatifs orchestrent les cas d'usage ; les agrégats métier protègent les invariants ; les repositories accèdent aux données ; les événements et jobs traitent les effets secondaires.

Les opérations sensibles sont transactionnelles. Une sortie de stock, une réservation, un paiement ou une écriture comptable ne se résume pas à modifier un total : elle crée un mouvement ou une écriture immuable et met à jour les projections nécessaires dans la même transaction.

Le stockage objet privé conserve les pièces jointes. L'API délivre des URL temporaires après contrôle d'autorisation. Les rapports lourds et exports sont produits en arrière-plan puis téléchargés depuis ce stockage.

## 3. Stack retenue

| Couche | Choix | Raisons | Alternatives écartées au départ |
|---|---|---|---|
| Clients | Flutter/Dart | Un code de présentation et d'intégration pour Windows, Android et Web ; bonne adéquation à une application métier riche et tactile. | React/Electron : deux piles et davantage de packaging ; .NET MAUI : écosystème mobile/web moins homogène pour cette cible. |
| Architecture client | Feature-first, services de domaine, Riverpod, GoRouter | Testabilité, état explicite, navigation centralisée, séparation des fonctionnalités. | BLoC : solide mais plus verbeux ; état global ad hoc : difficile à synchroniser. |
| Base locale | SQLite via Drift | Transactions, requêtes typées, migrations et fonctionnement local. | Hive/NoSQL : moins adapté aux relations, filtres et rapprochements ; base distante seule : incompatible avec le hors-ligne. |
| API | PHP Laravel | Migrations, validation, policies, jobs, notifications, tests et administration adaptés à un ERP. | NestJS : alternative sérieuse mais davantage de briques à assembler ; Django : moins cohérent avec les clients Dart et le choix d'équipe supposé. |
| Base centrale | PostgreSQL | Transactions, contraintes, indexation, JSONB contrôlé, recherche et verrouillage de lignes. | MySQL : possible mais moins intéressant pour certaines contraintes et requêtes ; NoSQL : ne convient pas aux relations et à la comptabilité. |
| ORM | Eloquent avec SQL explicite pour les requêtes critiques | Productivité et migrations Laravel ; les agrégations de stock et rapports peuvent employer des requêtes dédiées. | ORM trop abstrait pour les écritures financières : interdit sur les chemins critiques. |
| Authentification | Laravel Sanctum, access tokens courts et appareils révoquables | API first, sessions d'appareils, révocation simple et intégration Laravel. | OAuth2 complet : à introduire si SSO ou fédération deviennent nécessaires ; JWT autonome : révocation et rotation plus complexes. |
| Validation | Form Requests côté API, règles de domaine, DTOs typés côté client | Trois niveaux complémentaires : forme, autorisation, invariants métier. | Validation uniquement frontend : non fiable. |
| UI | Material 3 personnalisé, design tokens, composants métier partagés | Productivité, accessibilité, cohérence cross-platform. | Kit UI lourd : dépendance inutile avant de connaître les besoins visuels. |
| Jobs | Redis + queue Laravel | Rapports, exports, notifications, calculs de coût et synchronisations longues ne bloquent pas l'API. | Kafka/RabbitMQ : surdimensionnés au démarrage. |
| Fichiers | Stockage objet compatible S3, privé, URLs temporaires | Décorrèle fichiers et base, évolutif et sauvegardable. | Disque local du serveur : fragile avec plusieurs instances. |
| Tests | PHPUnit/Pest backend, tests Flutter, tests API et Playwright ou équivalent E2E | Couvre les invariants, les contrats et les parcours critiques. | Tests uniquement UI : trop lents et insuffisants pour les transactions. |
| Déploiement | Docker Compose en local, VPS/cloud managé en production | Déploiement simple, reproductible et progressif. | Kubernetes : inutile avant plusieurs services et une équipe d'exploitation. |
| CI/CD | GitHub Actions ou équivalent | Lint, tests, migrations contrôlées, builds et artefacts. | Pipeline propriétaire complexe : pas de bénéfice initial. |
| Monitoring | Sentry ou équivalent, logs structurés, métriques applicatives et uptime | Diagnostic réel sans plateforme d'observabilité disproportionnée. | Suite observabilité complète : à ajouter selon le volume. |

Les versions exactes seront figées dans les manifests et mises à jour par revue. Les choix s'appuient sur le support multiplateforme officiel de Flutter et sur les capacités transactionnelles, de verrouillage et de sécurité par ligne de PostgreSQL ; ils ne doivent pas être interprétés comme une promesse de support de tous les périphériques sans matrice de validation.

## 4. Structure du repository

```text
project/
├── apps/
│   ├── client/                 # Application Flutter Windows, Android, Web
│   └── api/                    # Application Laravel et modules backend
├── packages/
│   ├── api_contract/           # Schémas partagés générés ou documentés
│   ├── design_system/          # Tokens et composants Flutter partagés
│   └── tooling/                # Scripts non métier de qualité et génération
├── database/
│   ├── migrations/             # Source du schéma central
│   ├── seeders/                # Données de démonstration uniquement
│   └── reference/              # Plans comptables/configurations importables
├── infra/
│   ├── docker/                 # Développement et services locaux
│   ├── deploy/                 # Configuration non secrète de déploiement
│   └── monitoring/             # Dashboards et alertes
├── docs/
│   ├── ARCHITECTURE.md
│   ├── DATABASE.md
│   ├── API.md
│   ├── SECURITY.md
│   ├── ROADMAP.md
│   ├── DECISIONS.md
│   └── AGENT_GUIDELINES.md
├── tests/
│   ├── contract/
│   ├── e2e/
│   └── fixtures/
└── .github/workflows/
```

Dans `apps/api`, chaque domaine possède ses modèles, actions/services, policies, requests, resources, jobs et tests. Dans `apps/client`, chaque fonctionnalité possède présentation, état, cas d'usage, repository et adaptateurs local/distant. Les utilitaires communs restent petits et doivent avoir un propriétaire clair.

## 5. Modèle de données

Le schéma logique détaillé, les champs, contraintes et index sont dans [DATABASE.md](DATABASE.md). Les principes structurants sont :

- toutes les tables métier sont scindées par `tenant_id` et possèdent des timestamps UTC ;
- les montants utilisent des décimaux exacts, jamais des flottants ; chaque montant conserve sa devise et son taux appliqué ;
- les quantités sont décimales lorsque l'unité le permet, avec une unité de base et des conversions versionnées ;
- les stocks sont des projections calculées à partir de mouvements validés, avec verrouillage transactionnel ;
- les mouvements, factures validées, paiements, écritures et audits ne sont jamais supprimés physiquement ;
- les statuts sont des transitions explicites, validées côté domaine ;
- les références métier et numéros de documents sont uniques dans un tenant, avec gestion de concurrence ;
- les données synchronisées possèdent un identifiant global, une version et une date de modification ; les mutations hors ligne possèdent une clé d'idempotence.

## 6. API

L'API est JSON sous `/api/v1`, avec enveloppe d'erreur stable, pagination curseur pour les listes volumineuses et `Idempotency-Key` pour les commandes rejouables. Les resources ne renvoient que les champs autorisés par rôle et périmètre. Le détail des domaines est dans [API.md](API.md).

Les commandes utilisent des endpoints d'action explicites quand elles déclenchent une transition métier : `POST /receipts/{id}/confirm`, `POST /invoices/{id}/cancel`, `POST /inventory-counts/{id}/post`. Les écrans ne modifient pas directement un total dérivé.

## 7. Authentification et autorisations

L'authentification repose sur un compte utilisateur, un tenant actif, des appareils enregistrés et des tokens Sanctum révocables. Les tokens ont une durée limitée ; le renouvellement fait tourner le token et les appareils perdus peuvent être révoqués. Le MFA est activable pour les administrateurs, comptables et utilisateurs disposant d'actions sensibles.

Le modèle est RBAC configurable, enrichi par des règles de périmètre : tenant, site, entrepôt, caisse et type de donnée. Chaque action applique la permission et la portée sur le serveur. Les permissions `view_cost`, `view_margin`, `approve_stock_adjustment` et `post_accounting_entry` sont distinctes.

## 8. Sécurité

La surface API applique validation serveur, policies, requêtes paramétrées, échappement de sortie, CORS strict, rate limiting et réponses d'erreur sans secrets. Les fichiers sont contrôlés par taille, extension réelle, type MIME, antivirus asynchrone si disponible et stockage privé. Les exports et URLs de fichier sont autorisés objet par objet.

Les actions sensibles demandent permission, justification et parfois approbation. Une facture validée, un paiement posté, un inventaire validé ou un mouvement ne sont pas supprimés ; ils sont annulés ou compensés. Les détails dans [SECURITY.md](SECURITY.md) sont des critères de conception et de recette.

## 9. Performances

Les listes sont paginées, filtrées côté serveur et couvertes par index composés tenant/statut/date et tenant/code. Les écrans utilisent chargement progressif et debounce de recherche. Les rapports, exports, génération PDF, recalculs de coût et notifications sont des jobs.

Le cache est limité aux paramètres et agrégats dont la cohérence peut être explicitement invalidée. Les écritures de stock et de comptabilité ne dépendent jamais d'un cache. Une recherche plein texte ou un moteur dédié ne sera ajouté qu'après mesure ; PostgreSQL suffit pour le premier volume.

## 10. UI / UX

La navigation principale suit les tâches : Tableau de bord, Catalogue, Stock, Achats/Importations, Ventes, Caisse, Comptabilité, Rapports, Administration. La barre globale fournit recherche, scan, centre de notifications, état de synchronisation et compte utilisateur.

Les composants communs sont : tableau paginé, filtres persistants, formulaire avec validation, sélecteur de produit, scanner, timeline d'audit, badge de statut, dialogue d'approbation, import/export et panneau de synchronisation. Chaque écran définit loading, empty, error, offline et succès.

Les écrans Windows/Web privilégient tableaux et raccourcis clavier ; Android privilégie scan, actions courtes, gros contrôles tactiles et capture photo. Les contrastes, focus clavier, labels, alternatives aux couleurs et annonces d'erreurs suivent WCAG AA autant que possible.

## 11. Tests

Les invariants suivants sont obligatoires : aucune sortie non autorisée, stock négatif selon configuration, unicité des séries, conversion d'unités, allocation de frais et coût rendu, réservations, numérotation et immutabilité de facture, paiements partiels, clôture de caisse, génération comptable, séparation des tenants et rejouabilité de synchronisation.

La stratégie complète figure dans [ROADMAP.md](ROADMAP.md) : tests unitaires des règles, intégration PostgreSQL des transactions, tests API de permissions, tests de synchronisation/concurrence, tests Flutter de widgets et états, puis E2E sur les parcours critiques. La couverture chiffrée n'est pas un objectif en soi.

## 12. Observabilité

Les logs sont structurés en JSON avec `request_id`, `tenant_id` non sensible, utilisateur, appareil, domaine, action, résultat et durée. Les secrets, mots de passe, tokens et données personnelles inutiles ne sont jamais logués.

Les métriques initiales sont disponibilité API, erreurs par route, latence p95, jobs en échec, taille de la file de synchronisation, conflits, état des sauvegardes et connexions DB. Sentry ou équivalent suit les exceptions avec contexte filtré. L'audit métier reste dans la base append-only et est exportable pour diagnostic.

## 13. Déploiement

Local : Docker Compose pour PostgreSQL, Redis, stockage objet local et mail de test. Staging : environnement isolé avec données anonymisées. Production : serveur applicatif, worker, PostgreSQL managé ou sauvegardé, stockage objet privé, Redis, reverse proxy HTTPS et monitoring. Les clients sont construits par plateforme avec version affichée.

Les migrations sont exécutées avant activation de la version, de façon rétrocompatible lorsque deux versions peuvent coexister. Les sauvegardes chiffrées quotidiennes et la rotation sont testées par restauration périodique. Un rollback applicatif doit rester possible ; une migration destructrice demande une procédure séparée et une sauvegarde vérifiée.

## 14. Évolutivité

À abstraire dès maintenant : synchronisation, stockage de fichiers, fournisseur de taux, notifications, impression/scanner, résolution de conflits, règles de prix et règles comptables. À garder simples : repositories génériques, bus d'événements externe, moteur de workflow universel et microservices.

Les domaines les plus susceptibles de grossir sont rapports, comptabilité, synchronisation et recherche. Ils possèdent déjà des contrats et jobs isolés, mais restent dans le monolithe jusqu'à preuve de besoin.

## 15. Risques techniques

| Risque | Probabilité | Impact | Mitigation |
|---|---:|---:|---|
| Conflits de stock hors ligne | Haute | Très élevé | Journal d'opérations, idempotence, transactions serveur, conflits explicites, tests de concurrence. |
| Règles comptables/fiscales incomplètes | Haute | Très élevé | Validation par comptable local, règles configurables, période de clôture, exports vérifiables. |
| Coût rendu mal réparti | Moyenne | Élevé | Allocation détaillée et figée par importation, tests par méthode, rapprochement manuel. |
| Vol de données inter-tenant | Faible | Très élevé | Scoping obligatoire, tests négatifs, policies, éventuellement RLS PostgreSQL et revue de sécurité. |
| Perte ou corruption de fichiers | Moyenne | Élevé | Stockage privé redondant, checksum, sauvegardes séparées, test de restauration. |
| Périphériques et imprimantes hétérogènes | Moyenne | Moyen | Adaptateurs, matrice de matériel supporté, fallback manuel. |
| Rapports lents avec croissance | Moyenne | Moyen | Jobs, index, vues matérialisées mesurées, pagination et archivage. |
| Complexité excessive du périmètre initial | Haute | Élevé | MVP par flux, phases indépendantes, aucune abstraction non justifiée. |
| Compte ou appareil compromis | Moyenne | Très élevé | MFA, révocation appareil, limitation, audit et moindre privilège. |

## 16. Architecture Decision Records

Les décisions détaillées sont dans [DECISIONS.md](DECISIONS.md). Les décisions structurantes sont :

- monolithe modulaire avant microservices ;
- Flutter pour les trois cibles ;
- PostgreSQL comme autorité centrale ;
- opérations métier idempotentes pour le hors-ligne ;
- mouvements et écritures immuables avec projections ;
- multi-tenant explicite dès la première migration ;
- RBAC avec périmètres de données ;
- stockage objet privé pour les documents ;
- déploiement Docker/VPS évolutif sans Kubernetes initial.

## 17. Roadmap d'implémentation

La roadmap détaillée, les fichiers/modules concernés et les critères de validation se trouvent dans [ROADMAP.md](ROADMAP.md). L'ordre de dépendance est : fondations, tenant/authentification, catalogue et unités, stock transactionnel, offline/sync, achats/importations, ventes/caisse, comptabilité, rapports, durcissement et production.

## 18. Séparation des tâches Codex / Antigravity

### A — À faire avec Antigravity

Boilerplate contrôlé, écrans CRUD, composants UI répétitifs, formulaires simples, ressources API, documentation de détail, fixtures, exports simples et tests unitaires directs. Ces tâches suivent des contrats déjà validés.

### B — À faire avec Codex

Architecture, modèle de synchronisation, invariants de stock, transactions, coût rendu, comptabilité, permissions multi-tenant, sécurité, migrations sensibles, concurrence, restauration et analyse des bugs difficiles. Ces tâches peuvent rendre les données incohérentes si elles sont mal raisonnées.

### C — Antigravity puis revue Codex

Modules achats/ventes, inventaires collaboratifs, caisse, rapports financiers, workflows d'approbation, intégration fichiers et notifications. Ils sont volumineux mais comportent des règles métier et des effets financiers qui nécessitent une revue des scénarios limites et des permissions.

## Références techniques

- [Flutter — plateformes supportées](https://docs.flutter.dev/reference/supported-platforms)
- [Flutter — support Web](https://docs.flutter.dev/platform-integration/web)
- [PostgreSQL — transactions](https://www.postgresql.org/docs/current/tutorial-transactions.html)
- [PostgreSQL — verrouillage de lignes](https://www.postgresql.org/docs/current/explicit-locking.html)
- [PostgreSQL — Row-Level Security](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
