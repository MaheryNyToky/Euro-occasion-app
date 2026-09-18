# Cahier des charges — Application de gestion d'inventaire, importation, facturation, caisse et comptabilité

## 1. Présentation du projet

### 1.1. Contexte

L'entreprise exerce une activité d'**import/export** avec stockage de marchandises dans un ou plusieurs entrepôts ou lieux de stockage.

La gestion actuelle repose principalement sur **Excel**, ce qui entraîne plusieurs limites :

- risque de pertes ou d'erreurs de stock ;
- manque de traçabilité sur les entrées et sorties ;
- difficulté à savoir qui a pris ou déplacé un produit ;
- difficulté à connaître la valeur réelle du stock ;
- difficulté à calculer précisément le coût réel des produits importés ;
- absence de synchronisation fiable entre plusieurs utilisateurs et plusieurs sites ;
- inventaires physiques longs et difficiles à rapprocher du stock théorique ;
- facturation, caisse et comptabilité insuffisamment centralisées ;
- manque de visibilité globale pour la direction.

L'objectif est de créer une application moderne, rapide et fiable permettant de centraliser l'ensemble de ces opérations.

---

## 2. Objectifs généraux

L'application devra permettre de :

1. gérer les stocks de plusieurs entrepôts ou lieux ;
2. suivre précisément les entrées, sorties, transferts et corrections ;
3. assurer une traçabilité complète de toutes les opérations ;
4. connaître la valeur du stock en temps réel ;
5. gérer les produits unitaires, boîtes, lots et autres modes de conditionnement ;
6. gérer les produits avec variantes ;
7. gérer les produits disposant de numéros de série et/ou de garantie ;
8. gérer les fournisseurs et les achats ;
9. gérer les importations et expéditions ;
10. répartir les frais d'importation sur les produits ;
11. calculer le coût réel rendu de chaque produit ;
12. suggérer un prix de vente après calcul du coût réel ;
13. gérer les clients ;
14. gérer les devis, commandes, factures et paiements ;
15. gérer une caisse ;
16. intégrer une base comptable exploitable par l'entreprise ;
17. effectuer des inventaires physiques ;
18. scanner les produits par QR code ;
19. fonctionner sans Internet ;
20. synchroniser automatiquement les données lorsque la connexion revient ;
21. fonctionner depuis plusieurs lieux géographiques ;
22. être accessible à distance via Internet ;
23. offrir une application Windows installable ;
24. offrir une application Android installable ;
25. générer des rapports PDF, Excel et CSV ;
26. offrir un tableau de bord adapté au rôle de l'utilisateur ;
27. gérer un grand nombre d'utilisateurs ;
28. permettre une réutilisation future dans d'autres sociétés ;
29. permettre une éventuelle commercialisation future sous forme de produit multi-entreprises.

---

# 3. Périmètre fonctionnel

Le projet comprend les modules principaux suivants :

- Produits et catalogue ;
- Variantes et conditionnements ;
- Stocks ;
- Entrepôts et emplacements ;
- Mouvements de stock ;
- QR codes ;
- Inventaires physiques ;
- Fournisseurs ;
- Achats ;
- Commandes fournisseurs ;
- Importations / expéditions ;
- Frais d'importation ;
- Calcul du coût réel rendu ;
- Clients ;
- Devis ;
- Commandes clients ;
- Facturation ;
- Paiements ;
- Caisse ;
- Retours ;
- Comptabilité ;
- Utilisateurs ;
- Rôles et permissions ;
- Validation des actions sensibles ;
- Historique et journal d'audit ;
- Tableau de bord ;
- Alertes ;
- Rapports ;
- Exportations ;
- Sauvegardes ;
- Synchronisation hors ligne ;
- Paramètres de l'entreprise.

---

# 4. Utilisateurs et rôles

L'application devra supporter un nombre important d'utilisateurs.

## 4.1. Rôles standards

### Administrateur

Accès complet à l'application et à sa configuration.

Exemples :

- création des utilisateurs ;
- attribution des rôles ;
- configuration des entrepôts ;
- configuration des catégories ;
- configuration des taxes ;
- configuration des devises ;
- gestion des autorisations ;
- consultation complète des journaux ;
- paramétrage des sauvegardes.

L'administrateur ne devra toutefois **pas pouvoir effacer définitivement l'historique des opérations sensibles**.

### Responsable stock

Accès notamment à :

- produits ;
- stocks ;
- mouvements ;
- inventaires ;
- transferts ;
- réceptions ;
- commandes fournisseurs ;
- alertes de stock ;
- validation de certaines opérations sensibles.

### Employé

Droits limités selon le profil.

Exemples :

- consultation de produits ;
- scan QR ;
- création de mouvements autorisés ;
- participation aux inventaires ;
- ajout de photos ;
- consultation de son historique.

### Comptable

Accès notamment à :

- achats ;
- factures ;
- paiements ;
- taxes ;
- comptabilité ;
- rapports financiers ;
- exports.

### Direction

Accès principalement en consultation avec visibilité globale sur :

- stock ;
- valeur du stock ;
- achats ;
- ventes ;
- chiffre d'affaires ;
- marges ;
- pertes ;
- dépenses ;
- trésorerie ;
- rapports ;
- tableaux de bord.

## 4.2. Permissions personnalisables

Le système devra utiliser un modèle de permissions configurable.

Une autorisation pourra être accordée ou refusée pour chaque fonctionnalité, par exemple :

- voir ;
- créer ;
- modifier ;
- valider ;
- annuler ;
- exporter ;
- imprimer ;
- effectuer une correction ;
- consulter les coûts ;
- consulter les marges ;
- consulter la comptabilité ;
- administrer les utilisateurs.

Des profils personnalisés devront pouvoir être créés en plus des rôles standards.

---

# 5. Gestion des produits

## 5.1. Fiche produit

Chaque produit devra pouvoir contenir :

