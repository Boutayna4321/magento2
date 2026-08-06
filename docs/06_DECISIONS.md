# Decisions d'architecture (ADR)

## Format d'une décision

Chaque décision d'architecture est documentée selon le format ADR (Architecture Decision Record).

```
ADR-XXX
Titre de la décision

Statut: Accepté | Rejeté | Déprécié | Remplacé par ADR-YYY
Date: YYYY-MM-DD
Décideurs: [liste]

Contexte:
Description du contexte et du problème.

Décision:
Description de la décision prise.

Justification:
Pourquoi cette décision a été prise.

Impact:
Conséquences de cette décision.
```

---

## ADR-001 : Magento reste le cœur de l'application

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

Le projet AlpineCommerce doit être une plateforme e-commerce professionnelle. Plusieurs approches étaient possibles : créer un nouveau framework, réécrire Magento, ou utiliser Magento comme base.

### Décision

Magento 2 Open Source reste le cœur de l'application. Toutes les fonctionnalités natives de Magento sont utilisées telles quelles.

### Justification

- Magento est mature, sécurisé et éprouvé
- La communauté Magento est active
- Les fonctionnalités natives (catalogue, checkout, paiement) sont complexes à réécrire
- L'équipe maîtrise Magento

### Impact

- Aucune réécriture de fonctionnalités Magento
- Les modules AlpineCommerce viennent compléter Magento
- Les mises à jour de Magento sont possibles

---

## ADR-002 : AlpineCommerce développe uniquement des fonctionnalités métier

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

AlpineCommerce est un projet de développement de modules Magento. Il faut définir la frontière entre ce que Magento fait et ce que AlpineCommerce fait.

### Décision

Les modules AlpineCommerce ne font que des fonctionnalités métier que Magento ne propose pas nativement.

### Justification

- Éviter la duplication de fonctionnalités Magento
- Maintenir une architecture propre
- Faciliter les mises à jour de Magento

### Impact

- Pas de module `AlpineCommerce_Catalog`, `AlpineCommerce_Customer`, etc.
- Les modules AlpineCommerce sont des extensions métier pures

---

## ADR-003 : Étendre Magento plutôt que le remplacer

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

Pour ajouter une fonctionnalité, deux approches sont possibles : créer un nouveau module ou étendre un module Magento existant.

### Décision

Étendre Magento via Plugins, Observers, Layout XML, ViewModels avant de créer un nouveau module.

### Justification

- Moins de code à maintenir
- Meilleure compatibilité avec les mises à jour Magento
- Respect des conventions Magento

### Impact

- Utilisation systématique des Plugins et Observers
- Pas de duplication de code Magento

---

## ADR-004 : Chaque module possède une seule responsabilité

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

La structure des modules AlpineCommerce doit être définie pour garantir la maintenabilité.

### Décision

Chaque module AlpineCommerce a une seule responsabilité métier et ne dépend pas des autres modules AlpineCommerce.

### Justification

- Modules indépendants et réutilisables
- Maintenance simplifiée
- Déploiement granulaire

### Impact

- Pas de dépendances entre modules AlpineCommerce
- Chaque module peut être activé/désactivé indépendamment

---

## ADR-005 : Toutes les APIs utilisent les Service Contracts

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

Les APIs REST doivent être stables et évolutives.

### Décision

Toutes les routes REST API exposent des Service Contracts (interfaces dans `Api/`).

### Justification

- Abstraction de l'implémentation
- Facilité de test
- Compatibilité avec les outils Magento

### Impact

- Tous les modules avec API REST ont un `Api/` directory
- Les Controllers utilisent les interfaces, pas les implémentations

---

## ADR-006 : Le projet utilise uniquement REST API

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

Magento propose deux APIs : REST et GraphQL. Il faut choisir laquelle utiliser.

### Décision

Le projet utilise uniquement REST API pour l'instant. GraphQL n'est pas exclu pour le futur.

