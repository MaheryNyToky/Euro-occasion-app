# Plan d'implémentation — Phase 4, raccordement Flutter du stock

## Périmètre du ticket

Raccorder l'écran Flutter Stock aux commandes et projections existantes. Ajouter
uniquement la lecture des entrepôts nécessaire pour cibler une réception.

## Contrat client

- `GET /api/v1/warehouses` fournit les entrepôts actifs du tenant.
- `POST /api/v1/stock-movements` reçoit explicitement une entrée `receipt` après
  la création du produit, avec une clé d'idempotence générée côté client.
- `GET /api/v1/stock-balances` alimente le total affiché par produit.
- `GET /api/v1/stock-movements` alimente l'onglet Historique.

## Décisions de conception

- Les lectures s'appuient sur le scope tenant global existant.
- La réception est séparée de la création du produit afin que chaque variation
  de stock passe par le journal transactionnel.
- Aucun total local n'est muté directement par Flutter ; l'écran recharge les
  balances après une commande réussie.
- Aucune migration de schéma n'est requise.

## Vérification

Tests Flutter ciblés sur l'affichage de l'onglet, le chargement et la réception.
Analyse statique Flutter après modification.