- identifiant interne ;
- nom ;
- référence ;
- SKU ;
- catégorie ;
- sous-catégorie ;
- description ;
- photo principale ;
- plusieurs photos secondaires ;
- quantité ;
- unité ;
- type de conditionnement ;
- prix d'achat ;
- coût réel rendu ;
- prix de vente ;
- prix de vente suggéré ;
- fournisseur principal ;
- fournisseurs alternatifs ;
- emplacement ;
- seuil d'alerte ;
- observations ;
- état : neuf / occasion ;
- pays d'origine ;
- devise d'achat ;
- numéro de série si applicable ;
- informations de garantie ;
- QR code ;
- statut actif/inactif ;
- date de création ;
- date de modification ;
- utilisateur ayant créé ou modifié la fiche.

## 5.2. Catégories

L'entreprise devra pouvoir créer librement :

- catégories ;
- sous-catégories ;
- groupes de produits ;
- familles ;
- attributs personnalisés.

Aucune catégorie ne devra être codée en dur.

---

# 6. Variantes

Un même produit pourra avoir plusieurs variantes.

Exemples :

- taille ;
- couleur ;
- capacité ;
- modèle ;
- version ;
- conditionnement ;
- origine ;
- autres attributs personnalisés.

Chaque variante pourra disposer de :

- son propre SKU ;
- son propre QR code ;
- son propre prix ;
- son propre stock ;
- son propre seuil d'alerte ;
- sa propre photo ;
- son propre numéro de série si nécessaire.

---

# 7. Unités et conditionnements

Le système devra gérer différents modes de stockage et vente :

- unité ;
- boîte ;
- carton ;
- paquet ;
- lot ;
- palette ;
- kilogramme ;
- litre ;
- mètre ;
- autres unités personnalisées.

Il devra être possible de définir des conversions.

Exemple :

- 1 carton = 12 boîtes ;
- 1 boîte = 10 unités.

Une sortie de 1 boîte pourra ainsi diminuer automatiquement le stock de 10 unités si le produit est configuré de cette manière.

---

# 8. Numéros de série

Certains produits nécessitent un numéro de série unique.

Pour ces produits :

- chaque unité physique devra pouvoir être enregistrée individuellement ;
- deux unités ne pourront pas avoir le même numéro de série ;
- l'historique devra être consultable par numéro de série ;
- le système devra permettre de savoir :
  - quand le produit est arrivé ;
  - auprès de quel fournisseur ;
  - dans quelle importation ;
  - son coût ;
  - son emplacement ;
  - à quel client ou utilisateur il a été affecté ;
  - s'il a été vendu ;
  - s'il a été retourné ;
  - s'il a été déclaré perdu ou endommagé.

---

# 9. Garantie

Les produits concernés pourront disposer de :

- durée de garantie ;
- date de début ;
- date de fin ;
- fournisseur garant ;
- conditions de garantie ;
- numéro de garantie ;
- document associé ;
- notes.

Lors d'une vente, la garantie pourra être associée au client et à la facture.

---

# 10. Entrepôts et emplacements

## 10.1. Multi-sites

Le système devra permettre de créer un nombre variable de lieux de stockage :

- entrepôts ;
- magasins ;
- bureaux ;
- dépôts ;
- chambres ;
- zones temporaires ;
- autres lieux.

## 10.2. Emplacements internes

Chaque site pourra être subdivisé en :

- zone ;
- allée ;
- rayon ;
- étagère ;
- emplacement ;
- bac.

Exemple :

`Entrepôt A > Zone 2 > Allée B > Étagère 3 > Bac 04`

L'utilisation des emplacements précis devra rester facultative.

---

# 11. Gestion du stock

Chaque combinaison suivante devra pouvoir disposer de son propre stock :

- produit ;
- variante ;
- entrepôt ;
- emplacement.

Le stock devra distinguer si nécessaire :

- quantité physique ;
- quantité disponible ;
- quantité réservée ;
- quantité en transit ;
- quantité commandée auprès des fournisseurs ;
- quantité endommagée ;
- quantité bloquée.

---

# 12. Mouvements de stock

Le système devra gérer au minimum :

- entrée de stock ;
- sortie ;
- transfert ;
- retour fournisseur ;
- retour client ;
- produit endommagé ;
- produit perdu ;
- correction positive ;
- correction négative ;
- réservation ;
- libération de réservation ;
- inventaire physique ;
- vente ;
- réception fournisseur ;
- annulation de mouvement.

Chaque mouvement devra contenir :

- identifiant unique ;
- produit ;
- variante ;
- numéro de série si applicable ;
- quantité ;
- unité ;
- stock source ;
- stock destination ;
- type de mouvement ;
- motif ;
- date et heure ;
- utilisateur ;
- appareil utilisé ;
- référence associée ;
- commentaires ;
- pièces jointes éventuelles.

---

# 13. Affectation des sorties

Une sortie pourra être affectée à :

- un employé ;
- un chantier ;
- un service ;
- un client ;
- une chambre d'hôtel ;
- un véhicule ;
- un projet ;
- toute autre entité ajoutée ultérieurement.

L'entité d'affectation devra être configurable.

Le système devra pouvoir répondre à des questions telles que :

- Qui a pris ce produit ?
- Quel employé possède actuellement cet équipement ?
- Quels produits ont été attribués à ce véhicule ?
- Quels produits ont été utilisés pour ce chantier ?
- Quels produits sont affectés à cette chambre ?

---

# 14. Stock négatif et alertes

Le système devra détecter automatiquement :

- tentative de stock négatif ;
- stock faible ;
- stock nul ;
- incohérence de stock ;
- quantité inhabituelle ;
- seuil minimum atteint.

Lorsqu'une opération provoque un stock négatif, le comportement devra être configurable :

- bloquer l'opération ;
- autoriser avec avertissement ;
- demander une autorisation ;
- enregistrer une alerte.

Toutes les alertes devront être regroupées dans une interface dédiée accessible depuis un bouton ou une zone du tableau de bord.

---

# 15. QR codes

## 15.1. Génération

L'application devra générer des QR codes pour :

- produits ;
- variantes ;
- numéros de série ;
- emplacements si nécessaire.

## 15.2. Impression

Les QR codes devront pouvoir être imprimés sur :

- papier classique ;
- étiquettes ;
- imprimantes d'étiquettes.

