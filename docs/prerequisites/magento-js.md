# Magento 2 — JavaScript pour Débutants

> **Target audience**: développeurs qui savent faire du JS classique mais
> découvrent le JS dans Magento 2. Ce guide explique **Pourquoi** Magento
> utilise des outils spécifiques (RequireJS, KnockoutJS) et **Comment**
> écrire du JS compatible avec le projet AlpineCommerce.

---

## 1. Le problème que Magento résout

### 1.1 Sans système de modules

```html
<script src="jquery.js"></script>
<script src="bootstrap.js"></script>
<script src="mon-script.js"></script>
<script src="autre-script.js"></script>
```

Problèmes :
- **Conflits de noms** : deux scripts déclarent `var $`
- **Ordre de chargement** : si `mon-script.js` charge avant `jquery.js`, ça casse
- **Fichiers inutiles chargés** : la page produit charge 500 Ko de JS alors qu'elle n'a besoin que de 50 Ko

### 1.2 La solution Magento : RequireJS

Magento utilise **RequireJS** (bibliothèque AMD) pour charger les scripts
**à la demande**, avec des **dépendances explicites**.

```js
// Au lieu de charger 10 fichiers dans le HTML, on déclare :
define(['jquery', 'ko', 'mage/storage'], function ($, ko, storage) {
    // Magento charge automatiquement jquery, puis ko, puis mage/storage
    // puis exécute ce code
});
```

---

## 2. RequireJS — Le système de modules de Magento

### 2.1 Qu'est-ce que AMD ?

**AMD** = Asynchronous Module Definition. C'est un standard pour définir
des modules JavaScript qui :
- Déclarent leurs dépendances
- Sont chargés asynchronement (en parallèle)
- Évitent les conflits de noms globaux

### 2.2 La fonction `define()`

Tout fichier JS Magento commence par `define()` :

```js
define([
    'jquery',           // Dépendance 1
    'ko',               // Dépendance 2
    'mage/storage',     // Dépendance 3
    'mage/translate'    // Dépendance 4
], function ($, ko, storage, $t) {
    // Le corps du module
    // Les paramètres correspondent aux dépendances dans l'ordre
    
    'use strict';
    
    // Ton code ici
    return {
        init: function () {
            // ...
        }
    };
});
```

**Règles d'or** :
- Le nom des paramètres (`$`, `ko`, `storage`) **doit correspondre** à l'ordre des dépendances
- `'use strict';` est **obligatoire** dans Magento
- Le `return` expose ce que le module fournit aux autres

### 2.3 La fonction `require()`

Pour exécuter du code **une fois** que les dépendances sont chargées :

```js
require(['jquery', 'mage/translate'], function ($, $t) {
    $(document).ready(function () {
        console.log($t('Hello Magento'));
    });
});
```

Utilisé principalement dans les `.phtml` pour initialiser un composant.

### 2.4 `requirejs-config.js` — La carte des modules

Dans chaque module, on peut créer un `requirejs-config.js` pour :
- **Raccourcir** les chemins longs
- **Remplacer** un module par un autre (override)

```js
// StorePickup/view/frontend/requirejs-config.js
var config = {
    map: {
        '*': {
            // 'alphacommerceStorePickup' est un alias
            // Pour charger 'AlpineCommerce_StorePickup/js/view/store-pickup'
            alphacommerceStorePickup: 'AlpineCommerce_StorePickup/js/view/store-pickup'
        }
    }
};
```

Ainsi, dans un layout XML, on peut écrire :
```xml
<item name="component" xsi:type="string">alphacommerceStorePickup</item>
```
au lieu de :
```xml
<item name="component" xsi:type="string">AlpineCommerce_StorePickup/js/view/store-pickup</item>
```

---

## 3. KnockoutJS — L'UI réactive de Magento

### 3.1 Qu'est-ce que KnockoutJS ?

**KnockoutJS** (KO) est une bibliothèque de **data-binding** :
- Le HTML a des `data-bind` qui "écoutent" le JS
- Quand le JS change, le HTML se met à jour **automatiquement**
- Quand l'utilisateur change le HTML (input, select), le JS est mis à jour