### Justification

- REST est plus simple à mettre en place
- L'équipe maîtrise REST
- Les besoins actuels sont couverts par REST

### Impact

- Toutes les routes sont définies dans `webapi.xml`
- Pas de schema GraphQL pour l'instant

---

## ADR-007 : Chaque Sprint se termine par un audit complet

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

La qualité du code est critique pour un projet e-commerce professionnel.

### Décision

Chaque sprint se termine par un audit technique complet avant de passer au suivant.

### Justification

- Garantir la qualité du code
- Détecter les problèmes tôt
- Documenter l'état du projet

### Impact

- Temps d'audit inclus dans chaque sprint
- Aucun code non audité n'est considéré comme terminé

---

## ADR-008 : Toute nouvelle décision devra être ajoutée dans ce document

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

Les décisions d'architecture doivent être traçables et consultables.

### Décision

Toute nouvelle décision d'architecture sera ajoutée dans ce document avec le format ADR.

### Justification

- Traçabilité des décisions
- Documentation vivante
- Référence pour toute l'équipe

### Impact

- Ce document est mis à jour régulièrement
- Toute décision est datée et justifiée

---

## ADR-009 : Migration depuis Cartware vers AlpineCommerce

- **Statut** : Accepté
- **Date** : 2024-01-01
- **Décideurs** : Équipe AlpineCommerce

### Contexte

Le projet AlpineCommerce a été initialement développé sous le vendor Cartware. Une migration vers le vendor AlpineCommerce est nécessaire.

### Décision

Tous les modules Cartware sont migrés vers AlpineCommerce avec :
- Changement du namespace PHP
- Changement du nom de module
- Changement des noms de tables DB
- Changement des référenceIds dans db_schema.xml
- Conservation des fonctionnalités

### Justification

- Indépendance vis-à-vis de Cartware
- Vendor propre à AlpineCommerce
- Cohérence de nommage

### Impact

- 10 modules migrés
- Migration progressive module par module
- Les modules Cartware restent actifs jusqu'à validation complète

---

## Décisions futures

| ADR | Titre | Statut |
|---|---|---|
| ADR-010 | Frontend React vs PWA Studio | À décider |
| ADR-011 | GraphQL pour les APIs publiques | À décider |
| ADR-012 | Tests automatisés en CI/CD | À décider |
| ADR-013 | Stratégie de déploiement | À décider |
| ADR-014 | Architecture des modules ProductReviews et ProductQuestions | Accepté |

---

## ADR-014 : Architecture des modules ProductReviews et ProductQuestions

- **Statut** : Accepté
- **Date** : 2026-08-04
- **Décideurs** : Équipe AlpineCommerce

### Contexte

Les modules `ProductReviews` et `ProductQuestions` introduisent des fonctionnalités de engagement client sur les fiches produit. Ils doivent coexister sans conflits avec les modules Magento natifs (`Magento_Review`, `Magento_ReviewGraphQl`).

### Décision

1. **Routes isolées** : Utiliser `productreviews` et `productquestions` comme frontName pour éviter tout conflit avec les routes natives Magento (`review`).
2. **Injection produit** : Utiliser `catalog_product_view.xml` pour injecter les blocs frontend sur la fiche produit, sans modifier le Core.
3. **3 tables séparées** : Chaque entité (review, image, vote / question, answer, vote) a sa propre table avec clés étrangères et index.
4. **Vote utile désynchronisé** : Le compteur `helpful_count` est incrémenté à la volée pour éviter les jointures coûteuses en lecture.
5. **Modération par statut** : Les questions/reviews passent par un workflow (pending → approved/rejected).
6. **Réponses officielles** : Un champ `is_official` sur les réponses permet de distinguer les réponses admin des réponses clients.

### Impact

- Pas de conflit avec les modules Magento natifs
- Performance optimisée pour la lecture des questions/avis produits
- Modération complète côté admin
