Vous avez raison d'être exigeant. Pour qu'un document serve de référence absolue ("Bible du projet"), il ne doit pas se contenter des dernières modifications, il doit **tout** reprendre.

J'ai réagrégé l'intégralité de nos échanges (Utilisateurs, Finance, Admin, Workflow) pour produire le **Document d'Architecture des Données Complet**.

C'est ce fichier que vous pourrez donner à n'importe quel développeur pour qu'il comprenne tout le système sans vous poser de questions.

---

# **MASTER DATA BLUEPRINT : ARCHITECTURE COMPLÈTE**

Projet : Plateforme d'Analyse Casting

Date : 14 Décembre 2025

Statut : DÉFINITIF POUR DÉVELOPPEMENT

---

## **1\. CONFIGURATION GLOBALE & RÉFÉRENTIELS**

*Ces données définissent les règles du jeu et les listes de choix.*

### **1.1 PlatformConfig (Singleton \- Table Unique)**

*Table de configuration gérée par l'Admin. Une seule ligne dans la BDD.*

* **Finances :**  
  * platform\_commission\_percent (Float): % prélevé par la plateforme.  
  * vat\_percent (Float): Taux de TVA applicable.  
* **Délais & Sécurité :**  
  * global\_max\_delay\_days (Int): Délai max absolu avant annulation (sécurité).  
  * express\_option\_extra\_cost\_percent (Float): Majoration suggérée ou imposée pour l'express.

### **1.2 Référentiels Métiers (Admin)**

* **JobTitle** : Métiers des Pros (ex: "Directeur de Casting", "Agent").  
* **Specialty** : Spécialités (ex: "Théâtre", "Cinéma", "Publicité").  
* **Domain** : Catégories plus larges si besoin (ex: "Fiction", "Voix").

---

## **2\. GESTION DES SERVICES (CATALOGUE)**

*Structure polymorphique pour gérer les règles Photos/CV vs Vidéos.*

### **2.1 AbstractServiceType (Table Mère)**

* id, name (ex: "Photos"), slug (ex: "photos"), is\_active (Bool).  
* description (Text): Explication pour le candidat.  
* is\_express\_allowed (Bool): Peut-on demander une livraison 48h sur ce service ?

### **2.2 UnitServiceType (Table Fille \- Quantité)**

*Pour Photos & CV.*

* **Stockage :** library\_quota (Max fichiers stockés), max\_weight\_mb.  
* **Règles Commande :** order\_min\_qty, order\_max\_qty (Combien on peut en envoyer au pro).  
* **Prix :** base\_quantity (Le forfait, ex: 10 photos).

### **2.3 DurationServiceType (Table Fille \- Durée)**

*Pour Scènes, Démo, Présentation.*

* **Stockage :** library\_quota, max\_weight\_mb.  
* **Règles Commande :** order\_min\_files, order\_max\_files.  
* **Prix :** base\_duration\_min (Le forfait, ex: 3 minutes).

---

## **3\. LES UTILISATEURS**

### **3.1 AbstractUser (Classe Mère)**

* email (Unique), password (Hashed).  
* roles (JSON): \['ROLE\_CANDIDATE'\] ou \['ROLE\_PRO'\].  
* is\_verified (Bool): Email validé.  
* created\_at, deleted\_at (Soft delete).

### **3.2 Candidate (Le Comédien)**

* first\_name, last\_name.  
* gender, birth\_date.  
* phone\_number (Optionnel, non partagé par défaut).  
* **Relation :** OneToMany vers MediaObject (Sa bibliothèque).  
* **Relation :** OneToMany vers Order (Ses commandes).

### **3.3 Professional (Le Pro)**

* **Identité Publique :**  
  * first\_name, last\_name, avatar\_path.  
  * biography (Rich Text).  
  * job\_title\_id, specialties (ManyToMany).  
  * **Localisation :** city, zip\_code, department\_name, department\_code.  
* **Identité Légale (B2B) :**  
  * company\_name, siret\_number.  
  * billing\_address.  
  * stripe\_account\_id (Connect), is\_stripe\_verified (KYC).  
* **Paramètres "Magasin" :**  
  * standard\_delay\_days (3 à 15 jours).  
  * is\_express\_enabled (Bool), express\_premium\_percent (Float).  
  * max\_active\_orders (Capacité de charge).  
  * status (Enum: ACTIVE, UNAVAILABLE, SUSPENDED).  
  * **`unavailable_until` (DateTime, Nullable) — NOUVEAU**  
    * *Fonctionnement :*  
      * Si `NULL` et statut `UNAVAILABLE` : Indisponibilité indéfinie (Le Pro doit revenir manuellement).  
      * Si `DATE` renseignée : Le Pro est indisponible jusqu'à cette date précise.

