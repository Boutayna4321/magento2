# Documentation API AlpineCommerce

## Table des matières

1. [Vue d'ensemble de l'architecture](#vue-densemble-de-larchitecture)
2. [Comment les APIs ont été construites](#comment-les-apis-ont-été-construites)
3. [Authentification et autorisation](#authentification-et-autorisation)
4. [Modules et endpoints](#modules-et-endpoints)
   - [CustomerCare](#customercare)
   - [ProductReviews](#productreviews)
   - [ProductQuestions](#productquestions)
   - [Blog](#blog)
   - [LegalPages](#legalpages)
   - [Faq](#faq)
   - [Gdpr](#gdpr)
   - [EuVat](#euvat)
   - [LoyaltyProgram](#loyaltyprogram)
   - [StorePickup](#storepickup)
   - [ProductLabels](#productlabels)
5. [Collection Postman](#collection-postman)

---

## Vue d'ensemble de l'architecture

Tous les modules AlpineCommerce suivent l'architecture **Service Contracts** de Magento 2, qui repose sur 3 couches principales :

```
[Contrôleur REST / webapi.xml]
         ↓
[Interface API (Api/*Interface.php)]
         ↓
[Implémentation (Model/*.php)]
         ↓
[Modèle de données (Api/Data/*Interface.php)]
         ↓
[Resource Model (Model/ResourceModel/*.php)]
         ↓
[Base de données]
```

### Modules AlpineCommerce présents dans le projet

| Module | Description | Type d'API |
|--------|-------------|------------|
| `AlpineCommerce_CustomerCare` | Gestion VIP, niveaux de fidélité | REST admin/customer |
| `AlpineCommerce_ProductReviews` | Avis produits avec vote utile | REST anonymous/customer |
| `AlpineCommerce_ProductQuestions` | Q/R produits | REST anonymous/customer |
| `AlpineCommerce_Blog` | Articles et catégories de blog | REST anonymous |
| `AlpineCommerce_LegalPages` | Pages légales (CGU, confidentialité) | REST anonymous |
| `AlpineCommerce_Faq` | FAQ | REST anonymous |
| `AlpineCommerce_Gdpr` | RGPD : consentement, export, suppression | REST anonymous/customer |
| `AlpineCommerce_EuVat` | Validation TVA intracommunautaire | REST anonymous |
| `AlpineCommerce_LoyaltyProgram` | Points de fidélité sur panier | REST customer |
| `AlpineCommerce_StorePickup` | Click & Collect | REST customer |
| `AlpineCommerce_ProductLabels` | Étiquettes produits (admin) | REST admin |

---

## Comment les APIs ont été construites

### 1. Enregistrement du module

Chaque module est enregistré dans `registration.php` :

```php
ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'AlpineCommerce_CustomerCare',
    __DIR__
);
```

### 2. Définition des dépendances (`etc/module.xml`)

Chaque module déclare ses dépendances Magento obligatoires :

```xml
<module name="AlpineCommerce_CustomerCare" setup_version="1.0.0">
    <sequence>
        <module name="Magento_Customer"/>
        <module name="Magento_Sales"/>
        <module name="Magento_Backend"/>
        <module name="AlpineCommerce_CustomerGrid"/>
    </sequence>
</module>
```

### 3. Contrats de service (Interfaces API)

Chaque module expose des **interfaces PHP** dans le dossier `Api/`. Ces interfaces définissent le contrat public et permettent l'injection de dépendances.

**Exemple — CustomerCare :**

```php
// Api/CustomerCareInterface.php
interface CustomerCareInterface
{
    public function getVipStatus(int $customerId): VipStatusInterface;
    public function recalculateVipStatus(int $customerId): VipStatusInterface;
    public function recalculateAll(): int;
    public function resetAll(): void;
}
```

**Exemple — ProductReviews :**

```php
// Api/ReviewRestInterface.php
interface ReviewRestInterface
{
    public function getReviews(int $productId, int $page = 1, int $pageSize = 20): ReviewSearchResultsInterface;
    public function getReview(int $reviewId): ReviewInterface;
    public function addReview(int $productId, string $title, string $detail, int $rating): ReviewInterface;
    public function voteHelpful(int $reviewId, int $helpful): bool;
}
```

### 4. Interfaces de données (`Api/Data/*Interface.php`)

Les objets de retour (DTO) sont définis comme interfaces :

```php
// Api/Data/VipStatusInterface.php
interface VipStatusInterface
{
    public function getCustomerId(): int;
    public function getVipLevel(): string;
    public function getLifetimeSpent(): float;
    public function getBronzeThreshold(): float;
    public function getSilverThreshold(): float;
    public function getGoldThreshold(): float;
}
```

### 5. Implémentations (`di.xml`)

Le mapping interface → implémentation se fait dans `etc/di.xml` :

```xml
<preference for="AlpineCommerce\CustomerCare\Api\CustomerCareInterface"
            type="AlpineCommerce\CustomerCare\Model\CustomerCare"/>
<preference for="AlpineCommerce\CustomerCare\Api\Data\VipStatusInterface"
            type="AlpineCommerce\CustomerCare\Model\VipStatus"/>
```

### 6. Services REST (`Model/Rest/*RestService.php`)

Les endpoints REST sont implémentés dans des classes dédiées `Model/Rest/` :

```php
// Model/Rest/GdprRestService.php
class GdprRestService implements GdprRestInterface
{
    public function __construct(
        private readonly ConsentManagementInterface $consentManagement,
        private readonly GdprExportInterface $gdprExportService,
        private readonly GdprDeleteInterface $gdprDeleteService,
        private readonly UserContextInterface $userContext
    ) {}

    public function logConsent(string $consentType, bool $granted): GdprConsentResultInterface
    {
        $customerId = $this->userContext->getUserId();
        $success = $this->consentManagement->log($customerId ? (int) $customerId : null, $consentType, $granted);
        return new GdprConsentResult([
            'success' => $success,
            'message' => $success ? __('Consent recorded.') : __('Unable to record the consent.'),
        ]);
    }
}
```

### 7. Exposition des routes (`etc/webapi.xml`)

Les routes REST sont déclarées dans `etc/webapi.xml` :

```xml
<route url="/V1/alphacommerce/gdpr/consent" method="POST">
    <service class="AlpineCommerce\Gdpr\Api\GdprRestInterface" method="logConsent"/>
    <resources>
        <resource ref="anonymous"/>
    </resources>
</route>
<route url="/V1/alphacommerce/gdpr/export" method="GET">
    <service class="AlpineCommerce\Gdpr\Api\GdprRestInterface" method="exportData"/>
    <resources>
        <resource ref="self"/>
    </resources>
</route>
```

### 8. Autorisations (`etc/acl.xml`)

Les permissions admin sont déclarées dans `etc/acl.xml` :

```xml
<acl>
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="AlpineCommerce_CustomerCare::main" title="Customer Care" sortOrder="95">
                <resource id="AlpineCommerce_CustomerCare::config" title="Configuration" sortOrder="10"/>
                <resource id="AlpineCommerce_CustomerCare::manage" title="Manage Customer Care (REST)" sortOrder="20"/>
            </resource>
        </resource>
    </resources>
</acl>
```

---

## Authentification et autorisation

### Token admin

```http
POST /rest/V1/integration/admin/token
Content-Type: application/json

{
  "username": "{{admin_user}}",
  "password": "{{admin_pass}}"
}
```

**Réponse :** Token brut (chaîne de caractères)

```http
Authorization: Bearer {{admin_token}}
```

### Token customer

```http
POST /rest/V1/integration/customer/token
Content-Type: application/json

{
  "username": "{{customer_email}}",
  "password": "{{customer_pass}}"
}
```

**Réponse :** Token brut (chaîne de caractères)

```http
Authorization: Bearer {{customer_token}}
```

### Niveaux d'accès dans `webapi.xml`

| Référence | Signification |
|-----------|---------------|
| `anonymous` | Accessible sans authentification |
| `self` | Accessible avec un token customer (profil connecté) |
| `admin` | Accessible avec un token admin |
| `AlpineCommerce_X::resource` | Permission ACL admin spécifique |

---

## Modules et endpoints

### CustomerCare

**Chemin de base :** `/rest/V1/customercare/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/vip-status/{customerId}` | Admin (`AlpineCommerce_CustomerCare::manage`) | Récupérer le statut VIP d'un client |
| GET | `/me/vip-status` | Customer (self) | Récupérer son propre statut VIP |
| POST | `/vip-status/{customerId}` | Admin (`AlpineCommerce_CustomerCare::manage`) | Recalculer le statut VIP d'un client |
| POST | `/recalculate-all` | Admin (`AlpineCommerce_CustomerCare::manage`) | Recalculer le statut VIP de tous les clients |

**Exemple de réponse — GET VIP status :**

```json
{
  "customer_id": 1,
  "vip_level": "gold",
  "lifetime_spent": 1250.00,
  "bronze_threshold": 100.00,
  "silver_threshold": 500.00,
  "gold_threshold": 1000.00
}
```

---

### ProductReviews

**Chemin de base :** `/rest/V1/alphacommerce/product-reviews/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/?productId={id}&page={n}&pageSize={n}` | Anonymous | Lister les avis approuvés d'un produit |
| GET | `/{reviewId}` | Anonymous | Récupérer un avis par ID |
| POST | `/` | Customer (self) | Ajouter un avis |
| POST | `/{reviewId}/vote?helpful={0\|1}` | Customer (self) | Voter pour un avis |

**Exemple de requête — POST add review :**

```json
{
  "productId": 1,
  "title": "Great product",
  "detail": "Really enjoyed using this product.",
  "rating": 5
}
```

---

### ProductQuestions

**Chemin de base :** `/rest/V1/alphacommerce/product-questions/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/?productId={id}&page={n}&pageSize={n}` | Anonymous | Lister les questions d'un produit |
| GET | `/{questionId}` | Anonymous | Récupérer une question par ID |
| POST | `/` | Customer (self) | Poser une question |
| PUT | `/{questionId}` | Customer (self) | Modifier sa question |
| DELETE | `/{questionId}` | Customer (self) | Supprimer sa question |
| POST | `/{questionId}/vote?helpful={0\|1}` | Anonymous | Voter pour une question |
| POST | `/{questionId}/answer` | Customer (self) | Répondre à une question |

**Exemple de requête — POST add question :**

```json
{
  "productId": 1,
  "question": "Does this come in blue?"
}
```

**Exemple de requête — POST answer :**

```json
{
  "answer": "Yes, it is available in blue."
}
```

---

### Blog

**Chemin de base :** `/rest/V1/alphacommerce/blog/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/posts?page={n}&pageSize={n}` | Anonymous | Lister les articles actifs |
| GET | `/posts/{postId}` | Anonymous | Récupérer un article par ID |
| GET | `/categories?page={n}&pageSize={n}` | Anonymous | Lister les catégories actives |
| GET | `/categories/{categoryId}` | Anonymous | Récupérer une catégorie par ID |

---

### LegalPages

**Chemin de base :** `/rest/V1/alphacommerce/legal-pages/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/?page={n}&pageSize={n}` | Anonymous | Lister toutes les pages légales |
| GET | `/{type}` | Anonymous | Récupérer une page par type (ex: `privacy-policy`, `terms-conditions`) |

---

### Faq

**Chemin de base :** `/rest/V1/alphacommerce/faqs/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/` | Anonymous | Lister toutes les FAQs actives |
| GET | `/{faqId}` | Anonymous | Récupérer une FAQ par ID |

---

### Gdpr

**Chemin de base :** `/rest/V1/alphacommerce/gdpr/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| POST | `/consent` | Anonymous | Enregistrer un consentement |
| GET | `/export` | Customer (self) | Exporter les données personnelles (RGPD Art. 15) |
| DELETE | `/delete` | Customer (self) | Anonymiser les données (RGPD Art. 17) |

**Exemple de requête — POST log consent :**

```json
{
  "consentType": "marketing",
  "granted": true
}
```

**Exemple de réponse — GET export :**

```json
{
  "customer_id": 1,
  "data": "{ \"personal_data\": {...} }",
  "exported_at": "2026-08-12 16:00:00"
}
```

---

### EuVat

**Chemin de base :** `/rest/V1/alphacommerce/euvat/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| POST | `/validate` | Anonymous | Valider un numéro de TVA via VIES |
| GET | `/validate/{countryId}/{vatNumber}` | Anonymous | Récupérer une validation TVA enregistrée |

**Exemple de requête — POST validate :**

```json
{
  "countryId": "FR",
  "vatNumber": "40303265045"
}
```

---

### LoyaltyProgram

**Chemin de base :** `/rest/V1/carts/mine/loyalty-points`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| POST | `/` | Customer (self) | Appliquer des points de fidélité sur le panier |

Le `cartId` est injecté automatiquement via le contexte customer.

**Exemple de requête :**

```json
{
  "points": 100
}
```

**Réponse :** `TotalsInterface` (totaux recalculés du panier)

---

### StorePickup

**Chemin de base :** `/rest/V1/carts/mine/store-pickup`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/` | Customer (self) | Lister les magasis disponibles pour le retrait |
| POST | `/` | Customer (self) | Définir le magasin de retrait |

Le `cartId` est injecté automatiquement.

**Exemple de requête — POST set pickup :**

```json
{
  "sourceCode": "default"
}
```

---

### ProductLabels

**Chemin de base :** `/rest/V1/alphacommerce/product-labels/*`

| Méthode | Endpoint | Authentification | Description |
|---------|----------|-----------------|-------------|
| GET | `/?searchCriteria[page_size]=20` | Admin | Lister les étiquettes |
| POST | `/` | Admin | Créer une étiquette |
| GET | `/{entityId}` | Admin | Récupérer une étiquette par ID |
| DELETE | `/{entityId}` | Admin | Supprimer une étiquette |
| GET | `/{labelId}/products` | Admin | Lister les produits d'une étiquette |
| POST | `/{labelId}/products` | Admin | Assigner des produits à une étiquette |
| POST | `/{productId}/apply` | Admin | Assigner des étiquettes à un produit |

**Exemple de requête — POST create label :**

```json
{
  "name": "New Label",
  "color": "#FF0000",
  "is_active": true
}
```

**Exemple de requête — POST assign products to label :**

```json
{
  "productIds": [1, 2, 3]
}
```

**Exemple de requête — POST assign labels to product :**

```json
{
  "labelIds": [1, 2]
}
```

---

## Collection Postman

Une collection Postman unifiée contenant tous les endpoints est disponible :

```
docs/postman/AlpineCommerce-All-Modules.postman_collection.json
```

### Variables de collection

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `base_url` | `http://localhost:8080` | URL de base Magento |
| `admin_user` | `admin` | Identifiant admin |
| `admin_pass` | `Admin123!` | Mot de passe admin |
| `customer_email` | `customer@example.com` | Email client |
| `customer_pass` | `Customer123!` | Mot de passe client |
| `admin_token` | *(vide)* | Token admin (rempli automatiquement) |
| `customer_token` | *(vide)* | Token customer (rempli automatiquement) |
| `customer_id` | `1` | ID client |
| `product_id` | `1` | ID produit |
| `review_id` | `1` | ID avis |
| `question_id` | `1` | ID question |
| `post_id` | `1` | ID article blog |
| `category_id` | `1` | ID catégorie blog |
| `label_id` | `1` | ID étiquette |
| `cart_id` | `1` | ID panier |
| `store_code` | `default` | Code magasin pour StorePickup |

### Utilisation

1. Ouvrir Postman
2. **Import** → sélectionner le fichier JSON
3. Configurer les variables si nécessaire
4. Exécuter la requête **"Get Admin Token"** pour remplir `admin_token`
5. Exécuter la requête **"Get Customer Token"** pour remplir `customer_token`
6. Tester les endpoints des différents modules

Les scripts de test Postman sauvegardent automatiquement les tokens dans les variables de collection.

---

## Conventions de code

- **PHP 8.2+** avec `declare(strict_types=1)`
- **Constructors promotion** pour l'injection de dépendances
- **Interfaces de service** pour tous les contrats publics
- **DTOs** via `Api/Data/*Interface.php` pour les objets de réponse
- **Exceptions Magento** (`NoSuchEntityException`, `StateException`, etc.)
- **Resource Models** pour l'accès aux données
- **SearchCriteriaBuilder** pour la pagination et les filtres
- **UserContextInterface** pour récupérer l'ID du customer connecté