Des modèles d'étiquettes personnalisables devront pouvoir être proposés.

## 15.3. Scan Android

L'application Android devra utiliser l'appareil photo pour scanner un QR code.

Après scan, l'utilisateur pourra selon ses permissions :

- ouvrir la fiche produit ;
- consulter le stock ;
- enregistrer une entrée ;
- enregistrer une sortie ;
- transférer un produit ;
- participer à un inventaire ;
- consulter un numéro de série.

---

# 16. Inventaire physique

## 16.1. Création d'un inventaire

Un inventaire devra pouvoir être créé pour :

- un entrepôt ;
- une zone ;
- une catégorie ;
- une sélection de produits ;
- l'intégralité des stocks.

## 16.2. Comptage

Les utilisateurs pourront :

- scanner les produits ;
- rechercher manuellement un produit ;
- saisir les quantités constatées ;
- saisir les numéros de série ;
- ajouter des commentaires ;
- ajouter une photo si nécessaire.

## 16.3. Inventaire collaboratif

Plusieurs employés devront pouvoir travailler simultanément sur différentes zones du même inventaire.

Le système devra éviter les doubles comptages non voulus.

## 16.4. Comparaison

Le système devra comparer automatiquement :

- stock théorique ;
- stock réel ;
- écart ;
- valeur de l'écart.

## 16.5. Validation

La correction automatique après inventaire pourra être configurable :

- correction immédiate ;
- correction après vérification ;
- correction après validation d'un responsable.

Le besoin actuel n'impose pas obligatoirement une validation, mais l'option devra exister.

---

# 17. Fournisseurs

Chaque fournisseur pourra disposer d'une fiche comprenant :

- raison sociale ;
- nom commercial ;
- adresse ;
- pays ;
- téléphone ;
- email ;
- contact principal ;
- site Internet ;
- identifiants fiscaux ;
- coordonnées bancaires si nécessaire ;
- devise habituelle ;
- conditions de paiement ;
- délai moyen ;
- observations.

L'application devra afficher :

- produits proposés ;
- derniers prix ;
- historique des prix ;
- achats effectués ;
- commandes en cours ;
- montant total acheté ;
- retards éventuels ;
- documents associés.

---

# 18. Commandes fournisseurs

Le système devra gérer les futurs arrivages.

Une commande fournisseur pourra contenir :

- fournisseur ;
- date ;
- devise ;
- produits ;
- quantités ;
- prix unitaires ;
- remises ;
- taxes ;
- date estimée d'expédition ;
- date estimée d'arrivée ;
- statut ;
- documents ;
- notes.

Statuts possibles :

- brouillon ;
- envoyée ;
- confirmée ;
- préparation ;
- expédiée ;
- en transit ;
- arrivée ;
- réception partielle ;
- réception complète ;
- annulée.

---

# 19. Devises et taux de change

Les devises initialement prévues sont notamment :

- MGA — Ariary malgache ;
- EUR — Euro ;
- USD — Dollar américain ;
- CNY — Yuan / Renminbi chinois.

D'autres devises devront pouvoir être ajoutées.

## 19.1. Taux automatique

L'application devra pouvoir récupérer automatiquement un taux de change lorsqu'une connexion Internet est disponible.

## 19.2. Modification manuelle

L'utilisateur autorisé devra pouvoir remplacer manuellement le taux proposé.

## 19.3. Historisation

Le taux utilisé lors d'une opération devra être définitivement associé à cette opération.

Une modification future du taux de change ne devra jamais modifier rétroactivement :

- une ancienne facture ;
- un ancien achat ;
- une ancienne importation ;
- un ancien coût rendu.

La devise de référence comptable principale sera configurable, avec **MGA comme devise prévue initialement**.

---

# 20. Importations et expéditions

Le système devra gérer une entité dédiée appelée par exemple :

**Dossier d'importation / Expédition**

Elle pourra regrouper :

- un ou plusieurs fournisseurs ;
- plusieurs commandes ;
- plusieurs produits ;
- plusieurs conteneurs si nécessaire ;
- plusieurs factures ;
- plusieurs devises ;
- les frais liés à l'importation.

Chaque dossier pourra contenir :

- numéro interne ;
- référence fournisseur ;
- pays de départ ;
- pays d'arrivée ;
- date de départ ;
- date d'arrivée estimée ;
- date d'arrivée réelle ;
- mode de transport ;
- transporteur ;
- transitaire ;
- documents ;
- statut ;
- commentaires.

---

# 21. Frais d'importation

Les frais pourront notamment inclure :

- transport ;
- douane ;
- fret maritime ;
- fret aérien ;
- assurance ;
- manutention ;
- transit ;
- taxes ;
- stockage ;
- frais bancaires ;
- inspection ;
- autres frais.

Aucun frais ne devra être obligatoire.

L'utilisateur devra pouvoir :

- ajouter un type de frais personnalisé ;
- supprimer une ligne non utilisée ;
- saisir un frais dans une devise différente ;
- définir le taux utilisé ;
- joindre une facture ou un justificatif.

---

# 22. Calcul du coût réel rendu

L'application devra calculer le coût réel d'un produit une fois rendu dans l'entrepôt.

Le coût pourra inclure :

- prix d'achat ;
- transport ;
- fret ;
- douane ;
- taxes non récupérables ;
- assurance ;
- manutention ;
- transit ;
- autres frais.

## 22.1. Méthodes de répartition

Le système devra permettre plusieurs méthodes de répartition des frais :

- au prorata de la valeur d'achat ;
- au prorata de la quantité ;
- au prorata du poids ;
- au prorata du volume ;
- répartition égale ;
- affectation manuelle ;
- combinaison personnalisée.

Une méthode pourra être sélectionnée selon le type de frais.

## 22.2. Coût rendu

Pour chaque produit, le système devra conserver :

- coût d'achat initial ;
- part des frais ;
- coût réel rendu unitaire ;
- coût total rendu ;
- marge cible ;
- prix de vente actuel ;
- prix de vente suggéré.

---

# 23. Prix de vente suggéré

Une fois le coût réel rendu calculé, le système pourra proposer un prix de vente.

