# Architecture Decision Records

### ADR-001 — Monolithe modulaire au démarrage

Contexte : beaucoup de domaines transactionnels doivent partager stock, vente, caisse et comptabilité.

Décision : un backend Laravel déployable avec modules et workers séparés, sans microservices initiaux.

Alternatives : microservices, serverless par domaine.

Pourquoi cette décision : transactions et débogage restent cohérents, l'infrastructure est adaptée à une petite équipe.

Conséquences : frontières internes strictes et contrats d'événements sont nécessaires pour permettre une extraction future.

### ADR-002 — Flutter pour Windows, Android et Web

Contexte : les mêmes workflows doivent exister sur trois cibles et le hors-ligne est obligatoire sur les clients installés.

Décision : Flutter/Dart avec adaptateurs plateforme.

Alternatives : web React + Electron + mobile natif, .NET MAUI.

Pourquoi cette décision : réutilisation élevée de l'UI et des modèles de présentation ; support officiel des cibles requises.

Conséquences : les limites du Web, de l'impression et des plugins doivent être testées sur matériel réel.

### ADR-003 — PostgreSQL comme autorité centrale

Contexte : stock, comptabilité, multi-tenant et audit demandent contraintes et transactions.

Décision : PostgreSQL, avec verrouillage de lignes et index adaptés.

Alternatives : MySQL, document store.

Pourquoi cette décision : intégrité relationnelle et concurrence sont des exigences de premier ordre.

Conséquences : les rapports et écritures doivent respecter le modèle relationnel ; les agrégations lourdes sont optimisées après mesure.

### ADR-004 — Journal d'opérations pour le hors-ligne

Contexte : deux appareils peuvent modifier le même stock sans connexion.

Décision : mutations idempotentes, identifiants d'opération, curseurs, versions et conflits visibles ; aucune fusion silencieuse d'un mouvement.

Alternatives : dernière écriture gagnante, réplication complète, CRDT partout.

Pourquoi cette décision : le stock et les finances ont besoin d'une intention métier et d'une résolution contrôlable.

Conséquences : le client doit gérer états en attente, rejet et conflit ; des tests de concurrence sont obligatoires.

### ADR-005 — Mouvements et écritures append-only

Contexte : audit, valorisation et conformité exigent de savoir ce qui s'est passé.

Décision : conserver événements et contre-écritures, avec projections modifiables seulement transactionnellement.

Alternatives : modifier directement les totaux, soft delete universel.

Pourquoi cette décision : elle rend les corrections explicables et les rapprochements possibles.

Conséquences : le schéma et les écrans doivent distinguer opération originale, annulation et compensation.

### ADR-006 — Multi-tenant explicite dès le début

Contexte : une commercialisation future est prévue.

Décision : `tenant_id` obligatoire, scoping applicatif et tests d'isolation ; RLS en défense additionnelle si la stratégie de connexion est validée.

Alternatives : base séparée par société dès le départ, ajout ultérieur du tenant.

Pourquoi cette décision : ajouter le tenant après production serait une migration risquée et coûteuse.

Conséquences : aucune requête métier ne peut ignorer le tenant ; les exports et caches doivent aussi être isolés par tenant.

### ADR-007 — Stockage objet privé pour les documents

Contexte : photos, factures et justificatifs peuvent croître indépendamment de la base.

Décision : stockage compatible S3, clés privées, URLs temporaires et métadonnées en PostgreSQL.

Alternatives : blobs PostgreSQL, disque du serveur.

Pourquoi cette décision : séparation des responsabilités, sauvegardes et évolution horizontale.

Conséquences : la suppression, la rétention et la restauration doivent couvrir base et fichiers.

### ADR-008 — Comptabilité configurable, conformité validée avant production

Contexte : les règles fiscales exactes et le pays ne sont pas encore confirmés.

Décision : plan comptable, journaux, règles de génération et exports configurables ; validation métier obligatoire avant déclarer le système conforme.

Alternatives : coder un plan fiscal figé, intégrer immédiatement un logiciel comptable externe.

Pourquoi cette décision : elle évite de rendre l'architecture fausse pour un pays non spécifié.

Conséquences : une revue comptable est un jalon bloquant de la production.