### 3.2 Les concepts clés

#### Observables

```js
// Dans le JS
this.pointsUsed = ko.observable(0);          // Variable réactive
this.isSyncing = ko.observable(false);       // Boolean réactif
this.availableStores = ko.observableArray([]); // Array réactif

// Lire la valeur
var used = this.pointsUsed();   // 0

// Écrire la valeur
this.pointsUsed(100);           // Maintenant c'est 100
```

#### Computed (dérivés)

```js
// Ce computed se met à jour automatiquement quand pointsUsed ou redemptionRate change
this.discount = ko.computed(function () {
    return this.pointsUsed() * this.redemptionRate;
}, this);
```

#### Bindings dans le HTML

```html
<!-- Afficher la valeur -->
<span data-bind="text: pointsUsed"></span>

<!-- Input lié à un observable -->
<input type="number" data-bind="value: pointsUsed, valueUpdate: 'input'" />

<!-- Affichage conditionnel -->
<div data-bind="visible: pointsUsed() > 0">Discount applied</div>

<!-- Liste déroulante -->
<select data-bind="
    options: availableStores,
    optionsText: 'name',
    optionsValue: 'source_code',
    value: selectedSourceCode">
</select>

<!-- Texte traduit -->
<span data-bind="i18n: 'Choose your pickup store'"></span>
```

### 3.3 Exemple complet : StorePickup

**JS** (`store-pickup.js`) :
```js
define(['ko', 'Magento_Checkout/js/view/summary/abstract-total', ...], 
function (ko, Component, ...) {
    return Component.extend({
        initialize: function () {
            this._super();
            
            // Observables
            this.availableStores = ko.observableArray([]);
            this.selectedSourceCode = ko.observable();
            this.isSaving = ko.observable(false);
            this.syncMessage = ko.observable('');
            
            // Computed : visible seulement si méthode pickup ET stores disponibles
            this.isVisible = ko.computed(function () {
                return this.isPickupMethod() && this.availableStores().length > 0;
            }, this);
            
            // Computed : texte d'info sur le store sélectionné
            this.selectedStoreInfo = ko.computed(function () {
                var code = this.selectedSourceCode();
                var store = this.findStore(code);
                return store ? store.street + ', ' + store.city : '';
            }, this);
        },
        
        // Appelé quand l'utilisateur change le select
        saveStore: function () {
            var code = this.selectedSourceCode() || '';
            storage.post('/carts/mine/store-pickup', JSON.stringify({sourceCode: code}), ...);
        }
    });
});
```

**HTML** (`store-pickup.html`) :
```html
<select data-bind="
    options: availableStores,
    optionsText: 'name',
    optionsValue: 'source_code',
    value: selectedSourceCode,
    event: {change: saveStore}">
</select>

<p data-bind="text: selectedStoreInfo"></p>
<p data-bind="text: syncMessage, visible: syncMessage"></p>
```

**Flux** :
1. L'utilisateur change le `<select>`
2. Knockout met à jour `selectedSourceCode`
3. Le `data-bind="event: {change: saveStore}"` appelle `saveStore()`
4. `saveStore()` fait un AJAX vers le REST API
5. Le serveur répond → `syncMessage` est mis à jour
6. Le `<p data-bind="text: syncMessage">` se met à jour automatiquement

---

## 4. jQuery dans Magento

### 4.1 jQuery est inclus mais avec des précautions

jQuery est disponible via RequireJS :
```js
define(['jquery'], function ($) {
    'use strict';
    
    $(document).ready(function () {
        // Ton code jQuery
    });
});
```

### 4.2 Pattern classique : formulaire + AJAX

Utilisé dans **ProductReviews** et **ProductQuestions**.

