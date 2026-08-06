# Glossaire AlpineCommerce

## A

### ACL (Access Control List)
Système de permissions de Magento qui définit qui peut accéder à quelles ressources dans l'admin.

### Adobe Commerce
Nom officiel de Magento 2 (Enterprise Edition). Dans ce projet, nous utilisons Magento 2 Open Source.

### API REST
Interface de programmation qui permet d'interagir avec Magento via des requêtes HTTP. Définie dans `etc/webapi.xml`.

### Area
Concept Magento qui délimite le contexte d'exécution : `frontend`, `adminhtml`, `crontab`, `webapi_rest`, `graphql`.

### Attribute
Propriété d'un produit, client ou catégorie dans Magento. Peut être de type EAV (texte, date, décimal) ou Flat (varchar, int, text, decimal, datetime).

---

## B

### Block
Classe PHP qui fournit des données à un template PHTML. Hérite de `\Magento\Framework\View\Element\Template`.

### Bundle Product
Type de produit Magento composé d'options multiples, chaque option liée à un produit simple.

---

## C

### Cache
Mécanisme de Magento pour stocker des données fréquemment utilisées et améliorer les performances.

Types de cache :
- `config` : Configuration
- `layout` : Layout XML compilé
- `block_html` : HTML des blocks
- `collections` : Collections EAV
- `reflection` : Métadonnées des classes
- `db_ddl` : Schéma de base de données
- `full_page` : Cache de page complète (Varnish)
- `translate` : Traductions
- `config_integration` : Configuration intégrée
- `config_integration_api` : Configuration intégrée API

### Collection
Classe qui représente une liste d'entités (produits, commandes, etc.) avec filtres, tris et pagination.

### Composer
Gestionnaire de dépendances PHP utilisé par Magento.

### ComponentRegistrar
Classe Magento qui enregistre les modules, thèmes et packages de langue.

### Controller
Classe qui gère les requêtes HTTP et retourne une réponse. Dans Magento, les controllers étendent `\Magento\Framework\App\Action\Action`.

### Cron
Tâches planifiées dans Magento. Configurées dans `etc/crontab.xml`.

### Customer
Entité représentant un client dans Magento.

---

## D

### Data Patch
Script PHP qui modifie la structure ou les données de la base de données. Utilisé pour les modifications post-installation.

### db_schema.xml
Fichier XML déclaratif qui définit les tables, colonnes, index et contraintes de la base de données.

### Dependency Injection (DI)
Pattern qui permet d'injecter les dépendances d'une classe via son constructeur plutôt que de les créer directement.

### di.xml
Fichier de configuration du Dependency Injection Container de Magento.

### Directory
Répertoire virtuel de Magento (ex: `app/code`, `app/design`, `vendor`).

---

## E

### EAV (Entity-Attribute-Value)
Modèle de données de Magento pour les entités comme les produits et les clients. Permet d'ajouter des attributs dynamiquement.

### Event
Mécanisme de Magento qui permet de réagir à des actions spécifiques (ex: `sales_order_save_after`).

### events.xml
Fichier qui déclare les observers pour des événements Magento.

### Extension Attribute
Mécanisme qui permet d'ajouter des attributs à une interface sans la modifier. Utilisé pour étendre les Service Contracts.

---

## F

### Factory
Classe générée automatiquement par Magento pour créer des instances d'objets. Utilise le pattern Factory.

### Frontend
Zone de l'application visible par les clients. Différent de `adminhtml`.

### FrontName
Identifiant d'une route dans l'URL (ex: `loyalty` dans `/loyalty/customer/balance`).

---

## G