La formule devra être configurable selon :

- marge cible ;
- coefficient multiplicateur ;
- taux de marge ;
- taux de marque ;
- taxes ;
- arrondi commercial.

Exemples d'arrondi :

- 19 830 MGA → 19 900 MGA ;
- 19 830 MGA → 20 000 MGA.

Le prix suggéré restera modifiable par un utilisateur autorisé.

---

# 24. Clients

Une fiche client pourra contenir :

- particulier / entreprise ;
- nom ;
- raison sociale ;
- adresse ;
- téléphone ;
- email ;
- identifiant fiscal ;
- devise ;
- conditions de paiement ;
- limite de crédit éventuelle ;
- historique des achats ;
- factures ;
- paiements ;
- retours ;
- notes.

Les champs non nécessaires devront pouvoir rester vides.

---

# 25. Devis clients

Le système devra permettre de :

- créer un devis ;
- ajouter des produits ;
- appliquer une remise ;
- appliquer des taxes ;
- ajouter des conditions ;
- générer un PDF ;
- imprimer ;
- envoyer ou exporter ;
- convertir en commande ;
- convertir en facture.

Statuts possibles :

- brouillon ;
- envoyé ;
- accepté ;
- refusé ;
- expiré ;
- converti.

---

# 26. Commandes clients

Une commande client pourra être :

- créée manuellement ;
- issue d'un devis ;
- partiellement livrée ;
- totalement livrée ;
- facturée partiellement ;
- facturée totalement.

Le système devra gérer la réservation de stock.

---

# 27. Facturation

Les factures devront comporter :

- numéro unique ;
- date ;
- client ;
- produits/services ;
- quantités ;
- prix ;
- remises ;
- taxes ;
- total HT ;
- total taxes ;
- total TTC ;
- devise ;
- taux de change utilisé si nécessaire ;
- paiements ;
- solde ;
- échéance ;
- commentaires.

Certaines informations comme la TVA pourront être facultatives selon la configuration de l'entreprise.

## 27.1. Numérotation

La numérotation devra être configurable.

Exemple :

`FAC-2026-000001`

## 27.2. Immutabilité

Une facture validée ne devra pas être supprimée définitivement.

Les corrections devront utiliser :

- annulation ;
- avoir ;
- facture corrective ;
- nouvelle version selon les règles comptables applicables.

---

# 28. Paiements

Les paiements pourront être enregistrés avec :

- date ;
- montant ;
- devise ;
- mode de paiement ;
- référence ;
- facture concernée ;
- caisse concernée ;
- utilisateur ;
- commentaire.

Modes possibles :

- espèces ;
- virement ;
- carte ;
- mobile money ;
- chèque ;
- autre.

Des paiements partiels devront être possibles.

---

# 29. Caisse

Le module caisse devra permettre :

- ouverture de caisse ;
- fond de caisse ;
- ventes ;
- encaissements ;
- remboursements ;
- sorties de caisse ;
- entrées manuelles ;
- clôture ;
- contrôle de caisse ;
- écarts de caisse ;
- historique.

Chaque opération devra identifier l'utilisateur.

Plusieurs caisses pourront être créées.

Le système devra pouvoir fonctionner en mode hors ligne si nécessaire.

---

# 30. Comptabilité

L'application devra intégrer un module comptable suffisamment structuré pour centraliser les opérations principales de l'entreprise.

Le périmètre initial devra au minimum prévoir :

- plan comptable configurable ;
- journaux ;
- écritures comptables ;
- ventes ;
- achats ;
- caisse ;
- banque ;
- fournisseurs ;
- clients ;
- taxes ;
- paiements ;
- charges ;
- produits ;
- pièces justificatives ;
- balances ;
- grand livre ;
- journaux comptables ;
- suivi des comptes clients et fournisseurs.

L'architecture devra permettre d'adapter ultérieurement les règles comptables aux obligations du pays et de l'entreprise.

Les écritures issues de la facturation, des achats et des paiements devront pouvoir être générées automatiquement selon des règles configurables.

---

# 31. Tableau de bord

## 31.1. Tableau de bord standard

Afficher notamment :

- produits en stock faible ;
- produits en rupture ;
- mouvements récents ;
- produits les plus utilisés ;
- dépenses du mois ;
- alertes ;
- commandes fournisseurs attendues ;
- inventaires en cours.

## 31.2. Direction et administrateur

Ajouter notamment :

- valeur totale du stock ;
- valeur par entrepôt ;
- ventes du jour ;
- ventes hebdomadaires ;
- ventes mensuelles ;
- ventes annuelles ;
- chiffre d'affaires ;
- achats ;
- dépenses ;
- marge brute ;
- bénéfices/marges ;
- pertes ;
- créances clients ;
- dettes fournisseurs ;
- tendances.

Les indicateurs devront pouvoir être filtrés par :

- date ;
- site ;
- entrepôt ;
- catégorie ;
- produit ;
- fournisseur ;
- client.

---

# 32. Rapports

Rapports prévus :

- stock actuel ;
- valorisation du stock ;
- stock par entrepôt ;
- stock par emplacement ;
- mouvements ;
- entrées ;
- sorties ;
- transferts ;
- inventaires ;
- écarts d'inventaire ;
- achats par fournisseur ;
- historique fournisseur ;
- historique produit ;
- historique numéro de série ;
- produits perdus ;
- produits endommagés ;
- pertes ;
- ventes ;
- bénéfices ;
- marges ;
- chiffre d'affaires ;
- coûts d'importation ;
- coût rendu ;
- paiements ;
- créances ;
- dépenses.

L'utilisateur devra pouvoir sélectionner une période.

---

# 33. Exportations

Les rapports pourront être exportés au choix en :

- PDF ;
- Excel/XLSX ;
- CSV.

L'application devra proposer :

- export complet ;
- export des données filtrées ;
- impression.

---

# 34. Historique et audit

La traçabilité est considérée comme critique.

Toute modification sensible devra enregistrer :

- utilisateur ;
- date ;
- heure ;
- appareil ;
- action ;
- ancienne valeur ;
- nouvelle valeur ;
- motif éventuel ;
- objet concerné.

