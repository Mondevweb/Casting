# Document de Transfert de Contexte Technique : Casting App

Ce document sert de "référentiel mémoire" exhaustif. Il est conçu pour permettre à un développeur ou une IA repreneuse de comprendre instantanément l'historique, l'architecture métier profonde et les contraintes techniques du backend Symfony (API Platform) et du frontend Vue.js (Pinia).

---

## 🌍 0. Contexte Global et But du Projet

**Casting App** est une plateforme de mise en relation B2C et B2B dédiée aux métiers du spectacle, de l'audiovisuel et du casting. 

L'objectif principal de l'application est de permettre à des **Candidats** (acteurs, figurants, modèles) de commander et d'acheter des **Services** (photoshoot, réalisation de bande démo, coaching, etc.) auprès de **Professionnels** certifiés (photographes, réalisateurs, directeurs de casting, recruteurs). 

La plateforme agit comme un **tiers de confiance transactionnel** :
* Elle liste les professionnels via un catalogue filtrable.
* Elle permet aux professionnels de configurer leurs propres prestations (avec des tarifs unitaires ou basés sur le temps de prestation).
* Elle gère le tunnel de commande, l'upload de fichiers médias (nécessaires pour la réalisation des services), et répartit les revenus (la plateforme prélève une **commission** sur chaque transaction, le reste formant le revenu net du professionnel).

---

## 🏗️ 1. État Actuel du Projet & Architecture Métier

Le projet est fonctionnel sur ses briques de base : gestion des profils professionnels, catalogue avec filtres client, typologie de services et tunnel de création de commande (Order) avec attachement de médias.

### Modélisation des Services B2B (Polymorphisme)
Pour gérer la différence entre un service facturé à l'unité (ex: une photo) et un service facturé au temps (ex: une vidéo de 3 minutes), nous avons mis en place une architecture polymorphique avec Doctrine (InheritanceType `JOINED`).
* **`AbstractServiceType`** : La classe parente abstrait la définition du service (Nom, Slug, Actif/Inactif, `isExpressAllowed`, Relation ManyToMany avec les formats de médias `MediaFormat`).
* **`DurationServiceType`** : Hérite de l'abstrait. Introduit la notion de durée (`minDurationMinutes`, `maxDurationMinutes`) et un prix au palier de temps ou minute supplémentaire (`extraPricePerMinute`).
* **`UnitServiceType`** : Hérite de l'abstrait. Simplifié, la quantité représente des unités indépendantes.
* Le système repose sur une colonne discriminante `discriminator` pour l'hydratation automatique des bonnes sous-classes.

### Moteur de Commandes (`Order.php`)
L'entité `Order` est le cœur financier et transactionnel du système, faisant le pont entre un `Candidate` et un `Professional`, avec des relations One-to-Many vers `OrderLine`.
* **Workflow d'État** : Piloté par l'Enum `OrderStatus` (CART par défaut).
* **Répartition Financière** : Chaque commande inclut de façon *snapshotée* le prix TTC payé par le candidat (`totalAmountTtc`), le taux de TVA appliqué à l'instant T (`appliedVatPercent`), la commission de la plateforme (`commissionAmount`) et le revenu net du pro (`proAmount`). Tous les montants sont stockés en entiers (centimes) pour éviter les erreurs de virgule flottante.

### Moteur de Catalogue (Vue + Pinia)
La recherche du composant `CatalogView` ne fait **aucune requête réseau lors du filtrage**.
* Tout le pool des professionnels (limité en volume) est fetché au montage.
* Les filtres de Pinia (`searchQuery`, `jobTitles`, `specialties`) s'appliquent côté client.
* Un `getter` Pinia exclut d'office de l'affichage les professionnels n'ayant aucune prestation active (`pro.proServices.some(service => service.isActive === true)`).

### Traitement Asynchrone des Vidéos
Nous déléguons le stockage des médias à Bunny.net. Lors de l'upload, nous générons un message `ProcessVideoMessage` qui est envoyé dans le bus de messages Symfony Messenger. L'objectif est de ne pas bloquer le thread PHP pendant la communication ou le transcodage avec le CDN.

---

## 🧠 2. Historique des Blocages & Décisions de Contournement

Voici les crises techniques récentes que nous avons traversées et résolues. **Ces patterns ne doivent surtout pas être modifiés sans précaution.**