### GraphQL
API query language pour Magento (non utilisée dans ce projet pour l'instant).

### Group
Niveau de configuration dans Magento : `default` (global), `websites` (par site), `stores` (par boutique).

---

## H

### Helper
Classe utilitaire qui fournit des méthodes réutilisables. Dans Magento, les Helpers étendent `\Magento\Framework\App\Helper\AbstractHelper`.

---

## I

### Indexer
Processus Magento qui maintient les données à jour pour améliorer les performances des recherches et filtres.

### Interface
Contrat qui définit les méthodes qu'une classe doit implémenter. Dans Magento, les interfaces sont dans le dossier `Api/`.

### Interceptor
Classe générée par `setup:di:compile` qui implémente la logique des Plugins.

---

## J

### JavaScript
Langage de script utilisé dans le frontend. Dans ce projet, React + Vite + Tailwind CSS.

### JSON
Format de données utilisé pour les APIs REST.

---

## K

### Knockout.js
Framework JavaScript utilisé par Magento pour les composants UI (checkout, mini-cart). Dans ce projet, React remplace Knockout pour le frontend personnalisé.

---

## L

### Layout XML
Fichier XML qui définit la structure d'une page Magento (blocks, containers, templates).

### Logger
Classe qui écrit des messages dans les fichiers de log. Utilise PSR-3.

---

## M

### Magento 2
Plateforme e-commerce open source sur laquelle repose AlpineCommerce.

### Menu
Élément du menu admin défini dans `etc/adminhtml/menu.xml`.

### Module
Unité fonctionnelle de Magento. Dans AlpineCommerce, chaque module est une fonctionnalité métier.

### module.xml
Fichier qui déclare un module Magento avec son nom, sa version et ses dépendances.

### MSI (Multi Source Inventory)
Système d'inventaire multi-sources de Magento qui permet de gérer des stocks dans plusieurs entrepôts.

### Multi Store
Fonctionnalité de Magento qui permet de gérer plusieurs boutiques avec des configurations différentes.

---

## O

### Observer
Classe qui réagit à un événement Magento. Déclarée dans `etc/events.xml`.

### OOP (Object-Oriented Programming)
Paradigme de programmation utilisé par Magento : classes, interfaces, héritage, polymorphisme.

---

## P

### Patch
Script qui modifie la base de données. Peut être de type `Data` (données) ou `Schema` (structure).

### Payment
Module Magento qui gère les méthodes de paiement.

### Permission
Droit d'accès à une ressource Magento, défini dans `etc/acl.xml`.

### Plugin (Interceptor)
Pattern Magento qui permet de modifier le comportement d'une méthode sans la toucher. Défini dans `etc/di.xml`.

### Preference
Liaison dans `di.xml` qui associe une interface à une implémentation concrète.

### Product
Entité représentant un produit dans Magento.

### Proxy
Classe générée par Magento pour le chargement paresseux (lazy loading) des dépendances.

### PSR-12
Norme de codage PHP que respecte le projet.

### PHTML
Extension des fichiers de template Magento (PHP HTML).

---

## Q

### Quote
Entité représentant le panier d'un client avant la commande.

---

## R

### React
Bibliothèque JavaScript utilisée pour le frontend personnalisé d'AlpineCommerce.

### Registration
Fichier `registration.php` qui enregistre un module, un thème ou un package de langue auprès de Magento.

### Repository
Classe qui fournit un accès aux données via des méthodes métier (getById, getList, save, delete). Implémente un Service Contract.

### Resource Model
Classe qui effectue les opérations CRUD sur les tables de base de données.

### REST API
Interface de programmation basée sur HTTP pour interagir avec Magento.

### routes.xml
Fichier qui déclare les routes frontend ou admin d'un module.

---

## S

### Sales
Module Magento qui gère les commandes, factures, avoirs et expéditions.

### Schema
Structure de la base de données. Dans Magento, défini dans `etc/db_schema.xml`.

### Scope
Portée de configuration dans Magento : `default` (global), `website` (site web), `store` (boutique).

### Search Criteria
Objet Magento qui représente les critères de recherche (filtres, tris, pagination).

### Service Contract
Interface qui définit les méthodes d'un service métier. Stockée dans `Api/`.

### Setup
Répertoire qui contient les scripts d'installation et de mise à jour de la base de données.

### Shipping
Module Magento qui gère les méthodes de livraison.

### Store
Entité représentant une boutique dans Magento.

### Store View
Niveau le plus bas de la hiérarchie Magento : Global > Site Web > Groupe de boutiques > Boutique > Vue de boutique.

---

## T

### Tailwind CSS
Framework CSS utility-first utilisé pour le frontend personnalisé.

### Template
Fichier PHTML qui contient le HTML d'une page ou d'un block.

### Total Collector
Classe Magento qui calcule les totaux du panier (sous-total, taxes, frais de livraison, remises).

---

## U

### UI Component
Composant d'interface utilisateur Magento pour les grilles et formulaires admin. Défini dans `view/adminhtml/ui_component/`.

### URL Rewrite
Mécanisme de Magento qui permet de personnaliser les URLs pour le SEO.

---

## V

### Varnish
Reverse proxy cache utilisé en production pour accélérer le chargement des pages.

### ViewModel
Classe qui fournit des données et de la logique à un template. Alternative moderne aux Blocks.

### VirtualType
Type virtuel dans `di.xml` qui permet de configurer une classe sans la déclarer explicitement.

---

## W

### webapi.xml
Fichier qui déclare les routes REST API d'un module.

### Website
Entité représentant un site web dans la hiérarchie Magento.

---

## X

### XML
Langage de balisage utilisé pour les configurations Magento (layouts, di, webapi, etc.).

---

## Y

### YAML
Format de fichier utilisé par Docker et certaines configurations Magento.

---

## Z

### Zone
Concept Magento qui délimite le contexte d'exécution (frontend, adminhtml, crontab, etc.).