Cela concerne notamment :

- stock ;
- produits ;
- prix ;
- taux de change ;
- fournisseurs ;
- factures ;
- paiements ;
- importations ;
- utilisateurs ;
- permissions ;
- comptabilité.

---

# 35. Interdiction de suppression définitive

Les anciennes opérations critiques ne devront pas pouvoir être supprimées définitivement, même par un administrateur.

Le système devra privilégier :

- annulation logique ;
- archivage ;
- désactivation ;
- contre-écriture ;
- nouvelle opération corrective.

Le journal d'audit devra être append-only autant que possible.

Une opération annulée devra rester consultable avec :

- auteur ;
- date ;
- motif ;
- opération d'origine ;
- opération compensatrice.

---

# 36. Actions sensibles

Certaines actions pourront nécessiter :

- permission spécifique ;
- confirmation ;
- saisie du mot de passe ;
- validation par un responsable ;
- justification obligatoire.

Exemples :

- correction importante du stock ;
- prix modifié au-dessous du coût ;
- annulation de facture ;
- gros remboursement ;
- modification du taux de change ;
- suppression logique d'un produit ;
- changement de droits ;
- clôture de caisse ;
- écriture comptable manuelle.

Les seuils devront être configurables.

---

# 37. Fonctionnement hors ligne

Le mode hors ligne est obligatoire.

Les applications Windows et Android devront continuer à permettre les opérations essentielles sans Internet.

Exemples :

- consulter des produits déjà synchronisés ;
- scanner un produit ;
- créer une entrée ;
- créer une sortie ;
- faire un inventaire ;
- créer une vente ;
- enregistrer certaines opérations autorisées.

Chaque appareil devra disposer d'une base locale.

Les opérations effectuées hors ligne seront placées dans une file de synchronisation.

---

# 38. Synchronisation

Lorsque la connexion est disponible :

- les données locales devront être envoyées au serveur ;
- les nouvelles données du serveur devront être récupérées ;
- la synchronisation devra reprendre automatiquement après une interruption.

Le système devra gérer :

- synchronisation automatique ;
- synchronisation manuelle ;
- état de synchronisation ;
- date de dernière synchronisation ;
- opérations en attente ;
- erreurs de synchronisation.

---

# 39. Gestion des conflits

Le système devra prévoir les cas où deux utilisateurs modifient la même information hors ligne.

Exemple :

- Utilisateur A sort 5 articles hors ligne.
- Utilisateur B sort 4 articles du même stock hors ligne.
- Les deux appareils se reconnectent.

Le serveur devra conserver toutes les opérations et recalculer le stock de manière cohérente.

Pour les données non transactionnelles, le système devra utiliser des règles de résolution de conflit.

Exemples :

- version des enregistrements ;
- date de modification ;
- validation manuelle ;
- priorité serveur pour certaines données.

Aucune opération de stock ne devra être silencieusement écrasée.

---

# 40. Architecture logique recommandée

Le système devra adopter une architecture permettant de supporter plusieurs plateformes.

Architecture générale recommandée :

```text
Application Windows
        |
Application Android
        |
Application Web / accès navigateur
        |
        v
API sécurisée
        |
        v
Serveur central
        |
        v
Base de données centrale
```

Chaque application Windows/Android disposera également d'une base locale pour le mode hors ligne.

```text
App locale
   |
Base locale
   |
File de synchronisation
   |
Internet disponible
   |
API serveur
   |
Base centrale
```

L'architecture devra éviter de créer trois logiques métiers totalement différentes.

---

# 41. Application Windows

L'application Windows devra être :

- installable ;
- dotée d'une icône ;
- accessible depuis le menu Démarrer ;
- utilisable comme une application classique ;
- capable de fonctionner hors ligne ;
- compatible avec les périphériques nécessaires.

Périphériques envisagés :

- lecteurs de codes-barres USB ;
- lecteurs QR USB ;
- imprimantes classiques ;
- imprimantes d'étiquettes.

---

# 42. Application Android

L'application Android devra être :

- installable ;
- dotée d'une icône ;
- pensée pour l'utilisation tactile ;
- rapide ;
- utilisable hors ligne.

Fonctions mobiles importantes :

- scan QR via caméra ;
- ajout de photos ;
- inventaire ;
- entrées/sorties ;
- consultation produit ;
- transfert ;
- affectation ;
- synchronisation.

---

# 43. Accès Web

Une interface accessible via navigateur devra permettre un accès depuis l'étranger ou depuis un ordinateur non équipé de l'application Windows.

Cet accès devra être sécurisé.

L'application devra fonctionner via HTTPS.

L'accès Web pourra être limité selon les rôles.

---

# 44. Hébergement

L'entreprise ne possède actuellement aucun serveur.

Le projet devra donc inclure une infrastructure complète.

Une architecture Cloud/VPS est recommandée pour démarrer.

Elle devra comprendre au minimum :

- serveur applicatif ;
- API ;
- base de données ;
- stockage des fichiers ;
- sauvegardes ;
- certificat HTTPS ;
- monitoring ;
- journalisation.

L'infrastructure devra pouvoir évoluer sans reconstruction complète.

---

# 45. Multi-entreprises

Le système devra être conçu dès le départ pour permettre une future utilisation par plusieurs sociétés.

Même si une seule société utilise initialement l'application, les données devront être associées à une entité entreprise/tenant.

Exemples :

- Société A ;
- Société B ;
- Société C.

Les données d'une entreprise ne devront jamais être visibles par une autre sans autorisation.

Cette architecture facilitera :

- l'utilisation dans d'autres sociétés du groupe ;
- la commercialisation future du logiciel.

---

# 46. Sécurité

Les données sont considérées comme sensibles.

Le système devra prévoir :

- HTTPS ;
- mots de passe sécurisés ;
- hash des mots de passe ;
- contrôle d'accès ;
- expiration des sessions ;
- gestion des appareils ;
- permissions ;
- journalisation ;
- sauvegardes ;
- chiffrement des secrets ;
- protection contre les requêtes non autorisées ;
- limitation des tentatives de connexion.

