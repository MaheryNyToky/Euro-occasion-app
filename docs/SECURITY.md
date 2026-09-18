# Architecture de sécurité

## Principes

La sécurité est appliquée en profondeur : identité, tenant, permission, périmètre, validation, transaction et audit. Le client peut être compromis ; aucune décision de sécurité ou de stock ne repose sur son état local.

## Identité et sessions

Les mots de passe sont hachés avec l'algorithme recommandé par le framework et ne sont jamais journalisés. Les tokens d'appareils sont courts, rotatifs, révocables et liés à un appareil et un tenant. Une déconnexion globale révoque les sessions. Les comptes sensibles peuvent imposer MFA ; les codes de récupération sont à usage unique.

Les tentatives de connexion, récupération et endpoints sensibles sont limitées par IP, compte et appareil. Les messages publics ne révèlent pas si un email existe. Les comptes inactifs ou verrouillés ne peuvent pas renouveler une session.

## Autorisation

Chaque requête calcule : utilisateur, tenant, permission, ressource et périmètre. Les policies serveur sont obligatoires même si l'interface masque l'action. Les endpoints par identifiant utilisent une recherche limitée au tenant et au site autorisé afin d'éviter les IDOR.

Les permissions sensibles sont distinctes : consulter coût/marge, corriger stock, annuler facture, rembourser, modifier taux, clôturer caisse, poster écriture, gérer utilisateurs et exporter. Les validations à deux personnes seront ajoutées pour les seuils configurables si le risque opérationnel le justifie.

## Données et isolation

Toutes les requêtes métier doivent appliquer le tenant. Des tests négatifs tentent systématiquement d'accéder aux mêmes identifiants depuis un autre tenant. PostgreSQL RLS peut constituer une défense supplémentaire après définition d'une stratégie de connexion fiable ; il ne remplace pas les policies applicatives.

Les données en transit utilisent HTTPS/TLS. Les secrets sont fournis par le gestionnaire de secrets ou l'environnement de déploiement, jamais dans le dépôt. Les sauvegardes sont chiffrées et stockées hors de l'hôte principal. Les données sensibles dans les logs, exports temporaires et erreurs sont minimisées.

## Validation et injections

Les requêtes utilisent les mécanismes paramétrés du framework. Les noms de colonnes, tris, filtres et rapports passent par des listes blanches. Les entrées sont validées en taille, type, format, relation et invariant métier. Les sorties destinées à l'UI sont échappées ; les contenus riches sont interdits au départ.

Le Web applique CORS strict, headers de sécurité, CSP adaptée, cookies sécurisés si utilisés et protection CSRF pour les flux navigateur à cookie. Les tokens d'applications installées ne sont pas stockés dans des logs ni dans un stockage accessible au contenu web.

## Fichiers et exports

Les uploads sont privés par défaut, limités en taille et extension, inspectés par type MIME réel et renommés avec une clé générée. Ils sont servis via URL temporaire après autorisation. Les fichiers potentiellement actifs ne sont pas exécutés par le serveur. Les exports volumineux expirent et sont supprimés selon une rétention documentée.

## Intégrité métier

Les mouvements de stock, factures validées, paiements, écritures et audits sont append-only. Les corrections passent par contre-opérations avec motif et référence. Les transactions verrouillent la projection de stock, contrôlent les versions et utilisent l'idempotence pour empêcher doublons après retry.

## Audit, alertes et réponse

Les changements de permissions, accès sensibles, exports, authentifications, appareils, stock, prix, taux, factures, paiements, caisse et comptabilité créent un événement d'audit. L'audit capture avant/après sans secrets et n'est pas modifiable par un administrateur courant.

Les alertes initiales couvrent échecs de connexion anormaux, erreurs de permission, conflits, jobs, sauvegardes, stockage, latence et exceptions. La procédure d'incident contient isolement de compte/appareil, révocation, conservation des logs, restauration testée et notification selon les obligations applicables.

## Vérifications avant production

- revue des permissions par rôle et par écran ;
- tests d'isolation multi-tenant et IDOR ;
- tests de rate limiting, uploads et fichiers ;
- scan des dépendances et images Docker ;
- analyse statique et secrets scanning ;
- test de restauration et de rotation des sauvegardes ;
- test de concurrence stock/caisse et de rejouabilité sync ;
- revue des exigences fiscales, rétention et protection des données avec les responsables concernés.
