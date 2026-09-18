# Roadmap d'implémentation

Chaque phase doit être livrée avec migrations, documentation, tests ciblés, permissions et états d'erreur. Aucun écran ne doit être construit avant le contrat de domaine dont il dépend.

## Phase 0 — Validation et fondations

Objectifs : confirmer les règles fiscales/comptables, volumes, appareils, pays, workflow de stock et matrice de permissions.

Modules : `docs/`, manifests, conventions, Docker local, CI minimale, stratégie d'environnements.

Dépendances : décisions du propriétaire et validation comptable.

Validation : cahier des règles signé, dépôt reproductible, lint et tests de base automatisés.

## Phase 1 — Base technique multi-tenant

Objectifs : Laravel, PostgreSQL, Redis, Flutter, configuration, migrations de tenants, utilisateurs, appareils et audit.

Modules : `apps/api`, `apps/client`, `database/migrations`, `infra/docker`.

Validation : démarrage local, migration fraîche, health checks, création de tenant et événement d'audit.

## Phase 2 — Authentification et permissions

Objectifs : login, récupération, MFA préparatoire, sessions d'appareils, RBAC, périmètres site/entrepôt, middleware et écran d'administration.

Validation : tests d'accès positif/négatif, révocation, rate limiting, séparation entre deux tenants.

## Phase 3 — Catalogue et référentiels

Objectifs : produits, variantes, catégories, attributs, unités, conversions, QR, fournisseurs, clients, pièces jointes de base.

Validation : unicité, archivage, conversions, recherche et affichage responsive. Aucun stock réel n'est encore déduit par un simple CRUD.

## Phase 4 — Stock transactionnel connecté

Objectifs : sites, entrepôts, emplacements, lots, séries, balances, mouvements, affectations, alertes et réservations.

Validation : transactions concurrentes, absence de stock négatif selon configuration, séries uniques, transferts atomiques, contre-opérations et audit complet.

## Phase 5 — Client local et synchronisation

Objectifs : SQLite/Drift, modèle de changements, file locale, idempotence, curseurs, retry, conflits et indicateur de synchronisation.

Validation : appareils déconnectés, interruption en cours d'envoi, doublon de retry, sorties concurrentes A/B et conflit de fiche référentiel. Les résultats doivent être explicites et réconciliables.

## Phase 6 — Inventaires et QR opérationnel

Objectifs : scan Android, scan USB si nécessaire, inventaire par zone, claims, comptage collaboratif, rapprochement et validation.

Validation : double comptage empêché, inventaire hors ligne, séries détectées, correction après approbation et rapport d'écart.

## Phase 7 — Achats, importations et coût rendu

Objectifs : fournisseurs, commandes, réception partielle, dossiers d'importation, frais multi-devises, taux historiques, allocations et prix suggéré.

Validation : méthodes valeur/quantité/poids/volume/manuel, arrondis, frais sans valeur, répartition vérifiable, coût figé et reprise après échec de job.

## Phase 8 — Ventes, facturation, paiements et caisse

Objectifs : devis, commandes, réservations, livraisons, factures, avoirs, paiements partiels, caisses, sessions et rapprochement.

Validation : transitions, numérotation concurrente, facture immuable, stock réservé/libéré, remboursement, clôture idempotente et audit.

## Phase 9 — Comptabilité opérationnelle

Objectifs : plan comptable configurable, journaux, écritures issues des flux, écritures manuelles autorisées, périodes, balance et grand livre.

Validation : équilibre débit/crédit, mapping validé par comptable, période clôturée, contre-écriture, export et reprise après erreur.

## Phase 10 — Rapports, notifications et administration avancée

Objectifs : tableaux de bord par rôle, exports CSV/XLSX/PDF en jobs, notifications, sauvegardes administrables, historique avancé et modèles d'étiquettes.

Validation : filtres tenant/périmètre, jobs rejouables, téléchargement autorisé, performance mesurée, alertes exploitables.

## Phase 11 — Durcissement et production

Objectifs : tests E2E critiques, audit sécurité, monitoring, sauvegarde/restauration, builds Windows/Android/Web, distribution et documentation d'exploitation.

Validation : recette métier, restauration démontrée, rollback applicatif, HTTPS, secrets hors dépôt, RTO/RPO acceptés et runbook d'incident.

## Découpage recommandé des tickets

Un ticket doit viser un cas d'usage vertical court : migration + domaine + route + écran éventuel + tests. Les tâches complexes de synchronisation, stock, sécurité et comptabilité sont conçues par Codex puis implémentées en petits lots. Les écrans CRUD et composants répétitifs peuvent être délégués après contrat validé.