Une authentification à deux facteurs devra pouvoir être activée pour les utilisateurs sensibles.

---

# 47. Sauvegardes

Les sauvegardes seront automatiques.

Prévoir :

- sauvegardes quotidiennes ;
- sauvegardes plus fréquentes des données critiques si possible ;
- rotation ;
- plusieurs versions ;
- stockage sur un emplacement distinct du serveur principal ;
- chiffrement des sauvegardes.

Il faudra pouvoir restaurer :

- l'ensemble de la base ;
- une sauvegarde à une date donnée ;
- certains fichiers selon les possibilités.

---

# 48. Restauration et reprise

Le système devra prévoir une procédure documentée pour :

- restauration après erreur ;
- panne serveur ;
- corruption de données ;
- perte d'un serveur ;
- erreur humaine ;
- mise à jour défectueuse.

Le système devra minimiser la perte de données.

---

# 49. Documents et pièces jointes

L'application devra permettre d'ajouter des documents à plusieurs objets.

Exemples :

- facture fournisseur ;
- facture client ;
- bon de livraison ;
- document de douane ;
- photo ;
- contrat ;
- document de garantie ;
- justificatif de frais ;
- document d'importation.

Formats courants acceptés :

- PDF ;
- JPG ;
- PNG ;
- XLSX ;
- CSV ;
- DOCX si nécessaire.

---

# 50. Recherche

Une recherche globale devra permettre de rechercher rapidement par :

- nom ;
- SKU ;
- référence ;
- QR code ;
- numéro de série ;
- fournisseur ;
- client ;
- facture ;
- commande ;
- importation ;
- emplacement.

La recherche devra être rapide même avec un volume important de données.

---

# 51. Filtres

Les listes devront pouvoir être filtrées par exemple par :

- date ;
- catégorie ;
- fournisseur ;
- client ;
- entrepôt ;
- emplacement ;
- stock ;
- statut ;
- utilisateur ;
- montant ;
- devise.

---

# 52. Notifications

Le système devra disposer d'un centre de notifications.

Exemples :

- stock faible ;
- rupture ;
- commande attendue ;
- retard fournisseur ;
- conflit de synchronisation ;
- inventaire terminé ;
- paiement en retard ;
- facture impayée ;
- anomalie de stock ;
- échec de sauvegarde ;
- action nécessitant validation.

Des canaux supplémentaires pourront être intégrés plus tard :

- email ;
- SMS ;
- WhatsApp ;
- notifications push.

---

# 53. Interface utilisateur

Style souhaité :

**moderne, proche d'une application SaaS, mais rapide et fonctionnel.**

Principes :

- interface claire ;
- navigation simple ;
- peu de clics pour les actions fréquentes ;
- responsive ;
- adaptée aux écrans tactiles ;
- raccourcis pour les opérations fréquentes ;
- tableaux lisibles ;
- filtres rapides ;
- recherche instantanée ;
- tableaux de bord graphiques ;
- thème clair ;
- possibilité d'un thème sombre ultérieur.

La priorité devra rester la productivité plutôt que les animations décoratives.

---

# 54. Performance

L'application devra rester fluide avec un grand nombre de :

- produits ;
- mouvements ;
- factures ;
- fournisseurs ;
- clients ;
- utilisateurs.

Les listes volumineuses devront utiliser :

- pagination ;
- chargement progressif ;
- indexation base de données ;
- recherche optimisée.

---

# 55. Disponibilité et synchronisation multi-sites

L'application sera utilisée depuis plusieurs endroits géographiques.

Lorsqu'un utilisateur est connecté :

- les mouvements importants devront être visibles rapidement sur les autres appareils ;
- le stock devra être mis à jour presque immédiatement ;
- les notifications importantes devront être propagées.

Lorsqu'un appareil est hors ligne :

- les opérations seront stockées localement ;
- elles seront synchronisées automatiquement après reconnexion.

---

# 56. Exemple de journée d'utilisation

## Scénario 1 — Achat fournisseur

Un employé achète des produits chez un fournisseur.

Il ouvre l'application et crée ou sélectionne :

- fournisseur ;
- produits ;
- quantités ;
- prix ;
- devise ;
- taux de change ;
- commande ou achat.

Le système enregistre le coût initial.

Si les produits sont importés, ils sont associés à une expédition.

---

## Scénario 2 — Importation

Une commande de marchandises est expédiée depuis l'étranger.

La personne responsable crée un dossier d'importation.

Elle enregistre :

- produits ;
- quantités ;
- devises ;
- transport ;
- fret ;
- assurance ;
- douane ;
- transit ;
- taxes ;
- autres frais.

Le système répartit les frais.

Il calcule :

- coût réel par produit ;
- coût rendu unitaire ;
- marge ;
- prix suggéré.

---

## Scénario 3 — Réception

La marchandise arrive à l'entrepôt.

L'employé :

1. ouvre le dossier d'importation ;
2. sélectionne la réception ;
3. scanne ou sélectionne les produits ;
4. confirme les quantités ;
5. ajoute éventuellement les numéros de série ;
6. ajoute des photos ;
7. choisit l'entrepôt et l'emplacement ;
8. valide la réception.

Le stock est augmenté.

Le mouvement est historisé.

---

## Scénario 4 — Sortie interne

Un employé prend trois produits.

Il scanne le QR code.

Il choisit :

- sortie ;
- quantité ;
- destination ou affectation ;
- employé / chantier / véhicule / service ;
- motif.

Le système diminue le stock et enregistre exactement :

- qui ;
- quoi ;
- combien ;
- quand ;
- pourquoi.

---

## Scénario 5 — Vente

Un client achète plusieurs produits.

L'utilisateur :

1. recherche ou scanne les articles ;
2. choisit le client ou crée un client ;
3. applique éventuellement une remise ;
4. valide la vente ;
5. encaisse le paiement ;
6. génère la facture.

Le système :

- diminue le stock ;
- enregistre le paiement ;
- met à jour la caisse ;
- génère les écritures comptables nécessaires ;
- calcule la marge.

---

