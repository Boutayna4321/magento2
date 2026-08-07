# Changelog officiel du projet AlpineCommerce

Toutes les modifications notables du projet sont documentées dans ce fichier.

Format basé sur [Keep a Changelog](https://keepachangelog.com/fr-FR/).

> Ce document regroupe l'ancien `08_CHANGELOG.md` ainsi que les rapports racine
> `SPRINT_VALIDATION_REPORT.md` (Sprint 5 — validation fonctionnelle) et
> `SPRINT_INTEGRATION_REPORT.md` (Sprint 6 — intégration fonctionnelle).
>
> ⚠️ **Réconciliation des sprints** : les sprints de finalisation des modules
> (Sprint 1 = Gdpr, 2 = StorePickup, 3 = StoreLocator) et les sprints globaux
> (Sprint 4 = correctifs code, Sprint 5 = validation, Sprint 6 = intégration) sont
> regroupés ici chronologiquement. Les rapports détaillés de chaque sprint sont
> archivés dans `archive/sprints/`.

---

## [1.5.2] - 2026-08-06

### Corrigé (bug report admin — Sprint 6, addendum)

- `AlpineCommerce_Blog` — `view/adminhtml/ui_component/blog_category_form.xml` :
  exception `InvalidArgumentException: Node "argument" with name "class" is required for this type`
  sur la page admin `admin/blog/category/edit`. Cause : le `<dataSource>` n'avait pas
  d'enfant `<dataProvider class="...">` — `definition.map.xml` (module-ui) construit
  l'argument `dataProvider` en `configurableObject` dont la classe provient de
  `dataProvider/@class` (XPath) ; sans ce nœud, l'évaluateur
  `Magento\Framework\View\Element\UiComponent\Argument\Interpreter\ConfigurableObject`
  levait l'exception au rendu du layout.
  Fix : ajout de `<dataProvider class="AlpineCommerce\Blog\Ui\DataProvider\CategoryFormDataProvider"
  name="blog_category_form_data_source">` (`requestFieldName`/`primaryFieldName` = `category_id`),
  suppression de l'item `config/dataProvider` redondant, `js_config` aligné sur le
  formulaire Post qui fonctionne.
- **Même cause corrigée sur 4 autres formulaires admin** (même exception « class required ») :

  | uiComponent | dataProvider | ID |
  |---|---|---|
  | `faq_faq_form` | `AlpineCommerce\Faq\Ui\DataProvider\FaqFormDataProvider` | `faq_id` |
  | `legal_page_form` | `AlpineCommerce\LegalPages\Ui\DataProvider\FormDataProvider` | `page_id` |
  | `question_question_form` | `AlpineCommerce\ProductQuestions\Ui\DataProvider\QuestionFormDataProvider` | `question_id` |
  | `review_review_form` | `AlpineCommerce\ProductReviews\Ui\DataProvider\ReviewFormDataProvider` | `review_id` |

### Corrigé (formulaire admin vide côté navigateur — cause racine `button-set`)

- La page `admin/faq/faq/new|edit` renvoyait un HTML valide mais le formulaire restait
  vide dans le navigateur. Cause racine : `<container name="button_set"
  component="Magento_Ui/js/form/components/button-set">` — ce composant JS **n'existe
  pas en Magento 2.4.8** (seuls `button.js`, `button-adapter.js` et
  `form/adapter/buttons.js` existent). Au chargement, `Magento_Ui/js/core/app` échoue
  sur la référence manquante → la structure du formulaire n'est jamais initialisée.
- Fix — remplacement du container par `<settings><buttons>` avec des classes
  `ButtonProviderInterface` (pattern `Blog/Block/Adminhtml/Post/Edit/*`), pour les 5
  formulaires concernés (`faq_faq_form`, `blog_category_form`, `legal_page_form`,
  `question_question_form`, `review_review_form`) + 15 classes créées
  (`{GenericButton,SaveButton,BackButton}.php` par module).
  `GenericButton::get{...}Id()` via `getRequest()->getParam('<id>')` ; `SaveButton` :
  `actionName=save`, `params=[false]`, `sort_order=90` ; `BackButton` : url `*/*/`,
  `sort_order=10`.

### Technique (Sprint 6 — intégration)

- `ProductLabels/view/frontend/layout/catalog_product_view.xml` : `referenceContainer`
  → `referenceBlock` pour `product.info.media` + `product.info.details` (ce sont des
  `block`, pas des `container` — les labels n'étaient jamais rendus).
- `ProductQuestions/Block/Frontend/QuestionList.php` : `use Magento\Framework\Api\SortOrder;`
  ajouté (fatal `Class SortOrder not found`).
- `ProductQuestions/Model/Status.php` + `ProductReviews/Model/Status.php` : cast
  `(string)` sur les branches `match` (`getLabel()` retournait une `Phrase`, pas un `string`
  → `TypeError` sous PHP 8.2 + `strict_types=1`).
- `ProductQuestions/etc/di.xml` : preference `AnswerSearchResultsInterface` →
  `AnswerSearchResults` ajoutée (fatal `Cannot instantiate interface`).
- **Intégration validée** pour les 13 modules : frontend (page produit : labels,
  reviews, questions ; routes `/blog`, `/faq`, `/legal`, `/store-locator` ; checkout :
  loyalty + store pickup), admin (CRUD), REST API (GET/POST) et CLI.

### Reste à traiter (Phase 2 — voir `BACKLOG.md` B-06)

- 6 listings admin XSD-invalides (bien-formés) : `<massAction>` (mauvaise casse),
  `<deps>` texte, `<primaryDataSource>`, `<param>` dans massaction, `<options>` inline
- `AlpineCommerce_StorePickup/etc/adminhtml/routes.xml` absent (URLs admin
  `alphacommerce_pickup/*` non résolues)
- `AlpineCommerce_StorePickup/etc/adminhtml/menu.xml` : item de menu sans attribut `action`

---

## [1.5.1] - 2026-08-06

### Corrigé (Phase 1 — 14 bugs critiques, Sprint 5 validation)

Revue ciblée des 13 modules : 12 bugs critiques contre-vérifiés puis 2 fatals PHP
supplémentaires découverts par le compilateur. La compilation `setup:di:compile` était
**bloquée** (fatal PHP masqué par la barre de progression). **14 bugs corrigés.**

| # | Module | Fichier(s) | Cause racine | Fix |
|---|--------|-----------|--------------|-----|
| C1 | ProductReviews | `Helper/Image.php` | ctor sans `Context` + `parent::__construct` | `Context` injecté, `parent::__construct($context)` |
| C2 | ProductReviews | `Block/Frontend/ReviewList.php` | `use Magento\Framework\Api\SortOrder;` manquant (fatal) | import ajouté |
| C3 | ProductReviews | `Ui/Source/Status.php` | classe dans le mauvais namespace (fatal compile) | réécrit en `Status implements OptionSourceInterface` |
| C4 | ProductQuestions | `Ui/Source/Status.php` | idem C3 | réécrit en `Status implements OptionSourceInterface` |
| C5 | ProductQuestions | `question_question_form.xml` | `</item>` jamais fermé (XML malformé) | fermeture corrigée |
| C6 | ProductQuestions | `etc/frontend/routes.xml` | route frontend absente (404) | fichier créé |
| C7 | ProductLabels | `Block/Adminhtml/Label/Grid.php` | `use Magento\Backend\Block\Widget\Grid` (collision fatale), ctor invalide, renderer + constante inexistants | ctor corrigé, renderer retiré, massaction natif |
| C8 | StoreLocator | `alphacommerce_store_locator_store_form.xml` | XML malformé (`optionsclass`, `<formElements>` orphelins, `</label>`, `country_id` absent) | réécrit, XSD-validé |
| C9 | StorePickup | `alphacommerce_pickup_store_info_form.xml` | idem C8 | réécrit, XSD-validé |
| C10 | StoreLocator | `store-locator.phtml:7` | `getSize()` sur un retour `array` (fatal) | `count($stores)` |
| C11 | StoreLocator + StorePickup | `Controller/Adminhtml/Store/{Save,Delete}.php` (4) | `parent::__construct($context)` alors que `AbstractAction` exige `PageFactory` (fatal) | `PageFactory` injecté + `parent::__construct($context, $pageFactory)` |
| C12 | Blog | `blog_post_form.xml` | classes de boutons `Block\Adminhtml\Post\Edit\*` inexistantes (fatal) | `GenericButton`, `SaveButton`, `BackButton` créés |
| D1 | Gdpr | `Controller/Adminhtml/ConsentLog/Export.php` | PHP 8.2 fatal « Cannot redeclare non-readonly property ... as readonly » (découvert par di:compile) | propriété promue `ResultFactory` retirée |
| D2 | StoreLocator | `Model/StoreRepository.php` | `StoreInterfaceFactory` sans `use` → `Model\StoreInterfaceFactory` inexistant (découvert par di:compile) | `use Api\Data\StoreInterfaceFactory` ajouté |

### Technique (Sprint 5 — validation)

- `setup:di:compile` : compilation complète validée (**EXIT 0**, 4582 classes générées)
  après correction des fatals PHP (le blocage historique « Repositories code generation »
  était le fatal Gdpr silencieux). Permissions `var/generated` rétablies pour le runtime php-fpm.
- Lint PHP 100 % propre (488+ fichiers), XML 100 % bien-formé, 12/18 `ui_component` XSD-valides.
- **13 modules validés fonctionnellement** : installation, schéma DB (16 tables), admin,
  frontend (HTTP 200), REST API, CLI — tous `PASS`.
- **18 bugs corrigés au total** sur les sprints 4-5, dont 4 API-critiques en Sprint 5 :
  1. `getCurrentCustomer()` inexistant en 2.4.8 (`QuestionRestService`, `ReviewRestService`)
     → remplacé par `UserContextInterface::getUserId()` + `getById()`.
  2. Doc blocks `@return`/`@param` manquants sur 6 interfaces Data (`DataObjectProcessor`
     exige les doc blocks pour la sérialisation JSON) → ajoutés (10 fichiers avec SearchResults).
  3. `Status` non importé dans le namespace `Model\Rest` → `use` ajouté.
  4. Mismatch de type ID client (`string` vs `?int`) dans les setters → cast `(int)` + `?->`.

### Issues résiduelles (non bloquantes — voir `BACKLOG.md`)

- Page produit 500 : bug core Elasticsearch 8.x `_id` fielddata (pas un bug AlpineCommerce).
- APIs `self` 401 : plugin `recaptcha-webapi-rest` bloque les routes customer-self y compris
  les endpoints natifs Magento (issue d'environnement).
- GDPR `delete` n'anonymise pas les données client (Art. 17 incomplet) — B-06 P4.
- ProductLabels : observer N+1 — B-06 P5.

---

## [1.5.0] - 2026-08-06

### Ajouté

- `AlpineCommerce_ProductLabels` : étiquettes produits administrables
  - Tables : `alphacommerce_product_label`, `alphacommerce_product_label_product`
  - Grille admin (listing, massactions Delete / Change status, bouton Add New Label)
  - Formulaire d'édition : nom, code, couleurs, priorité, position, dates de validité, statut, sélection produits
  - REST API : `/V1/alphacommerce/product-labels` (GET/POST), `/:entityId` (GET/DELETE),
    `/:labelId/products` (GET/POST), `/:productId/apply` (POST)
  - Frontend : rendu des étiquettes sur la page produit et dans les listings catégorie (plugin `CatalogBlock`)
  - i18n français

### Corrigé

- Grille admin réécrite au format Magento 2.4.8 (retrait `primaryDataSource`, bloc
  `<templates><filters><select>` obsolète ; ajout du `<dataProvider>` enfant obligatoire)
- VirtualType de data source retiré de `di.xml` (le `<dataProvider class="...">` XML suffit)
- Template des blocs admin corrigé avec extension `.phtml` (`::label/edit.phtml`)
- Formulaire d'édition : `use_container => true` + URL d'action via `getUrl()`
- Contrôleur `Edit` : injection explicite de `Magento\Framework\Registry`
- Routes REST réécrites en syntaxe `:param` ; docblocks PHPDoc ajoutés
- `ProductLabelSearchResultsInterface::getItems()` sans `: array` (compatibilité PHP)
- Suppression du code de debug (`var_dump`)

---

## [1.4.0] - 2024-01-15

### Ajouté

- `AlpineCommerce_LoyaltyProgram` : programme de fidélité avec gain et dépense de points
  - Tables : `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points`
  - REST API : `/V1/carts/mine/loyalty-points`
  - Observers : attribution de points sur facture, déduction sur commande
  - Total collector : réduction de panier
  - Plugin minicart : message incitatif

### Corrigé

- Correction des `referenceId` dans `db_schema.xml` (prefix `ALPINECOMMERCE_*`)
- Suppression des fichiers legacy `Setup/InstallSchema.php` et `Setup/InstallData.php`
- Suppression du repository en mémoire `InMemory/LoyaltyBalanceRepository.php`

---

## [1.3.0] - 2024-01-10

### Ajouté

- `AlpineCommerce_Training` : module de formation et démonstration
  - Data Patch pour création de store views (⚠️ à supprimer — voir `BACKLOG.md` B-08)
  - Observers sur produit, commande, checkout, connexion client
  - Configuration multi-stores
- `AlpineCommerce_StoreLocator` : localisateur de magasins physiques
  - Interface admin pour gérer les magasins
  - Frontend avec carte et coordonnées
  - CSS admin et frontend
- `AlpineCommerce_StorePickup` : option de retrait en magasin
  - Carrier Magento personnalisé
  - Sélection de magasin dans le checkout
  - Configuration admin
  - i18n français

### Corrigé

- Migration des chemins de configuration (`cartware_*` → `alphacommerce_*`)

---

## [1.2.0] - 2024-01-05

### Ajouté

- `AlpineCommerce_LegalPages` : pages légales dynamiques
  - Types de pages : CGV, CGU, confidentialité, mentions légales
  - Interface admin CRUD
  - REST API publique
  - Frontend avec listing et vue détaillée
- `AlpineCommerce_Gdpr` : conformité RGPD
  - Logging des consentements
  - Export des données personnelles (Art. 15)
  - Anonymisation des données (Art. 17)
  - Commandes CLI
  - REST API
- `AlpineCommerce_Faq` : FAQ
  - Interface admin CRUD
  - REST API publique
  - Frontend avec listing et vue détaillée

---

## [1.1.0] - 2024-01-01

### Ajouté

- `AlpineCommerce_Blog` : blog multi-boutiques
  - Catégories et articles
  - Interface admin CRUD
  - REST API publique
  - Frontend avec listing et vue détaillée

### Corrigé

- Standardisation des noms de tables et colonnes
- Correction des chemins de configuration

---

## [1.0.0] - 2023-12-20

### Ajouté

- `AlpineCommerce_EuVat` : validation TVA européenne
  - Intégration service VIES via SOAP
  - Commande CLI `alphacommerce:euvat:validate`
  - REST API
  - Configuration admin
  - i18n français
- `AlpineCommerce_Hreflang` : balises hreflang SEO
  - Génération automatique des balises hreflang
  - Support multi-boutiques
  - Configuration admin
  - i18n français

---

## [0.1.0] - 2023-12-01

### Ajouté

- Structure initiale du projet
- Documentation officielle (`docs/`)
- Workflow de sprint
- Guidelines de développement
- Décisions d'architecture (ADR)

---

## Légende

- **Ajouté** : Nouvelles fonctionnalités
- **Corrigé** : Corrections de bugs
- **Modifié** : Changements dans des fonctionnalités existantes
- **Supprimé** : Fonctionnalités supprimées
- **Sécurité** : Corrections de vulnérabilités

---

## Prochaines versions

### v1.1 (prévu)

- Compléter la finalisation des 7 modules en cours (interface admin pour
  LoyaltyProgram, EuVat, Hreflang, Training — voir `ROADMAP.md`)
- Anonymisation admin GDPR (Art. 17), configuration système GDPR
- Gestion des transactions LoyaltyProgram, disponibilités StorePickup,
  recherche par proximité StoreLocator
- Tests automatisés (voir `BACKLOG.md` B-07)

### v2.0 (prévu)

- Introduction de `AlpineCommerce_Contact`
- Passage au frontend React + Vite + Tailwind CSS

---

*Dernière mise à jour : 2026-08-06.*
