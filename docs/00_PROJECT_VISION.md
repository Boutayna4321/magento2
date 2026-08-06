# Vision du projet AlpineCommerce

## Mission

AlpineCommerce est un projet e-commerce professionnel basé sur **Adobe Commerce (Magento 2 Open Source)**.

Nous ne construisons pas un nouveau moteur e-commerce.
Nous ne remplaçons pas Magento.
Nous exploitons Magento comme cœur de l'application et y ajoutons des fonctionnalités métier spécifiques via des modules propres.

Le projet est développé dans un but **pédagogique** : apprendre Adobe Commerce comme dans une entreprise réelle, avec des standards de code, une architecture propre et des processus de travail structurés.

---

## Contexte

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

## Objectifs

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

## Philosophie

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
AlpineCommerce_Blog         → Gestion du blog
AlpineCommerce_Faq          → Gestion de la FAQ
AlpineCommerce_Gdpr         → Conformité RGPD
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

---

## Pourquoi AlpineCommerce existe ?

- **Indépendance** : nos modules ne dépendent pas d'un éditeur tiers
- **Propriété intellectuelle** : le code nous appartient
- **Évolutivité** : nous contrôlons la roadmap et les priorités
- **Apprentissage** : construction d'une expertise Adobe Commerce interne
- **Réutilisabilité** : les modules sont conçus pour être déployés sur d'autres projets Magento