## Scénario 6 — Inventaire physique

Un responsable crée un inventaire de l'entrepôt.

Plusieurs employés utilisent leurs téléphones.

Chaque employé travaille sur une zone différente.

Ils scannent les produits et saisissent les quantités constatées.

L'application compare :

- stock théorique ;
- stock réel ;
- différence ;
- valeur de la différence.

Les écarts peuvent être vérifiés avant correction.

---

## Scénario 7 — Consultation direction

En fin de journée, la direction ouvre son tableau de bord.

Elle voit notamment :

- chiffre d'affaires du jour ;
- ventes ;
- dépenses ;
- valeur du stock ;
- produits en rupture ;
- produits faibles ;
- mouvements récents ;
- pertes ;
- marges ;
- importations en cours ;
- factures impayées.

---

# 57. Règles métiers importantes

## RM-001 — Historique obligatoire

Toute opération affectant le stock devra produire un mouvement immuable.

## RM-002 — Pas de suppression de mouvement

Un mouvement validé ne peut pas être supprimé définitivement.

## RM-003 — Correction par compensation

Une erreur devra être corrigée par une opération inverse ou corrective.

## RM-004 — Taux de change figé

Le taux utilisé lors d'une transaction doit être conservé définitivement.

## RM-005 — Numéros de série uniques

Un numéro de série ne peut être attribué à deux unités actives du même contexte d'entreprise.

## RM-006 — Stock calculé à partir des mouvements

La logique de stock devra être suffisamment robuste pour que l'historique permette d'expliquer le stock final.

## RM-007 — Actions attribuées

Toute action importante doit être reliée à un utilisateur.

## RM-008 — Hors ligne

Une opération hors ligne doit disposer d'un identifiant unique empêchant sa duplication pendant la synchronisation.

## RM-009 — Multi-entreprises

Chaque donnée métier devra appartenir à une entreprise.

## RM-010 — Facture validée

Une facture validée ne doit pas pouvoir être effacée sans trace.

---

# 58. Identifiants et prévention des doublons

Les enregistrements importants devront utiliser des identifiants universellement uniques.

Cela est particulièrement important pour :

- mouvements hors ligne ;
- factures ;
- paiements ;
- inventaires ;
- synchronisation.

Une même opération synchronisée plusieurs fois ne devra jamais être enregistrée plusieurs fois.

---

# 59. Journal technique de synchronisation

Le système devra conserver un journal comprenant :

- appareil ;
- utilisateur ;
- dernière synchronisation ;
- opérations envoyées ;
- opérations reçues ;
- erreurs ;
- conflits ;
- tentatives ;
- statut.

Une interface administrateur devra permettre de diagnostiquer les appareils non synchronisés.

---

# 60. Gestion des appareils

Le système devra identifier les appareils autorisés.

Informations possibles :

- nom de l'appareil ;
- type ;
- OS ;
- utilisateur ;
- dernière connexion ;
- dernière synchronisation ;
- statut.

Un administrateur devra pouvoir révoquer un appareil perdu ou volé.

---

# 61. API

Toutes les applications devront communiquer avec le système central via une API sécurisée.

L'API devra :

- authentifier les utilisateurs ;
- contrôler les permissions ;
- gérer les transactions ;
- synchroniser les données ;
- exposer les données nécessaires ;
- journaliser les opérations sensibles.

L'architecture devra également permettre de connecter de futures applications externes.

---

# 62. Évolutivité

Même si aucune intégration externe n'est prévue immédiatement, le système devra pouvoir évoluer vers :

- e-commerce ;
- CRM ;
- autres logiciels de caisse ;
- marketplaces ;
- API partenaires ;
- services de paiement ;
- applications logistiques ;
- scanners professionnels ;
- BI ;
- autres sociétés.

---

# 63. Internationalisation

Compte tenu de l'activité import/export et d'une possible commercialisation future, l'architecture devra être compatible avec :

- plusieurs devises ;
- plusieurs langues ;
- plusieurs formats de date ;
- plusieurs fuseaux horaires ;
- plusieurs pays ;
- plusieurs règles fiscales.

La langue initiale pourra être le français.

---

# 64. Date et heure

Toutes les opérations critiques devront enregistrer la date et l'heure de manière fiable.

Le stockage serveur devra utiliser un format de temps normalisé et l'interface affichera l'heure locale de l'utilisateur.

Cela permettra une utilisation cohérente depuis plusieurs pays.

---

# 65. Critères d'acceptation principaux

L'application sera considérée comme conforme au périmètre initial si les scénarios suivants fonctionnent correctement.

### CA-001 — Produit

Un utilisateur autorisé peut créer un produit avec photo, SKU, catégorie, coût, prix et QR code.

### CA-002 — Variante

Un utilisateur peut créer plusieurs variantes d'un même produit avec stocks distincts.

### CA-003 — QR

Un utilisateur Android peut scanner le QR d'un produit et ouvrir sa fiche.

### CA-004 — Sortie

Une sortie diminue correctement le stock et identifie l'utilisateur.

### CA-005 — Audit

L'ancienne et la nouvelle quantité sont traçables.

### CA-006 — Hors ligne

Une sortie peut être enregistrée sans Internet.

### CA-007 — Synchronisation

La sortie hors ligne est synchronisée sans doublon après reconnexion.

### CA-008 — Multi-utilisateurs

Deux utilisateurs peuvent travailler simultanément.

### CA-009 — Importation

Une importation peut regrouper plusieurs produits et plusieurs frais.

### CA-010 — Coût rendu

Le système calcule correctement le coût réel de chaque produit.

### CA-011 — Devise

Un achat en EUR/USD/CNY peut être converti en MGA avec le taux utilisé conservé.

### CA-012 — Réception

Une réception augmente correctement le stock.

### CA-013 — Inventaire

L'application compare le stock théorique au stock réel.

### CA-014 — Facturation

Une vente génère une facture et diminue le stock.

### CA-015 — Paiement

Un paiement peut être affecté à une facture.

### CA-016 — Caisse

Une vente encaissée est visible dans la caisse correspondante.

### CA-017 — Comptabilité

