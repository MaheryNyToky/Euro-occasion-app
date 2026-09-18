# Règles pour les agents de développement

## Avant toute modification

- Lire `AI_RULES.Md`, `docs/ARCHITECTURE.md` et le document de domaine concerné.
- Comprendre le comportement existant et rechercher les composants, services et migrations déjà présents.
- Définir l'objectif, les fichiers concernés, les dépendances, les tests et les risques avant d'écrire.
- Ne pas modifier du code hors scope ni commencer une refonte opportuniste.

## Architecture et dépendances

- Respecter les décisions documentées ; demander explicitement une justification avant toute modification architecturale.
- Ne pas remplacer une technologie choisie sans raison sérieuse, preuve et mise à jour d'un ADR.
- Ne pas créer une abstraction, un service ou une dépendance pour quelques lignes si une solution existante suffit.
- Conserver les frontières : routes/contrôleurs, validation, policies, services métier, accès aux données et UI ne doivent pas être mélangés.

## Données et sécurité

- Ne jamais faire confiance aux données du client ; valider à l'API et dans le domaine.
- Appliquer le tenant, la permission et le périmètre à chaque lecture et mutation.
- Ne jamais supprimer physiquement mouvements, factures validées, paiements, écritures ou audits.
- Préserver les migrations et données existantes ; éviter les migrations destructives.
- Ne jamais committer de secret, désactiver une validation ou réduire la sécurité pour contourner un problème.
- Utiliser des transactions, l'idempotence et les verrouillages prévus pour stock, caisse, paiement et synchronisation.

## Client et UI

- Rechercher un composant existant avant d'en créer un ; partager les composants réellement communs.
- Respecter les tokens, la navigation, le responsive, l'accessibilité et les états loading/empty/error/offline.
- Adapter l'expérience Windows/Web aux tableaux et l'expérience Android au tactile et au scan sans dupliquer la logique métier.

## Qualité et livraison

- Lancer les tests pertinents, le lint et les vérifications de type après chaque étape importante.
- Ajouter un test de non-régression pour un bug et des tests de permissions pour toute route sensible.
- Vérifier le diff et expliquer les changements importants, décisions et limites.
- Privilégier les modifications minimales et conserver la compatibilité des contrats.
- Mettre à jour la documentation lorsqu'un comportement, une API, une migration ou une décision change.
- Antigravity peut prendre en charge boilerplate, CRUD, UI répétitive et tests simples ; Codex doit concevoir ou revoir sécurité, permissions, concurrence, transactions, migrations délicates et logique financière.

## Escalade

Créer ou mettre à jour `CODEX_REVIEW.md` lorsqu'il existe une ambiguïté métier, un conflit de synchronisation, une transaction complexe, un risque de sécurité, une migration difficile ou un écart avec l'architecture. Décrire le contexte, les fichiers exacts, ce qui a été essayé et une question précise.
