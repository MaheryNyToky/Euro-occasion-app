# AI Development Rules

## 1. Avant de coder

Pour toute tâche triviale :

1. Comprendre l'objectif.
2. Examiner uniquement les fichiers pertinents.
3. Identifier les dépendances.
4. Évaluer les risques de régression.
5. Établir un plan avant toute modification et créer ou remplacer le fichier ARCHITECTURE.md.

Ne pas explorer l'intégralité du dépôt sans nécessité.

---

## 2. Scope

Toujours effectuer la modification minimale nécessaire.

Ne pas :
- refactorer du code non concerné ;
- modifier l'architecture sans nécessité ;
- ajouter des dépendances inutilement ;
- réécrire des fichiers entiers pour une petite modification ;
- supprimer du comportement existant sans justification.

---

## 3. Implémentation

Travailler par petites étapes.

Après chaque étape importante :

- vérifier le code ;
- exécuter les tests pertinents ;
- vérifier les erreurs de compilation / lint ;
- continuer seulement si l'étape précédente est valide.

---

## 4. Bugs

Pour un bug :

1. reproduire le problème ;
2. identifier la cause racine ;
3. ne pas appliquer de workaround si une correction propre existe ;
4. effectuer la correction minimale ;
5. ajouter un test de non-régression si pertinent.

---

## 5. Tests

Ne pas lancer toute la suite de tests inutilement.

Commencer par les tests directement liés aux fichiers modifiés.

Lancer une suite plus large uniquement lorsque nécessaire.

---

## 6. Architecture

Respecter l'architecture existante.

Avant d'introduire :
- une nouvelle dépendance ;
- un nouveau service ;
- une nouvelle abstraction ;
- une nouvelle technologie ;

vérifier qu'une solution existe déjà dans le projet.

---

## 7. Sécurité

Ne jamais :
- exposer des secrets ;
- mettre des clés API en dur ;
- désactiver une validation pour contourner une erreur ;
- diminuer la sécurité simplement pour faire fonctionner une fonctionnalité.

---

## 8. Base de données

Pour toute modification de schéma :

- préserver les données existantes ;
- vérifier la compatibilité ;
- prévoir la migration ;
- éviter les migrations destructives sans demande explicite.

---

## 9. Fin d'une tâche

Une tâche n'est terminée que si :

- l'objectif demandé fonctionne ;
- les tests pertinents passent ;
- aucune erreur de lint/type importante n'est introduite ;
- aucun fichier non nécessaire n'a été modifié.

Donner à la fin :

- fichiers modifiés ;
- résumé de la modification ;
- tests exécutés ;
- éventuels risques ou points restant à vérifier.

## Règles à toujours respecter

- Ne lire que les fichiers nécessaires à la tâche.
- Ne pas scanner l'ensemble du dépôt par défaut.
- Utiliser les recherches ciblées avant d'ouvrir de nombreux fichiers.
- Ne pas relire un fichier inchangé inutilement.
- Ne pas lancer toute la suite de tests lorsqu'un test ciblé suffit.
- Préférer les petites modifications aux réécritures complètes.
- Never run `git push` unless the user explicitly asks for it in the current conversation.
- If a request is ambiguous or missing critical details, ask a short clarifying question before editing files or changing behavior.
- When a push is performed, the final summary must explicitly state the target repository URL and branch, and clearly say that the changes were pushed to that repository.