Les opérations configurées génèrent les écritures comptables attendues.

### CA-018 — Permissions

Un utilisateur sans droit de modification ne peut pas modifier une donnée protégée.

### CA-019 — Audit admin

Un administrateur ne peut pas faire disparaître une ancienne opération du journal d'audit.

### CA-020 — Rapport

Un rapport filtré peut être exporté en PDF, XLSX ou CSV.

---

# 66. Priorités de développement

## Phase 1 — Socle technique

- authentification ;
- entreprises ;
- utilisateurs ;
- rôles ;
- permissions ;
- API ;
- base centrale ;
- base locale ;
- synchronisation ;
- audit.

## Phase 2 — Inventaire

- produits ;
- variantes ;
- catégories ;
- unités ;
- entrepôts ;
- emplacements ;
- stocks ;
- mouvements ;
- QR codes ;
- numéros de série ;
- garanties.

## Phase 3 — Achats et importation

- fournisseurs ;
- commandes ;
- réceptions ;
- devises ;
- taux ;
- expéditions ;
- frais ;
- coût rendu ;
- prix suggéré.

## Phase 4 — Inventaire physique

- sessions d'inventaire ;
- scan ;
- comptage collaboratif ;
- écarts ;
- corrections.

## Phase 5 — Vente et facturation

- clients ;
- devis ;
- commandes ;
- factures ;
- paiements ;
- retours.

## Phase 6 — Caisse

- sessions ;
- encaissements ;
- clôtures ;
- écarts.

## Phase 7 — Comptabilité

- plan comptable ;
- journaux ;
- écritures ;
- automatisations comptables ;
- rapports.

## Phase 8 — Reporting avancé

- tableaux de bord ;
- KPI ;
- exports ;
- rapports avancés.

## Phase 9 — Industrialisation

- optimisation ;
- monitoring ;
- sauvegarde avancée ;
- sécurité ;
- multi-entreprises complet ;
- préparation à une éventuelle commercialisation.

---

# 67. Contraintes non fonctionnelles

Le système devra respecter les critères suivants :

### Fiabilité

Une opération métier validée ne doit pas être perdue.

### Traçabilité

Toutes les opérations sensibles sont auditées.

### Sécurité

Les utilisateurs ne voient et ne modifient que ce qui leur est autorisé.

### Performance

Les opérations courantes doivent rester rapides.

### Disponibilité

Le système central doit être accessible à distance lorsque Internet est disponible.

### Résilience réseau

La perte d'Internet ne doit pas empêcher les principales opérations locales.

### Maintenabilité

Le code devra être organisé, documenté et testable.

### Scalabilité

Le système devra supporter une hausse du nombre d'utilisateurs, de produits et de sociétés.

### Portabilité

Les composants devront être suffisamment découplés pour permettre des évolutions technologiques.

---

# 68. Tests obligatoires

Prévoir au minimum :

- tests unitaires ;
- tests d'intégration ;
- tests API ;
- tests de permissions ;
- tests hors ligne ;
- tests de synchronisation ;
- tests de conflits ;
- tests de calcul du coût rendu ;
- tests de devises ;
- tests d'inventaire ;
- tests de facturation ;
- tests de caisse ;
- tests comptables ;
- tests de restauration ;
- tests de charge sur les fonctions critiques.

---

# 69. Documentation

Le projet devra comporter :

- documentation utilisateur ;
- documentation administrateur ;
- documentation technique ;
- procédure d'installation ;
- procédure de sauvegarde ;
- procédure de restauration ;
- documentation API ;
- schéma de base de données ;
- description de la synchronisation ;
- règles métier ;
- procédure de déploiement.

---

# 70. Livrables attendus

- application Windows installable ;
- application Android installable ;
- interface Web ;
- serveur/API ;
- base de données ;
- système de synchronisation ;
- module inventaire ;
- module fournisseurs ;
- module importation ;
- module facturation ;
- module caisse ;
- module comptabilité ;
- module rapports ;
- module administration ;
- système de sauvegarde ;
- documentation ;
- tests.

---

# 71. Hypothèses retenues

Certaines réponses impliquent les choix suivants :

1. L'application Windows sera bien une application installable avec icône.
2. Android sera une application installable avec icône.
3. Une interface Web sera également prévue pour l'accès à distance.
4. MGA sera la devise comptable de référence initiale, tout en restant configurable.
5. CNY correspond à la devise chinoise Renminbi/Yuan.
6. L'application sera pensée dès l'origine pour plusieurs entreprises, même si une seule est utilisée au lancement.
7. La suppression physique des opérations comptables et de stock critiques sera interdite.
8. Le mode hors ligne ne sera pas un simple cache de consultation : il devra permettre la création réelle d'opérations.
9. Les règles comptables exactes et réglementaires du pays devront être validées séparément avant mise en production comptable.

---

# 72. Résultat attendu

Le produit final devra devenir le **système central de gestion opérationnelle de l'entreprise**.

Il devra permettre à tout moment de répondre rapidement à des questions telles que :

- Combien avons-nous de ce produit ?
- Où se trouve-t-il ?
- Combien vaut notre stock ?
- Qui a sorti ce produit ?
- Où est passée cette unité ?
- Quel est son numéro de série ?
- Quel fournisseur l'a vendu ?
- Dans quelle importation est-il arrivé ?
- Quel était son coût d'achat ?
- Quel est son coût réel rendu ?
- À quel prix devrions-nous le vendre ?
- Quelle marge avons-nous réalisée ?
- Quelles marchandises sont actuellement en transit ?
- Quelles factures restent impayées ?
- Quel est le chiffre d'affaires aujourd'hui ?
- Quelle est la valeur totale du stock ?
- Quelles pertes avons-nous enregistrées ?
- Quels produits doivent être commandés ?
- Quelles opérations ont été réalisées par chaque utilisateur ?
- Que s'est-il passé pendant une période donnée ?

L'objectif est d'obtenir une application fiable, traçable, multi-utilisateurs, multi-sites, hors ligne et connectée, capable de remplacer progressivement les fichiers Excel tout en constituant une base solide pour la croissance future de l'entreprise.