```js
define(['jquery', 'mage/utils', 'mage/mage'], function ($, utils) {
    'use strict';
    
    return {
        init: function () {
            $('#submit-review').on('click', function () {
                var title = $('#review-title').val();
                var detail = $('#review-detail').val();
                
                $.ajax({
                    url: '/rest/V1/alphacommerce/product-reviews',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        productId: parseInt(productId),
                        title: title,
                        detail: detail
                    }),
                    success: function () {
                        alert('Review submitted!');
                        window.location.reload();
                    },
                    error: function (xhr) {
                        alert('Error: ' + xhr.responseText);
                    }
                });
            });
        }
    };
});
```

### 4.3 Initialisation depuis un `.phtml`

```html
<!-- Dans le template -->
<div id="review-form-container">
    <button id="submit-review">Submit</button>
</div>

<script type="text/x-magento-init">
{
    "#review-form-container": {
        "reviewForm": {
            "submitBtnSelector": "#submit-review"
        }
    }
}
</script>
```

Magento lit ce `data-mage-init` (ou `<script type="text/x-magento-init">`) et
appelle automatiquement `reviewForm.init(submitBtnSelector)`.

---

## 5. Les bibliothèques `mage/*` (Magento core)

Magento emballe ses propres utilitaires sous le namespace `mage/`.

| Module | Usage | Exemple |
|--------|-------|---------|
| `mage/storage` | AJAX avec gestion de session/cookies | `storage.post(url, data, false, 'application/json')` |
| `mage/translate` | Traduction côté client | `$t('Hello')` |
| `mage/mage` | Initialisation jQuery UI/Magento | `$('.selector').mage(...)` |
| `mage/utils` | Utilitaires (validation, templates) | `utils.stringify(object)` |

### 5.1 `mage/storage` vs `$.ajax`

**Ne jamais utiliser `$.ajax` directement** pour les appels Magento :
- `mage/storage` gère les cookies de session automatiquement
- Il gère les erreurs 401 (redirection vers login)
- Il ajoute les headers `X-Requested-With: XMLHttpRequest`

```js
// ✅ Bien : mage/storage
define(['mage/storage'], function (storage) {
    storage.post('/rest/V1/cart', JSON.stringify(data), false, 'application/json')
        .done(function (response) { /* ... */ });
});

// ❌ Éviter : $.ajax brut
$.ajax({ url: '/rest/V1/cart', ... });
```

### 5.2 `mage/translate`

```js
define(['mage/translate'], function ($t) {
    return $t('Pickup store saved.');
});
```

Magento cherche la traduction dans les fichiers CSV (`i18n/fr_FR.csv`, etc.).

---

## 6. Les 3 patterns JS dans AlpineCommerce

### 6.1 Pattern UI Component + KnockoutJS (moderne)

**Utilisé pour** : checkout, composants interactifs complexes

**Modules** : StorePickup, LoyaltyProgram

**Structure** :
```
view/frontend/
├── requirejs-config.js      # alias du module
├── web/
│   ├── js/
│   │   └── view/
│   │       └── store-pickup.js   # Composant KO (observables, computed)
│   └── template/
│       └── store-pickup.html     # Template KO (data-bind)
```

**Quand l'utiliser** :
- Interface réactive avec beaucoup d'états
- Besoin de computed values
- Intégration dans le checkout Magento (qui utilise massivement KO)

### 6.2 Pattern jQuery + AJAX (classique)

**Utilisé pour** : formulaires simples, interactions ponctuelles

**Modules** : ProductReviews, ProductQuestions

**Structure** :
```
view/frontend/
├── web/
│   ├── js/
│   │   └── review-form.js       # Init jQuery, événements
│   └── templates/
│       └── review_form.phtml    # HTML + data-mage-init
```

**Quand l'utiliser** :
- Formulaire de soumission simple
- Vote / like
- Peu d'états à gérer

### 6.3 Pattern Vanilla JS (léger)

**Utilisé pour** : filtres, recherche côté client, micro-interactions

**Modules** : StoreLocator

**Structure** :
```
view/frontend/
└── web/
    └── js/
        └── store-locator.js     # Pas de jQuery, pas de KO
```

**Quand l'utiliser** :
- Aucune dépendance externe nécessaire
- Performance critique
- Logique simple (filter, sort)

---

## 7. Comment intégrer du JS dans Magento

### 7.1 Via le Layout XML (composants UI)

