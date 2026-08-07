# Charte du projet AlpineCommerce

> Document fondateur du projet : vision, mission, contexte, cahier des charges v1.0
> et analyse fonctionnelle. Il regroupe l'ancien `00_PROJECT_VISION.md`, le cahier des
> charges du sprint de finalisation (`12`) et l'analyse fonctionnelle associée (`13`).

---

## 1. Vision

AlpineCommerce a **deux objectifs complémentaires** :

### 1.1 Une plateforme e-commerce professionnelle

Projet e-commerce professionnel basé sur **Adobe Commerce (Magento 2 Open Source)**.

Nous ne construisons pas un nouveau moteur e-commerce.
Nous ne remplaçons pas Magento.
Nous exploitons Magento comme cœur de l'application et y ajoutons des fonctionnalités métier spécifiques via des modules propres.

### 1.2 Une référence open source pour apprendre Magento 2

Le projet a pour ambition de devenir **l'une des meilleures références open source** pour
apprendre Adobe Commerce / Magento 2 : un dépôt qu'un développeur débutant ou intermédiaire
peut cloner, lire, parcourir et comprendre **progressivement**.

> **Le dépôt doit permettre de passer de « je ne connais pas Magento » à « je suis capable
> de développer un module professionnel ».**
>
> Chaque module est un chapitre du cours. La documentation explique non seulement **ce que
> fait le code**, mais surtout **pourquoi** ce choix d'architecture a été fait, quelles sont
> les alternatives, et quelles erreurs éviter.

### 1.3 Roadmap d'exécution (4 phases)

| Phase | Contenu | État |
|---|---|---|
| **A — Standards** | Engineering Bible, Learning Path, Backlog, README pédagogique | ✅ en cours (2026-08) |
| **B — Développement métier** | Finaliser les 13 modules de la v1.0 (cap ferme) | à venir |
| **C — Harmonisation** | Refactoriser les anciens modules (1 sprint / module) | après v1.0 |
| **D — Documentation pédagogique** | README modules, schémas, exercices | après harmonisation |

---

## 2. Contexte

Le e-commerce moderne exige des fonctionnalités avancées que Magento ne propose pas nativement en Open Source :

- Un système de fidélité complet
- Un blog intégré au catalogue
- Un module FAQ optimisé pour le SEO
- Une gestion avancée du RGPD
- Un localisateur de magasins physique
- Une option de retrait en magasin (Store Pickup)
- Des pages légales dynamiques
- Des balises hreflang pour le SEO multi-boutiques
- Une validation TVA européenne automatisée

Au lieu d'acheter ces modules à des éditeurs tiers, nous les développons en interne sous le vendor `AlpineCommerce`.

---

## 3. Objectifs

### Objectifs métier

- Fournir une expérience e-commerce complète et professionnelle
- Disposer de fonctionnalités différenciantes (fidélité, blog, FAQ, RGPD)
- Maîtriser l'ensemble de la stack Adobe Commerce
- Être capable de maintenir et faire évoluer chaque module indépendamment

### Objectifs techniques

- Produire du code propre, testable et maintenable
- Respecter les standards Adobe Commerce et PHP (PSR-12)
- Utiliser les patterns officiels Magento : Service Contracts, Repository, DI, Plugins, Observers
- Assurer la compatibilité avec les futures versions de Magento
- Garantir la performance et la sécurité

### Objectifs pédagogiques

- Comprendre l'architecture Magento en profondeur
- Savoir quand étendre Magento vs quand créer un nouveau module
- Maîtriser les concepts : Service Contracts, Resource Models, UI Components, Layout XML
- Apprendre les bonnes pratiques d'une équipe Adobe Commerce professionnelle

---

## 4. Philosophie

### Magento est le cœur

Magento fournit nativement :

- Catalogue produits
- Gestion des clients
- Processus de commande
- Paiements et livraisons
- CMS
- Inventaire (MSI)
- REST API
- Indexers et cache