### **3.4 ProService (Le Catalogue du Pro)**

*Liaison Pro \<-\> ServiceType.*

* is\_active (Le pro propose-t-il ce service ?).  
* base\_price (Prix du forfait).  
* supplement\_price (Prix unitaire/minute sup).

---

## **4\. LA BIBLIOTHÈQUE (MEDIA CENTER)**

### **4.1 MediaObject**

* id, owner\_id (Candidat).  
* file\_path (S3 Key).  
* original\_name, mime\_type, size.  
* **category** (Enum): PHOTO, CV, VIDEO\_SCENE, VIDEO\_DEMO, VIDEO\_PRES.  
* duration (Int, secondes): Pour les vidéos uniquement.  
* created\_at.

---

## **5\. LE FLUX DE COMMANDE (TRANSACTIONS)**

### **5.1 Order (La Commande Globale)**

* reference (String unique).  
* candidate\_id, professional\_id.  
* status (Enum: CART, PENDING\_PAYMENT, PAID\_PENDING\_PRO, IN\_PROGRESS, DELIVERED, DISPUTE, COMPLETED, CANCELLED).  
* created\_at, paid\_at, delivered\_at.  
* is\_express (Bool).  
* **Finances :**  
  * total\_amount\_ttc (Payé par le candidat).  
  * **`applied_vat_percent`** (Float) : Le taux de TVA en vigueur au moment de la validation de la commande (ex: 20.0).  
  * commission\_amount (Revenu plateforme).  
  * pro\_amount (Revenu net pro).

### **5.2 OrderLine (Ligne de service)**

* service\_type\_id (Type de service acheté).  
* **Snapshot Prix (Immuable) :**  
  * unit\_price\_frozen, base\_price\_frozen.  
  * quantity\_billed (Nb photos ou Minutes facturées).  
  * line\_total\_amount.  
* instructions (Commentaire candidat).  
* **Relation :** ManyToMany vers MediaObject (Les fichiers à analyser).

### **5.3 PaymentTransaction (Traçabilité)**

* order\_id.  
* stripe\_payment\_intent\_id.  
* amount, currency (EUR).  
* status (SUCCESS, FAILED, REFUNDED).

### **5.4 Invoice (Documents)**

* order\_id.  
* type (Enum: CANDIDATE\_RECEIPT, PRO\_COMMISSION).  
* file\_path (PDF).  
* invoice\_number.

---

## **6\. L'ANALYSE & LE LIVRABLE**

### **6.1 Analysis (Détail par Ligne)**

*L'évaluation d'un service spécifique (ex: Les photos).*

* order\_line\_id.  
* content (Feedback textuel).  
* rating (1-5).  
* **ranking\_data** (JSON Array): Ordre de préférence des médias \[id\_media\_1, id\_media\_3, id\_media\_2\].  
* meet\_interest (Enum: NO, MAYBE, YES): Sur la base de ce matériel uniquement.

### **6.2 OrderConclusion (Synthèse Globale)**

*La conclusion de toute la commande.*

* order\_id.  
* global\_synthesis (Résumé général).  
* global\_rating (Note globale du profil).  
* **meet\_opinion** (Enum): Sentiment global ("Envie de rencontrer ?").  
* **wants\_to\_connect** (Bool): Déclencheur email de mise en relation.

### **6.3 Dispute (Litiges)**

* order\_id.  
* reason (Enum/Text).  
* status (OPEN, RESOLVED\_REFUND, RESOLVED\_REDELIVER).  
* admin\_comment.

---

## **7\. DIAGRAMME DE RELATION (ERD)**

Ce diagramme est la représentation visuelle de tout ce qui est écrit ci-dessus.

1. **L'Utilisateur (Pro)** configure ses **ProServices**.  
2. **L'Utilisateur (Candidat)** remplit ses **MediaObjects** (Bibliothèque).  
3. Il crée une **Order** composée de **OrderLines**.  
4. Chaque ligne lie des **MediaObjects** à un **ServiceType**.  
5. Le Pro remplit une **Analysis** par ligne et une **OrderConclusion** globale.  
6. **PaymentTransaction** et **Invoice** tracent l'argent.

C'est complet. **Est-ce que cette version "Blueprint Master" est validée ?**