```xml
<!-- view/frontend/layout/checkout_cart_index.xml -->
<referenceBlock name="checkout.cart.totals">
    <arguments>
        <argument name="js_config" xsi:type="array">
            <item name="component" xsi:type="string">alphacommerceStorePickup</item>
        </argument>
        <argument name="data" xsi:type="array">
            <item name="availableStores" xsi:type="object">AlpineCommerce\StorePickup\Block\Adminhtml\Store\Source\StoreInfo</item>
        </argument>
    </arguments>
</referenceBlock>
```

### 7.2 Via `data-mage-init` dans un `.phtml`

```php
<!-- view/frontend/templates/review/form.phtml -->
<div id="review-form" 
     data-mage-init='{"reviewForm": {"submitBtnSelector": "#submit-review"}}'>
    <button id="submit-review">Submit</button>
</div>

<script type="text/x-magento-init">
{
    "#review-form": {
        "reviewForm": {
            "submitBtnSelector": "#submit-review"
        }
    }
}
</script>
```

Magento charge automatiquement `reviewForm` (défini dans `review-form.js`)
et appelle `init(submitBtnSelector)`.

### 7.3 Via `requirejs-config.js` + composant KO

```xml
<!-- view/frontend/layout/checkout_index_index.xml -->
<referenceContainer name="checkout.cart.totals">
    <block class="Magento\Checkout\Block\Cart\Totals"
           name="loyalty.points"
           template="AlpineCommerce_LoyaltyProgram::points.phtml">
        <arguments>
            <argument name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">alphacommerceLoyaltyPoints</item>
            </argument>
        </arguments>
    </block>
</referenceContainer>
```

Le composant KO est automatiquement instancié par Magento.

---

## 8. Bonnes pratiques Magento

| Pratique | Raison | AlpineCommerce |
|----------|--------|----------------|
| **Toujours utiliser `define()`** | RequireJS est obligatoire | ✅ Tous les fichiers |
| **Toujours mettre `'use strict';`** | Détecte les erreurs de scope | ✅ Tous les fichiers |
| **Utiliser `mage/storage` pour l'AJAX** | Gère session + erreurs | ✅ StorePickup, LoyaltyProgram |
| **Utiliser `mage/translate` pour les strings** | i18n automatique | ✅ Tous les modules |
| **Nommer les paramètres de `define()` correctement** | `$` pour jQuery, `ko` pour KO | ✅ |
| **Éviter les IDs en dur dans jQuery** | Conflits possibles | ⚠️ Review/Question utilisent `#submit-review` |
| **Ne pas polluer le scope global** | RequireJS isole les modules | ✅ Pas de `var` global |
| **Utiliser KO pour l'UI complexe** | Réactivité, computed values | ✅ StorePickup, LoyaltyProgram |
| **Utiliser jQuery pour les formulaires simples** | Rapide à écrire | ✅ Review, Question |

---

## 9. Pièges courants

### 9.1 Oublier `'use strict';`

```js
// ❌ Sans 'use strict'
function test() {
    x = 5; // Crée une variable globale (erreur silencieuse)
}

// ✅ Avec 'use strict'
function test() {
    'use strict';
    x = 5; // ReferenceError: x is not defined
}
```

### 9.2 Mauvais ordre des paramètres dans `define()`

```js
// ❌ ERREUR : les paramètres ne correspondent pas aux dépendances
define(['jquery', 'ko'], function (ko, $) {
    // $ est en fait KO, ko est en fait jQuery
});

// ✅ Correct
define(['jquery', 'ko'], function ($, ko) {
    // $ = jQuery, ko = Knockout
});
```

### 9.3 Utiliser `$` en global

```js
// ❌ $ n'existe pas forcément dans le scope global (protect mode jQuery)
$('#id').click(...);

// ✅ Utiliser le $ injecté par RequireJS
define(['jquery'], function ($) {
    $('#id').click(...);
});
```

### 9.4 Oublier `return` dans le module