**1. Sérialisation API Platform sur l'Entité `Order` (Erreur 500)**
* **Problème :** Lors de la création d'une commande via un POST sur `/api/orders`, API Platform crashait car il n'arrivait pas à désérialiser ou lier certains champs (`candidate`, `appliedVatPercent`). Il tentait de créer de nouveaux sous-objets plutôt que de lier via l'IRI.
* **Solution :** Nous avons drastiquement restreint le `denormalizationContext` (mode écriture) par rapport au `normalizationContext` (mode lecture). Les relations complexes nécessitent désormais de passer strictement l'IRI (identifiant de ressource) depuis le Front. De plus, `appliedVatPercent` a été retiré des groupes d'écriture pour être calculé post-soumission (ou via le StateProcessor d'API Platform) afin d'éviter la falsification de la TVA par le client. Le `forceEager: false` a parfois été nécessaire pour alléger les jointures massives générées par Doctrine.

**2. Failures de Sécurité sur les Uploads de Médias (Erreurs 401 & 404)**
* **Problème :** Lors de l'envoi de fichiers vidéos lourds en `multipart/form-data`, le serveur jetait des 401 (Non autorisé) ou le composant Vue se trompait de route (404).
* **Solution :** Le client Axios perdait l'en-tête `Authorization: Bearer <TOKEN>` lors de la construction du FormData custom. Le store d'authentification a dû être explicitement rappelé pour injecter le JWT. Côté backend, nous avons bypassé certaines restrictions génériques d'API Platform pour utiliser un Controller Symfony natif très spécifique à l'upload.

**3. Pertes de Persistance sur les Fixtures (`AppFixtures.php`)**
* **Problème :** Les tests et la base de données de dev généraient des fatal errors de typage ("Cannot instantiate abstract class").
* **Solution :** Les fixtures Symfony essayaient d'instancier la classe `AbstractServiceType` mère. Nous devions explicitement instancier `UnitServiceType` ou `DurationServiceType` selon la prestation (Ex: une démo vidéo = Duration, une photo = Unit), puis les lier au professionnel.

---

## 🚀 3. "To-Do List" & Chantiers En Cours

Ce qui était littéralement sur le point d'être implémenté :

1. **Intégration du Tunnel de Paiement Stripe** :
   * L'entité `Order` est prête ; le champ de routage `stripeAccountId` est présent sur l'entité `Professional`.
   * Un endpoint de paiement dédié a été préparé `POST /orders/{id}/pay` lié à un `OrderPaymentController` (attente d'implémentation de l'intent Stripe Checkout).
2. **Extensions Doctrine de Sécurité** :
   * L'endpoint `GET /orders` est actuellement ouvert (avec un commentaire dans le code appelant à la prudence). La prochaine étape est de créer une **QueryExtension** d'API Platform pour injecter un `AND WHERE user_id = :current_user_id` automatiquement afin que les candidats ne voient QUE leurs propres commandes, ce qui est plus élégant que la sécurité par Voter.
3. **Mise à Jour Dynamique du Panier** :
   * Des ajustements sont encore nécessaires sur le front pour s'assurer que le calcul du total final TTC se rafraîchisse instantanément lorsque la quantité d'une `OrderLine` est modifiée typiquement sur des services de type `Duration` où on ajoute des "minutes" en plus.

---

## ⚠️ 4. Règles Strictes pour l'IA Repreneuse

En tant qu'IA, tu seras sanctionnée si tu ne respectes pas ces dogmes :

* **NE JAMAIS RÉACTIVER LA PAGINATION API SUR LES PROFESSIONNELS** : Le composant `CatalogView.vue` compte sur la réception d'un Array complet de la base de données. API Platform est forcé en `paginationEnabled: false` sur cette ressource. Si tu le réactives, la recherche du catalogue côté front sautera.
* **IRIs UNIQUEMENT POUR LES RELATIONS "TO-ONE"** : En POST/PATCH vers API Platform (depuis le front), tu ne dois passer que l'URL relative de la ressource enfant, pas d'ID brut, ni d'objet JSON imbriqué, ex : `"professional": "/api/professionals/12"`.
* **RESPECT DES GROUPES DE SÉRIALISATION** : Ne crée pas de boucle récursive de JSON. Ne modifie pas les assertions `#[Groups]` dans les entités sans t'assurer que le `normalizationContext` et le `denormalizationContext` du contrôleur appelant ne risquent pas une `MaxDepthException`.
* **CENTIMES SEULEMENT** : Tous les montants financiers (prix, montant TTC, proAmount, commission, suppléments de minutes) DOIVENT être manipulés et persistés en `Integer` (centimes d'euros), puis divisés par 100 uniquement à l'affichage sur les composants Vue. Évite la virgule flottante côté PHP/Doctrine.

Bon courage pour la reprise !