Nous **n'écrivons jamais** de code pour remplacer ces fonctionnalités.
Nous les utilisons telles quelles et les étendons uniquement si nécessaire.

### Chaque module a une seule responsabilité

Un module AlpineCommerce ne fait qu'une chose et il le fait bien.

```
AlpineCommerce_Blog          → Gestion du blog
AlpineCommerce_Faq           → Gestion de la FAQ
AlpineCommerce_Gdpr          → Conformité RGPD
AlpineCommerce_LoyaltyProgram → Programme de fidélité
...
```

### Étendre avant de créer

Avant de créer un module, nous vérifions systématiquement si Magento native ne propose pas déjà la fonctionnalité.

- Si Magento le fait → nous étendons via Plugin, Observer, Layout XML
- Si Magento ne le fait pas → nous créons un module AlpineCommerce

### Documentation comme Source of Truth

Toute décision architecturale est documentée.
Tout le code doit respecter la documentation.
Toute modification de la documentation est tracée et validée.

### Pourquoi AlpineCommerce existe ?

- **Indépendance** : nos modules ne dépendent pas d'un éditeur tiers
- **Propriété intellectuelle** : le code nous appartient
- **Évolutivité** : nous contrôlons la roadmap et les priorités
- **Apprentissage** : construction d'une expertise Adobe Commerce interne
- **Réutilisabilité** : les modules sont conçus pour être déployés sur d'autres projets Magento
- **Communauté** : partager une référence open source de qualité qui manque à la communauté Magento (le « pourquoi » des choix d'architecture)

### Le critère de qualité définitif

Avant toute implémentation, la question à se poser est :

> « Est-ce qu'un développeur qui découvre Magento comprendra facilement **pourquoi** cette
> solution a été choisie ? »

Si la réponse est non, on améliore le code ou la documentation avant de considérer le travail terminé.

---

## 5. Cahier des charges v1.0

> **Sprint** : Finalisation fonctionnelle des 13 modules AlpineCommerce
> **Version cible** : v1.0 stable
> **Date** : 2026-08-06
> **Statut** : En attente de validation

### 5.1 Besoin métier

AlpineCommerce dispose de 13 modules fonctionnels sur le plan du code, mais plusieurs d'entre eux présentent des **incomplétudes fonctionnelles** qui empêchent la plateforme d'être utilisable en production.

Le besoin métier est de **finaliser chaque module** pour qu'il soit :

- **Opérationnel** : un administrateur peut utiliser l'interface backend pour configurer et gérer le module.
- **Complet** : toutes les fonctionnalités métier définies dans la roadmap sont implémentées et accessibles.
- **Stable** : le module ne présente pas de crash, d'erreur PHP, ou de comportement incohérent.
- **Conforme** : l'interface admin respecte les standards Adobe Commerce 2.4.8 (UI Components, ACL, validation).

Aujourd'hui, 7 modules sur 13 présentent des **blocages fonctionnels** :

| Module | Blocage fonctionnel |
|---|---|
| **Gdpr** | Aucune interface admin pour consulter/exporter les consentements. |
| **EuVat** | Aucune interface admin pour consulter l'historique des validations TVA. |
| **LoyaltyProgram** | Aucune interface admin pour configurer le programme ou consulter les soldes clients. |
| **StorePickup** | Aucune interface admin pour gérer les points de retrait. |
| **StoreLocator** | Aucune interface admin pour gérer les magasins. Couplage fort avec StorePickup. |
| **Hreflang** | Module 100% configuration, mais son périmètre exact (SEO pur ou gestion d'entités) n'est pas clair. |
| **Training** | Module de démonstration incohérent : data patch créant des store views, pas d'interface. |

### 5.2 Fonctionnalités attendues par module à finaliser

#### GDPR — Gestion des consentements RGPD

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Listing des consentements** | Interface admin listant tous les consentements enregistrés (customer, date, type, IP, statut). | Critique |
| **Export des données** | Interface admin permettant d'exporter les consentements d'un client (RGPD : droit à la portabilité). | Critique |
| **Configuration** | Page de configuration système (durée de rétention, consentements requis, anonymisation auto). | Haute |
| **ACL** | Permissions granulaire : consulter les logs, exporter, configurer. | Haute |

#### EuVat — Validation TVA européenne

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Historique des validations** | Interface admin listant les validations TVA effectuées (pays, numéro, résultat, date). | Haute |
| **Configuration** | Page de configuration système (pays activés, mode strict/validation, cache). | Haute |
| **ACL** | Permissions : consulter l'historique, configurer. | Haute |

#### LoyaltyProgram — Programme de fidélité

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Configuration du programme** | Interface admin pour définir les règles (points par euro dépensé, valeur du point, seuils). | Critique |
| **Consultation des soldes** | Interface admin permettant de rechercher un client et voir son solde de points, historique des transactions. | Critique |
| **Gestion des transactions** | Interface admin pour consulter, filtrer, annuler des transactions de points. | Haute |
| **ACL** | Permissions : configurer, consulter soldes, gérer transactions. | Haute |

#### StorePickup — Retrait en magasin

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Gestion des points de retrait** | Interface admin CRUD complète pour gérer les magasins (nom, adresse, horaires, capacités, statut). | Critique |
| **Disponibilités** | Interface admin pour gérer les créneaux de retrait (jours, heures, capacité par créneau). | Haute |
| **Configuration** | Page de configuration système (frais de retrait, délai avant retrait, activation/désactivation). | Haute |
| **ACL** | Permissions : gérer les magasins, gérer les disponibilités, configurer. | Haute |

#### StoreLocator — Localisateur de magasins

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Gestion des magasins** | Interface admin CRUD pour gérer les magasins (nom, adresse, géolocalisation, horaires, statut). | Critique |
| **Carte frontend** | Page frontend affichant la carte des magasins avec recherche par géolocalisation ou adresse. | Haute |
| **Configuration** | Page de configuration système (fournisseur de carte, rayon de recherche, unité). | Haute |
| **ACL** | Permissions : gérer les magasins, configurer. | Haute |

#### Hreflang — SEO multi-boutiques

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Clarification du périmètre** | Décider si le module reste 100% configuration ou s'il gère des entités métier. | Critique |
| **Configuration** | Page de configuration système (activation par store view, langue par défaut, x-default). | Haute |
| **Balises hreflang** | Injection automatique des balises `<link rel="alternate" hreflang="...">` dans le head. | Haute |
| **ACL** | Permissions : configurer. | Haute |

#### Training — Module de démonstration

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Clarification du rôle** | Définir si c'est un module de démo pour apprenants ou un module fonctionnel. | Critique |
| **Interface cohérente** | Supprimer le data patch créant des store views (anormal). | Haute |
| **Fonctionnalités** | Si module de démo : implémenter les fonctionnalités promises. Si module fonctionnel : aligner sur les standards. | Haute |

### 5.3 Modules déjà stables (6)

Les modules suivants sont considérés comme fonctionnellement stables. Aucune nouvelle fonctionnalité n'est demandée dans ce sprint :

| Module | Statut |
|---|---|
| Blog | ✅ Stable |
| Faq | ✅ Stable |
| LegalPages | ✅ Stable |
| ProductQuestions | ✅ Stable |
| ProductReviews | ✅ Stable |
| ProductLabels | ✅ Stable |

### 5.4 Contraintes techniques

**Standards obligatoires** (Charte du projet) :
- Magento 2.4.8, PSR-12, `declare(strict_types=1)`
- Dependency Injection uniquement
- Service Contracts pour toute logique métier
- Repository Pattern, ResourceModels, Collections
- `db_schema.xml` (pas de `InstallSchema`/`InstallData`)
- ACL complet, UI Components pour l'admin
- REST API si exposition nécessaire, Layout XML pour le frontend
- Aucun ObjectManager, aucun code legacy

**Contraintes d'architecture** :
- **Étendre Magento, jamais le remplacer**
- **Un module = une responsabilité**
- **Homogénéité** : tous les modules doivent donner l'impression d'avoir été développés par une seule équipe
- **Compatibilité** : ne pas casser les fonctionnalités ni les données existantes

**Contraintes de sécurité** :
- Toutes les routes admin protégées par ACL
- Entrées utilisateur validées et échappées
- Aucune fuite d'information sensible sans contrôle d'accès
- Conformité RGPD pour le module Gdpr (droit à l'oubli, droit à la portabilité)

**Contraintes de données** :
- Les modifications de tables core (`quote`, `sales_order`) documentées et sécurisées
- Data patches idempotents
- Aucune perte de données existante pendant les migrations

### 5.5 Critères d'acceptation généraux

| Critère | Description | Acceptation |
|---|---|---|
| **Interface admin fonctionnelle** | L'administrateur peut accéder à toutes les fonctionnalités du module via le backend. | Menu accessible, pages chargent sans erreur, CRUD fonctionnel. |
| **ACL opérationnelle** | Les permissions sont définies et appliquées. | Un utilisateur sans permission ne peut pas accéder aux pages. |
| **Respect des standards** | Le code respecte la Charte du projet. | `strict_types=1`, DI, pas d'ObjectManager, pas de legacy. |
| **Pas de régression** | Les modules déjà stables continuent de fonctionner. | Tests manuels des 6 modules stables. |
| **Cohérence visuelle** | L'interface admin ressemble aux autres modules AlpineCommerce. | Même style que le module canonique (Faq). |

### 5.6 Hors périmètre v1.0

- **Tests automatisés** (Unit, Integration, Functional, API) : sprint dédié
- **Documentation pédagogique** (README modules, schémas, exercices) : sprint dédié
- **Harmonisation de la dette technique** (préfixe tables, data patches, audit XSD formulaires) : sprint dédié
- **Nouveaux modules** : interdits jusqu'à la v1.0 stable
- **Optimisation des performances** : non bloquant pour la v1.0

---

## 6. Analyse fonctionnelle v1.0

### 6.1 Méthode d'analyse

Pour chaque module, la grille suivante est appliquée :

| Critère | Question |
|---|---|
| **Besoin métier** | Quel problème résout cette fonctionnalité ? |
| **Solution native** | Magento propose-t-il déjà cette fonctionnalité ? |
| **Indispensable v1.0** | Sans cette fonctionnalité, la plateforme est-elle utilisable en production ? |
| **Reportable** | Peut-elle être ajoutée dans un sprint ultérieur sans bloquer la v1.0 ? |
| **Impact utilisateur** | Qui est affecté et comment ? |
| **Impact architecture** | La fonctionnalité modifie-t-elle l'architecture existante ? |
| **Priorité** | Critique / Haute / Moyenne / Faible |
| **Recommandation** | Inclure dans v1.0 ou reporter ? |

### 6.2 Synthèse par module

| Module | Fonctionnalité v1.0 | Fonctionnalité v1.1 | Justification globale |
|---|---|---|---|
| **GDPR** | Listing admin + Export admin + ACL | Anonymisation admin + Config système | Le module métier existe, il manque l'interface admin pour être exploitable. |
| **EuVat** | Historique admin + ACL étendue | Config avancée | Le module métier existe, il manque la visibilité sur les validations. |
| **LoyaltyProgram** | Configuration + Consultation soldes + ACL + Menu | Gestion transactions + Config avancée | Le cœur fonctionne (checkout), il manque la configuration et la visibilité admin. |
| **StorePickup** | CRUD magasins + ACL + Menu | Disponibilités/créneaux + Config avancée | Le checkout fonctionne, il manque les magasins à gérer. |
| **StoreLocator** | Décision architecture + CRUD magasins + Carte frontend + ACL + Menu | Recherche proximité + Config | Module partiellement implémenté avec mauvais couplage. À restructurer. |
| **Hreflang** | Clarification périmètre (config-only) + validation injection balises | Mapping manuel langue-store | Module fonctionnellement complet. Décision d'architecture à documenter. |
| **Training** | Clarification rôle + Suppression data patch | Alignement standards (si fonctionnel) | Module incohérent. Nécessite une décision de périmètre. |

### 6.3 Périmètre v1.0 (fonctionnalités incluses)

| ID | Module | Fonctionnalité | Priorité |
|---|---|---|---|
| V1-01 | GDPR | Listing admin des consentements (UI Component + controllers + ACL) | Critique |
| V1-02 | GDPR | Export admin RGPD (bouton dans le listing) | Haute |
| V1-03 | EuVat | Historique admin des validations (UI Component + controllers + menu + ACL) | Haute |
| V1-04 | LoyaltyProgram | Configuration des règles (system.xml) | Critique |
| V1-05 | LoyaltyProgram | Consultation soldes clients (UI Component + controllers + ACL + menu) | Critique |
| V1-06 | StorePickup | CRUD magasins (UI Component + controllers + ACL + menu) | Critique |
| V1-07 | StoreLocator | Décision d'architecture documentée | Critique |
| V1-08 | StoreLocator | CRUD magasins (UI Components + controllers + ACL + menu) | Critique |
| V1-09 | StoreLocator | Carte frontend (block + layout + template) | Haute |
| V1-10 | Hreflang | Décision d'architecture documentée (configuration-only) | Critique |
| V1-11 | Hreflang | Validation injection balises (déjà fait, vérification) | Haute |
| V1-12 | Training | Clarification rôle documentée | Critique |
| V1-13 | Training | Suppression data patch `CreateStores.php` | Haute |

### 6.4 Périmètre reporté à v1.1

| ID | Module | Fonctionnalité | Justification |
|---|---|---|---|
| V1.1-01 | GDPR | Anonymisation admin | Console commands suffisantes. |
| V1.1-02 | GDPR | Configuration système | Valeurs par défaut codées en dur acceptables. |
| V1.1-03 | EuVat | Configuration avancée | Config de base suffisante. |
| V1.1-04 | LoyaltyProgram | Gestion avancée des transactions | Consultation solde suffisante. |
| V1.1-05 | LoyaltyProgram | Configuration avancée | Valeurs par défaut acceptables. |
| V1.1-06 | StorePickup | Gestion des disponibilités/créneaux | Horaires génériques suffisent. |
| V1.1-07 | StorePickup | Configuration avancée | Config carrier de base suffisante. |
| V1.1-08 | StoreLocator | Recherche par proximité | Raffinement UX. |
| V1.1-09 | StoreLocator | Configuration système | Valeurs par défaut acceptables. |
| V1.1-10 | Hreflang | Mapping manuel langue-store | Mapping automatique suffit. |
| V1.1-11 | Training | Alignement standards complet | Dépend de la décision de rôle. |

### 6.5 Décisions d'architecture majeures

| # | Décision | Options | Recommandation | Impact si non décidé |
|---|---|---|---|---|
| 1 | **StoreLocator** : couplage avec StorePickup ou indépendance ? | A : Couplage (lecture seule sur StorePickup) / B : Indépendance (entité propre) | **Option B** | Si non décidé, le module reste avec un couplage fort non maintenable. |
| 2 | **Hreflang** : configuration-only ou entités métier ? | A : Configuration-only / B : Entités métier (mapping langue-store) | **Option A** | Si non décidé, le scope reste flou et risque de gonfler. |
| 3 | **Training** : module demo ou module fonctionnel ? | A : Démonstration pédagogique / B : Module fonctionnel | **Option A** | Si non décidé, le module reste incohérent avec data patch anormal. |

> **Validation requise** : périmètre v1.0 (V1-01 → V1-13), périmètre v1.1 reporté,
> et les 3 décisions d'architecture ci-dessus doivent être validées par le product owner.

---

*Document conforme à la Charte du projet AlpineCommerce.*
