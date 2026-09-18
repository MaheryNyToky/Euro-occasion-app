# Contrat de l'API

## Conventions

Base URL : `/api/v1`. Format JSON UTF-8. Les réponses de collection contiennent `data`, `meta` et `links`. Les listes importantes utilisent `cursor`, `limit`, `sort`, filtres nommés et une limite maximale. Les dates sont ISO 8601 UTC ; les montants sont des chaînes décimales accompagnées de `currency`.

Les commandes mutantes acceptent `Idempotency-Key`. Les mutations hors ligne ajoutent `X-Device-Id`, `X-Sync-Operation-Id` et la version connue. Une réussite répétée renvoie le même résultat logique ; un conflit renvoie `409` avec une référence exploitable.

Erreurs : `400` requête invalide, `401` non authentifié, `403` permission insuffisante, `404` ressource invisible ou absente, `409` conflit métier/version, `422` validation, `429` rate limit, `500` erreur corrélée par `request_id`. Les détails internes restent dans les logs.

## Authentification et appareils

| Méthode | Endpoint | Accès | Résultat |
|---|---|---|---|
| POST | `/auth/login` | public, rate limité | token court, utilisateur, tenant et appareil. |
| POST | `/auth/refresh` | token valide | token renouvelé et ancien révoqué. |
| POST | `/auth/logout` | authentifié | révocation de l'appareil ou de la session. |
| POST | `/auth/password/forgot` | public | réponse uniforme, email si compte valide. |
| POST | `/auth/password/reset` | jeton de récupération | nouveau mot de passe et révocation des sessions sensibles. |
| GET | `/me` | authentifié | profil, permissions et périmètres effectifs. |
| GET/DELETE | `/devices` | utilisateur ou admin | appareils connus, dernière synchronisation, révocation. |

## Référentiels et catalogue

`GET/POST /products`, `GET/PATCH /products/{id}`, `POST /products/{id}/archive`, `GET /products/{id}/history` gèrent catalogue, photos, variantes et historique. `GET/POST/PATCH /categories`, `/attributes`, `/units` et `/unit-conversions` gèrent les référentiels selon permissions.

`GET /search?q=&types=&cursor=` fournit la recherche globale autorisée. `GET/POST /products/{id}/qr` génère une représentation ; l'impression et le fichier final peuvent être produits par un job.

Validation : SKU et QR uniques par tenant, variante compatible, unités compatibles, prix et seuils non négatifs. Erreurs : doublon `422`, référentiel utilisé `409`, accès hors périmètre `403/404`.

## Sites, stocks et mouvements

`GET/POST/PATCH /sites`, `/warehouses`, `/locations` gèrent la hiérarchie. `GET /stock-balances?warehouse_id=&item_id=&status=` lit la projection paginée.

| Méthode | Endpoint | Permission et comportement |
|---|---|---|
| POST | `/stock-movements/receipts` | `stock.create`; réception transactionnelle avec lignes, séries/lots et référence. |
| POST | `/stock-movements/issues` | `stock.create`; contrôle stock négatif configurable, affectation et justification. |
| POST | `/stock-movements/transfers` | `stock.transfer`; source et destination atomiques. |
| POST | `/stock-movements/adjustments` | `stock.adjust`; seuil, justification et approbation éventuelle. |
| GET | `/stock-movements` | `stock.view`; filtres date, item, utilisateur, type, site. |
| POST | `/stock-movements/{id}/reverse` | permission sensible ; crée une contre-opération. |
| GET | `/serial-numbers/{number}/history` | `stock.view_serial`; timeline complète. |

## Inventaires

`POST /inventory-counts`, `GET /inventory-counts`, `GET /inventory-counts/{id}`, `POST /inventory-counts/{id}/claims`, `POST /inventory-counts/{id}/lines`, `POST /inventory-counts/{id}/submit`, `POST /inventory-counts/{id}/approve` et `POST /inventory-counts/{id}/post` couvrent création, attribution de zone, comptage collaboratif, validation et correction.

Une ligne porte la version du stock observée. Une double revendication ou modification concurrente renvoie `409`. La publication crée des mouvements de correction et conserve le rapprochement théorique/réel.

## Fournisseurs, achats et importations

`GET/POST/PATCH /suppliers` et `/customers` gèrent les fiches. `GET/POST/PATCH /purchase-orders`, `POST /purchase-orders/{id}/confirm`, `/receive` et `/cancel` gèrent commandes et réceptions partielles.

`GET/POST/PATCH /import-shipments`, `POST /import-shipments/{id}/charges`, `POST /import-shipments/{id}/allocate-costs`, `POST /import-shipments/{id}/validate-cost` gèrent dossier, frais et coût rendu. La validation retourne lignes, méthode, taux, montant alloué, coût unitaire et éventuels écarts d'arrondi.

`GET /exchange-rates?from=&to=&date=` lit les taux ; `POST /exchange-rates/manual` exige une permission et un motif. Le taux copié sur une opération ne change jamais rétroactivement.

## Ventes, factures et paiements

`GET/POST/PATCH /quotes`, `POST /quotes/{id}/accept`, `/convert-to-order` ; `GET/POST/PATCH /sales-orders`, `POST /sales-orders/{id}/reserve`, `/deliver` ; `GET/POST /invoices`, `POST /invoices/{id}/issue`, `/cancel`, `/credit-note` composent le cycle commercial.

`POST /payments` enregistre un paiement partiel ou total avec clé d'idempotence. `GET /receivables` expose les soldes. Un document émis n'est jamais modifié directement ; une correction passe par annulation, avoir ou nouveau document autorisé.

## Caisse et comptabilité

`POST /cash-registers/{id}/sessions/open`, `POST /cash-sessions/{id}/entries`, `POST /cash-sessions/{id}/close`, `GET /cash-sessions` couvrent ouverture, opérations et clôture. Une session ne peut être clôturée deux fois ; l'écart est enregistré.

`GET/POST/PATCH /chart-accounts`, `/accounting-journals`, `GET /accounting-entries`, `POST /accounting-entries/manual`, `POST /accounting-periods/{id}/close` gèrent la comptabilité. La création d'une écriture vérifie équilibre débit/crédit, période ouverte, compte actif et source. Les écritures issues de ventes/achats/paiements portent une référence source.

## Documents, rapports et notifications

`POST /attachments/presign`, `POST /attachments/complete`, `GET /attachments/{id}/download` utilisent des URL temporaires et une autorisation par objet.

`POST /reports/{type}/export` crée un job ; `GET /exports/{id}` suit l'état ; `GET /exports/{id}/download` télécharge le fichier si l'utilisateur a toujours accès aux données. Les filtres sont sérialisés dans le job et ne font pas confiance à un filtre client ultérieur.

`GET /notifications`, `POST /notifications/{id}/read` et `GET /sync/status` fournissent le centre de notifications et la visibilité opérationnelle.

## Synchronisation

`GET /sync/changes?cursor=&limit=` récupère les changements autorisés. `POST /sync/operations` accepte un lot borné d'opérations idempotentes et retourne pour chacune `accepted`, `already_applied`, `conflict` ou `rejected`, avec nouveau curseur.

Une opération refusée pour permission ou validation ne doit pas être rejouée automatiquement. Une opération en conflit reste consultable et actionnable ; le client ne remplace jamais la décision du serveur silencieusement.

## Versionnement et compatibilité

Les ruptures de contrat créent `/api/v2`. Les nouveaux champs sont ajoutés optionnellement ; les clients anciens continuent de recevoir les champs requis. Toute transition de statut et toute permission nouvelle sont documentées et testées par contrat.