```js
// ❌ Le module ne retourne rien, les autres ne peuvent pas l'utiliser
define(['jquery'], function ($) {
    function init() { ... }
});

// ✅ Retourner l'objet public
define(['jquery'], function ($) {
    return {
        init: function () { ... }
    };
});
```

### 9.5 Knockout : oublier les parenthèses pour lire un observable

```js
// ❌ Affiche "[object Function]" car pointsUsed est la fonction elle-même
console.log(this.pointsUsed);

// ✅ Affiche la valeur (ex: 100)
console.log(this.pointsUsed());
```

### 9.6 `mage/storage` : oublier le 4ème paramètre

```js
// ❌ Erreur de Content-Type
storage.post(url, JSON.stringify(data));

// ✅ Spécifier le Content-Type pour le JSON
storage.post(url, JSON.stringify(data), false, 'application/json');
```

---

## 10. Debugger du JS dans Magento

### 10.1 Chrome DevTools

```
F12 → Console
```

**Voir les modules RequireJS chargés** :
```js
require.s.contexts._.defined
```

**Tester un module** :
```js
require(['AlpineCommerce_StorePickup/js/view/store-pickup'], function (Module) {
    console.log(Module);
});
```

**Inspecter un observable KO** :
```js
// Dans la console, si tu as accès au composant :
$t('Pickup store saved.');
```

### 10.2 Activer les erreurs RequireJS

Dans le navigateur :
```js
requirejs.onError = function (err) {
    console.error('RequireJS error:', err);
};
```

### 10.3 Voir les fichiers JS chargés

```
F12 → Network → Filtrer par "JS"
```

---

## 11. Tableau de correspondance AlpineCommerce

| Concept JS | Fichier AlpineCommerce | Ligne | Explication |
|------------|------------------------|-------|-------------|
| `define([...])` | Tous les `.js` | 1 | Chargement modulaire |
| `ko.observable()` | `store-pickup.js` | 22-25 | Variables réactives |
| `ko.computed()` | `store-pickup.js` | 27-33 | Valeurs dérivées |
| `data-bind="visible: ..."` | `store-pickup.html` | 6 | Binding KO |
| `data-bind="options: ..."` | `store-pickup.html` | 15-20 | Liste KO |
| `mage/storage.post()` | `store-pickup.js` | 74 | AJAX Magento |
| `mage/translate` | `store-pickup.js` | 80 | Traduction |
| `requirejs-config.js` | `store-pickup/requirejs-config.js` | 1 | Alias module |
| `$.ajax()` | `review-form.js` | 42 | AJAX jQuery |
| `data-mage-init` | `review_form.phtml` | — | Initialisation auto |
| Vanilla JS | `store-locator.js` | 1 | Pas de dépendance |

---

## 12. Ressources pour aller plus loin

- **RequireJS** : https://requirejs.org/docs/api.html
- **KnockoutJS** : https://knockoutjs.com/documentation/
- **Magento JS Dev Guide** : https://developer.adobe.com/commerce/frontend/core-js/
- **mage/translate** : https://github.com/magento/magento2/blob/2.4/lib/web/mage/translate.js
- **AlpineCommerce exemples** : `src/app/code/AlpineCommerce/StorePickup/view/frontend/web/js/`

---

## 13. Résumé

| Question | Réponse |
|----------|---------|
| **Pourquoi RequireJS ?** | Chargement modulaire, pas de conflits, pas de fichier inutile |
| **Qu'est-ce qu'un module AMD ?** | Un fichier JS qui déclare ses dépendances avec `define([...])` |
| **Quand utiliser KO ?** | UI réactive (checkout, formulaires dynamiques) |
| **Quand utiliser jQuery ?** | Formulaires simples, interactions ponctuelles |
| **Quand utiliser Vanilla JS ?** | Aucune dépendance nécessaire, logique simple |
| **Comment appeler le REST ?** | `mage/storage.post(url, data, false, 'application/json')` |
| **Comment traduire ?** | `define(['mage/translate'], function ($t) { $t('string'); })` |
| **Comment initialiser un composant ?** | `data-mage-init` dans le `.phtml` ou layout XML |

---

*Last updated: 2026-08-11.*
