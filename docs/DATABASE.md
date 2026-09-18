# Modèle de données et règles de persistance

## Principes

PostgreSQL est la source de vérité. Les identifiants sont des UUID ou ULID générés côté serveur/client pour permettre la création hors ligne. Toutes les dates sont stockées en UTC. Les montants sont `numeric` avec devise explicite ; les quantités ont une précision définie par l'unité. Les opérations financières et de stock sont historisées, jamais écrasées.

Chaque entité métier porte `tenant_id`, `created_at`, `updated_at`, et lorsque pertinent `created_by`, `updated_by`, `version` et `deleted_at`. `deleted_at` désactive les référentiels ; il n'est pas utilisé pour les événements comptables ou mouvements.

## Entités principales

| Entité | Champs principaux | Relations et contraintes |
|---|---|---|
| `tenants` | id, code, nom, devise_comptable, timezone, statut | code unique ; parent logique de toute donnée métier. |
| `users` | id, tenant_id, nom, email, password_hash, statut, mfa | email unique par tenant ; jamais supprimé si auteur d'un audit. |
| `roles`, `permissions`, `role_permissions`, `user_roles` | noms et capacités | permissions versionnées ; rôles globaux ou limités à un périmètre. |
| `sites`, `warehouses`, `locations` | hiérarchie, code, type, actif | code unique dans tenant ; emplacement parent facultatif ; pas de cycle. |
| `categories`, `attributes`, `attribute_values` | libellé, type, valeur | référentiel tenant ; suppression logique seulement si utilisé. |
| `products` | sku, référence, nom, état, unité de base, seuil, actif | SKU unique tenant ; coût et prix courants sont des projections. |
| `product_variants` | product_id, attributs, sku, QR, prix | SKU/QR uniques tenant ; variante identifiable séparément. |
| `units`, `unit_conversions` | code, précision, facteur, date d'effet | conversions orientées et versionnées ; interdiction de convertir des familles incompatibles. |
| `serial_numbers`, `lots`, `warranties` | numéro, état, dates, fournisseur, coût | numéro de série unique tenant ; état cohérent avec les mouvements. |
| `stock_balances` | item, warehouse/location, on_hand, reserved, damaged, in_transit, version | unique par combinaison ; jamais modifié hors transaction de mouvement. |
| `stock_movements` | type, quantité, unité de base, source, destination, référence, actor, idempotency_key | append-only ; quantité positive ; source/destination selon type ; index date/item/site. |
| `stock_allocations` | mouvement, type d'affectation, cible polymorphe | permet employé, chantier, véhicule, chambre ou projet sans coder chaque cible. |
| `inventory_counts`, `inventory_count_lines` | périmètre, état, compteur, quantité théorique/réelle, écart | une ligne par item/emplacement ; verrouillage de claim pour éviter double comptage. |
| `suppliers`, `customers` | identité, contacts, devise, conditions, fiscalité | référentiels séparés ; informations facultatives ; archivage logique. |
| `purchase_orders`, `purchase_order_lines` | fournisseur, devise, statuts, dates, prix, taxes | réception partielle ; prix et taux figés sur la ligne. |
| `import_shipments`, `shipment_orders`, `shipment_charges` | dossier, transport, frais, devise, taux, méthode d'allocation | frais conservés par ligne et allocation figée après validation. |
| `landed_cost_allocations` | charge, ligne, base, montant, montant unitaire | somme des allocations = charge convertie, avec arrondi documenté. |
| `exchange_rates` | devise source/cible, taux, date, source | historique ; taux utilisé copié sur chaque opération. |
| `price_rules`, `product_prices` | marge/coefficient, taxe, arrondi, validité | prix suggéré explicable ; prix appliqué copié sur documents. |
| `quotes`, `sales_orders`, `invoices`, lignes | statut, client, numérotation, devise, totaux | transitions contrôlées ; document validé immuable ; avoir/correction séparé. |
| `payments` | montant, devise, méthode, facture, caisse, référence | paiement partiel possible ; somme contrôlée ; idempotence. |
| `cash_registers`, `cash_sessions`, `cash_entries` | caisse, session, fonds, type, rapprochement | une session ouverte par caisse ; clôture avec écart explicite. |
| `chart_accounts`, `accounting_journals`, `accounting_entries`, lignes | compte, période, débit, crédit, source | débit = crédit ; période clôturable ; contre-écriture. |
| `attachments` | owner_type/id, objet, hash, MIME, taille, storage_key | contrôle d'accès hérité de l'objet ; stockage privé. |
| `notifications`, `devices`, `sync_operations`, `sync_conflicts` | destinataire, appareil, mutation, statut, erreur | clé d'idempotence unique par appareil/commande ; conflits visibles. |
| `audit_events` | acteur, action, objet, before/after, motif, device, request_id | append-only ; index tenant/objet/date ; accès réservé. |

## Relations et règles métier

Un produit peut avoir des variantes ; un item stockable est soit un produit simple soit une variante, avec un identifiant stable `stock_item_id`. Un article sérialisé possède une quantité logique de 1 et un numéro unique. Les lots permettent de tracer une quantité non sérialisée avec date et fournisseur.

Les conversions d'unité transforment toujours vers l'unité de base avant mouvement. Une sortie de boîte ne décrémente donc pas un entier arbitraire : elle produit la quantité de base configurée. Les anciennes conversions restent lisibles pour l'historique.

Une réception crée les mouvements d'entrée et rattache prix, taux, importation et documents. Une vente réserve d'abord puis consomme le stock lors de la livraison ; annuler une réservation crée l'opération inverse. Un transfert crée une paire source/destination atomique.

La valorisation conserve au minimum coût d'achat, frais alloués, coût rendu unitaire et méthode. La méthode et les taux sont figés à la validation de l'importation. Une correction ultérieure est une nouvelle version ou une contre-opération, jamais une modification silencieuse.

## Index et intégrité

Index uniques tenant/code pour SKU, QR, séries, fournisseurs, clients et numéros de documents. Index composés `(tenant_id, status, updated_at)`, `(tenant_id, item_id, warehouse_id)`, `(tenant_id, created_at)` sur mouvements, factures, paiements et audits. Index partiels sur actifs et documents ouverts. Les rapports lourds pourront obtenir des vues matérialisées après mesure.

Les clés étrangères utilisent `RESTRICT` pour les événements et `SET NULL` seulement pour les références facultatives. Les contraintes vérifient montants non négatifs, devise présente, débit/crédit valides, quantités positives et statuts cohérents. Les opérations concurrentes verrouillent la projection de stock et vérifient sa version.

## Multi-tenant et synchronisation

Le tenant est injecté depuis l'identité authentifiée, jamais depuis un champ fiable envoyé seul par le client. Les appareils téléchargent un périmètre autorisé et un curseur de changements. Une mutation contient `operation_id`, `device_id`, `base_version`, commande, horodatage client et payload validé. Le serveur accepte une seule fois une opération identique ; une nouvelle opération est créée si le client réessaie après timeout.

Les commandes de stock sont fusionnées par opération, pas par remplacement de `stock_balances`. Pour les fiches de référentiel, la version de base permet de détecter un conflit ; selon le champ, le serveur peut fusionner, garder la dernière version autorisée ou demander une résolution. Tout conflit est enregistré et présenté.

## Suppression et rétention

Les référentiels sans historique peuvent être archivés. Les opérations de stock, factures validées, paiements postés, écritures, sessions clôturées et audits sont conservés selon la politique légale à confirmer. Une demande de suppression de données personnelles doit respecter les obligations de conservation et anonymiser l'acteur si la loi le permet.
