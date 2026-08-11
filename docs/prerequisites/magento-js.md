# Magento 2 — JavaScript : Cours Complet

> **Objectif** : comprendre et écrire du JavaScript dans Magento 2, du
> premier `define()` jusqu'au composant interactif dans le checkout.
> Ce cours suppose que tu sais déjà ce qu'est une variable, une fonction,
> un objet et une classe en JavaScript.

---

## Table des matières

1. [Pourquoi Magento a son propre JavaScript ?](#1)
2. [RequireJS — Le système de modules](#2)
3. [KnockoutJS — L'interface réactive](#3)
4. [jQuery — DOM et AJAX](#4)
5. [Les bibliothèques `mage/*`](#5)
6. [Les 3 patterns JS du projet](#6)
7. [Intégrer du JS dans Magento](#7)
8. [Debugger du JS dans Magento](#8)
9. [Exercices pratiques](#9)
10. [Résumé](#10)

---

## 1. Pourquoi Magento a son propre JavaScript ? {#1}

### 1.1 Le problème du JavaScript "classique"

```html
<!-- Ancienne façon : plein de <script> dans le HTML -->
<script src="js/jquery.js"></script>
<script src="js/bootstrap.js"></script>
<script src="js/mon-script.js"></script>
<script src="js/autre-script.js"></script>
```

**Problèmes** :
- **Conflits de noms** : deux fichiers déclarent `var $`
- **Ordre critique** : si `mon-script.js` charge avant `jquery.js`, ça casse
- **Fichiers inutiles** : la page produit charge 500 Ko de JS alors qu'elle n'a besoin que de 50 Ko
- **Pas de réutilisabilité** : impossible de partager un composant entre deux pages

### 1.2 La solution Magento : RequireJS + KnockoutJS

Magento 2 utilise deux outils :

| Outil | Rôle | Analogie |
|-------|------|----------|
| **RequireJS** | Charger les scripts **à la demande**, avec dépendances explicites | Un chef qui ne commande en cuisine que les ingrédients nécessaires |
| **KnockoutJS** | Créer des interfaces **réactives** (quand le JS change, le HTML se met à jour tout seul) | Un tableau de bord qui se met à jour automatiquement quand les données changent |

### 1.3 Ce que tu dois retenir

- Tout fichier JS Magento commence par `define([...])`
- Les composants complexes (checkout) utilisent KnockoutJS
- Les formulaires simples utilisent jQuery
- Les appels au serveur passent par `mage/storage` (jamais `$.ajax` direct)

---

## 2. RequireJS — Le système de modules {#2}

### 2.1 Qu'est-ce qu'un module AMD ?

**AMD** = *Asynchronous Module Definition*. C'est une façon de déclarer
un fichier JS qui :
- liste ses **dépendances** (quels autres fichiers il faut charger avant lui)
- retourne ce qu'il **expose** aux autres fichiers

```js
// Ce fichier JS EST un module AMD
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';
    
    // Code du module...
    
    return {
        // Ce que les autres peuvent utiliser
        hello: function () {
            return $t('Hello');
        }
    };
});
```

### 2.2 La fonction `define()` — syntaxe obligatoire

```js
define([
    'jquery',           // Dépendance 1
    'mage/translate'    // Dépendance 2
], function ($, $t) {   // Paramètres : même ordre que les dépendances
    'use strict';
    
    // Ton code ici
    
    return { /* API publique */ };
});
```

**Règles strictes** :
1. Le nom des paramètres **doit** correspondre à l'ordre des dépendances
2. `'use strict';` est **obligatoire** en première ligne du corps
3. Le `return` expose l'API publique (ce qui n'est pas returné est privé)

### 2.3 Les chemins des dépendances

| Chemins | Exemple | Explication |
|---------|---------|-------------|
| **Alias court** | `'jquery'` | Module natif Magento, disponible partout |
| **Module Magento** | `'mage/storage'` | Librairie Magento (AJAX, traduction...) |
| **Module custom** | `'AlpineCommerce_StorePickup/js/view/store-pickup'` | Ton propre module |
| **Alias custom** | `'alphacommerceStorePickup'` | Défini dans `requirejs-config.js` |

### 2.4 `requirejs-config.js` — la carte des modules

Chaque module peut créer un alias pour raccourcir les chemins :

```js
// StorePickup/view/frontend/requirejs-config.js
var config = {
    map: {
        '*': {
            // 'alphacommerceStorePickup' est maintenant un alias pour :
            alphacommerceStorePickup: 'AlpineCommerce_StorePickup/js/view/store-pickup'
        }
    }
};
```

Utilisation dans un layout XML :
```xml
<item name="component" xsi:type="string">alphacommerceStorePickup</item>
```

### 2.5 `require()` — exécution ponctuelle

Pour exécuter du code **une seule fois** (pas un module réutilisable) :

```js
require(['jquery', 'mage/translate'], function ($, $t) {
    $(document).ready(function () {
        console.log($t('Page loaded'));
    });
});
```

**Quand l'utiliser** : dans un `.phtml` pour initialiser quelque chose.

---

## 3. KnockoutJS — L'interface réactive {#3}

### 3.1 Le concept de binding

Avec KnockoutJS, tu lies (`bind`) le HTML au JavaScript :

```html
<!-- HTML -->
<input data-bind="value: userName">
<p data-bind="text: userName"></p>
```

```js
// JavaScript
this.userName = ko.observable('Alice');
```

**Résultat** :
- L'input affiche "Alice"
- Si tu changes l'input → le `<p>` se met à jour tout seul
- Si tu changes `this.userName('Bob')` en JS → l'input ET le `<p>` se mettent à jour

### 3.2 Les observables (`ko.observable`)

Un **observable** est une variable "réactive" : quand sa valeur change,
tout ce qui y est lié se met à jour.

```js
// DÉCLARATION
var count = ko.observable(0);          // nombre
var name = ko.observable('Alice');      // texte
var isActive = ko.observable(true);     // boolean
var items = ko.observableArray([]);     // tableau

// LECTURE → ajouter les parenthèses ()
console.log(count());        // 0
console.log(name());         // 'Alice'

// ÉCRITURE → appeler comme une fonction
count(10);
name('Bob');
isActive(false);
items.push({id: 1, title: 'Product 1'});
```

### 3.3 Les computed (`ko.computed`)

Un **computed** est une valeur **calculée automatiquement** à partir
d'autres observables :

```js
var firstName = ko.observable('Alice');
var lastName = ko.observable('Dupont');

// Ce computed se recalcule AUTOMATIQUEMENT
// quand firstName ou lastName change
var fullName = ko.computed(function () {
    return firstName() + ' ' + lastName();
});

console.log(fullName()); // 'Alice Dupont'

firstName('Bob');
console.log(fullName()); // 'Bob Dupont' (recalculé automatiquement)
```

### 3.4 Les bindings dans le HTML

| Binding | Rôle | Exemple |
|---------|------|---------|
| `text` | Afficher du texte | `data-bind="text: userName"` |
| `value` | Lier un input à une variable | `data-bind="value: count"` |
| `visible` | Afficher/masquer | `data-bind="visible: count() > 0"` |
| `click` | Clic sur un bouton | `data-bind="click: increment"` |
| `event` | Autres événements | `data-bind="event: {change: save}"` |
| `options` | Liste déroulante | `data-bind="options: items, optionsText: 'name'"` |
| `foreach` | Boucler sur une liste | `data-bind="foreach: items"` |
| `i18n` | Traduire | `data-bind="i18n: 'Hello'"` |
| `attr` | Attribut HTML dynamique | `data-bind="attr: {for: inputId}"` |

### 3.5 Exemple complet : StorePickup

**JavaScript** (`store-pickup.js`) :
```js
define(['ko', 'mage/storage', 'mage/translate'], function (ko, storage, $t) {
    'use strict';
    
    return {
        initialize: function () {
            // 1. Observables (état)
            this.availableStores = ko.observableArray([]);
            this.selectedStore = ko.observable('');
            this.isSaving = ko.observable(false);
            this.message = ko.observable('');
            
            // 2. Computed (dérivés)
            this.isVisible = ko.computed(function () {
                return this.selectedStore() !== '';
            }, this);
            
            this.selectedStoreName = ko.computed(function () {
                var code = this.selectedStore();
                var store = this.findStore(code);
                return store ? store.name : '';
            }, this);
        },
        
        findStore: function (code) {
            return this.availableStores().find(function (s) {
                return s.code === code;
            });
        },
        
        saveStore: function () {
            var self = this;
            this.isSaving(true);
            
            storage.post('/carts/mine/store-pickup', 
                JSON.stringify({sourceCode: this.selectedStore()}), 
                false, 
                'application/json'
            ).done(function () {
                self.message($t('Store saved'));
            }).fail(function () {
                self.message($t('Error saving store'));
            }).always(function () {
                self.isSaving(false);
            });
        }
    };
});
```

**HTML** (`store-pickup.html`) :
```html
<div data-bind="visible: isVisible">
    <label data-bind="i18n: 'Choose your store'"></label>
    
    <select data-bind="
        options: availableStores,
        optionsText: 'name',
        optionsValue: 'code',
        value: selectedStore,
        event: {change: saveStore}">
    </select>
    
    <p data-bind="text: selectedStoreName"></p>
    <p data-bind="text: message, visible: message"></p>
</div>
```

**Flux** :
1. L'utilisateur change le `<select>`
2. Knockout met à jour `selectedStore`
3. Le `event: {change: saveStore}` déclenche `saveStore()`
4. `saveStore()` appelle le REST via `mage/storage`
5. Le serveur répond → `message` est mis à jour
6. Le `<p data-bind="text: message">` se met à jour **tout seul**

---

## 4. jQuery — DOM et AJAX {#4}

### 4.1 jQuery dans Magento

jQuery est disponible via RequireJS. **Ne jamais utiliser `$` en global** :

```js
// ✅ Correct
define(['jquery'], function ($) {
    'use strict';
    $('#mon-element').click(function () { ... });
});

// ❌ Faux (ne marche pas dans Magento)
$('#mon-element').click(function () { ... });
```

### 4.2 Sélection et manipulation du DOM

```js
define(['jquery'], function ($) {
    'use strict';
    
    // Sélectionner
    var $btn = $('#submit-btn');           // par ID
    var $form = $('.review-form');         // par classe
    var $item = $('[data-product-id="5"]'); // par attribut
    
    // Lire des valeurs
    var title = $('#review-title').val();
    var rating = $('input[name="rating"]:checked').val();
    
    // Modifier le DOM
    $btn.prop('disabled', true);
    $form.addClass('loading');
    $('#result').html('<p>Done</p>');
    
    // Événements
    $btn.on('click', function () {
        console.log('Clicked');
    });
    
    // Délégué (pour les éléments dynamiques)
    $(document).on('click', '.vote-btn', function () {
        var reviewId = $(this).data('review-id');
    });
});
```

### 4.3 AJAX avec `$.ajax`

```js
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';
    
    function submitReview() {
        var data = {
            productId: parseInt($('#product-id').val()),
            title: $('#review-title').val(),
            detail: $('#review-detail').val()
        };
        
        $.ajax({
            url: '/rest/V1/alphacommerce/product-reviews',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function (response) {
                alert($t('Review submitted'));
            },
            error: function (xhr) {
                alert($t('Error') + ': ' + xhr.responseText);
            }
        });
    }
});
```

---

## 5. Les bibliothèques `mage/*` {#5}

Magento fournit des utilitaires encapsulés sous le namespace `mage/`.

### 5.1 `mage/storage` — AJAX sécurisé

```js
define(['mage/storage'], function (storage) {
    'use strict';
    
    // POST avec JSON
    storage.post(
        '/rest/V1/cart',           // URL
        JSON.stringify(data),       // Corps de la requête
        false,                      // parallèle (pas d'overlay de chargement)
        'application/json'          // Content-Type
    ).done(function (response) {
        // Succès
    }).fail(function (xhr) {
        // Erreur
    });
    
    // GET
    storage.get('/rest/V1/products/1', false)
        .done(function (product) { ... });
    
    // PUT
    storage.put('/rest/V1/cart/1', JSON.stringify(data), false, 'application/json')
        .done(function () { ... });
    
    // DELETE
    storage.delete('/rest/V1/cart/1', false)
        .done(function () { ... });
});
```

**Pourquoi `mage/storage` au lieu de `$.ajax` ?**
- Gère automatiquement les cookies de session
- Gère les erreurs 401 (redirection vers login)
- Ajoute le header `X-Requested-With: XMLHttpRequest`
- Formate les erreurs Magento

### 5.2 `mage/translate` — Traduction côté client

```js
define(['mage/translate'], function ($t) {
    'use strict';
    
    var msg = $t('Hello World');
    // Cherche la traduction dans i18n/fr_FR.csv, i18n/de_DE.csv, etc.
    
    // Avec paramètres
    var msg2 = $t('Hello %1, you have %2 products').replace('%1', 'Alice').replace('%2', '5');
});
```

### 5.3 `mage/mage` — Initialisation jQuery UI

```js
define(['jquery', 'mage/mage'], function ($) {
    'use strict';
    
    // Initialise les composants Magento sur un élément
    $('#my-form').mage('validation', { /* règles de validation */ });
});
```

### 5.4 `mage/utils` — Utilitaires

```js
define(['mage/utils'], function (utils) {
    'use strict';
    
    // Sérialiser un objet en query string
    var query = utils.stringify({page: 1, limit: 10});
    // → "page=1&limit=10"
    
    // Cloner un objet profondément
    var copy = utils.copy(original);
});
```

---

## 6. Les 3 patterns JS du projet {#6}

### 6.1 Pattern 1 : UI Component + KnockoutJS

**Utilisé pour** : checkout, composants interactifs complexes

**Modules** : StorePickup, LoyaltyProgram

**Structure** :
```
view/frontend/
├── requirejs-config.js       # Alias du module
├── web/
│   ├── js/
│   │   └── view/
│   │       └── store-pickup.js   # Composant KO (observables, computed)
│   └── template/
│       └── store-pickup.html     # Template KO (data-bind)
```

**Caractéristiques** :
- `define(['ko', ...], function (ko, ...)`
- Retourne un objet avec `initialize()` (pattern Magento)
- Observables pour l'état, computed pour les dérivés
- Template HTML avec `data-bind`
- Intégré via layout XML (`js_config/component`)

### 6.2 Pattern 2 : jQuery + AJAX

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

**Caractéristiques** :
- `define(['jquery', ...], function ($, ...)`
- Retourne un objet avec `init()`
- Événements jQuery (`$('#id').on('click', ...)`)
- AJAX avec `$.ajax` ou `mage/storage`
- Initialisé via `data-mage-init` dans le `.phtml`

### 6.3 Pattern 3 : Vanilla JS (léger)

**Utilisé pour** : filtres, recherche côté client

**Modules** : StoreLocator

**Structure** :
```
view/frontend/
└── web/
    └── js/
        └── store-locator.js     # Pas de jQuery, pas de KO
```

**Caractéristiques** :
- `define(['mage/translate'], function ($t) { ... })`
- Retourne une fonction `(config, element) => { ... }`
- DOM natif (`querySelector`, `addEventListener`)
- Aucune dépendance lourde

---

## 7. Intégrer du JS dans Magento {#7}

### 7.1 Méthode 1 : `data-mage-init` dans un `.phtml`

```php
<!-- review_form.phtml -->
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

Magento charge `reviewForm` et appelle `init(submitBtnSelector)`.

### 7.2 Méthode 2 : Layout XML (composants UI)

```xml
<referenceContainer name="product.info.main">
    <block class="Magento\Framework\View\Element\Template"
           name="store.pickup"
           template="AlpineCommerce_StorePickup::store-pickup.phtml">
        <arguments>
            <argument name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">alphacommerceStorePickup</item>
            </argument>
            <argument name="data" xsi:type="array">
                <item name="availableStores" xsi:type="object">
                    AlpineCommerce\StorePickup\Block\Adminhtml\Store\Source\StoreInfo
                </item>
            </argument>
        </arguments>
    </block>
</referenceContainer>
```

### 7.3 Méthode 3 : `requirejs-config.js` + KO Component

```xml
<!-- checkout_index_index.xml -->
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

## 8. Debugger du JS dans Magento {#8}

### 8.1 Chrome DevTools

```
F12 → Console
```

**Voir les modules RequireJS chargés** :
```js
require.s.contexts._.defined
// Affiche tous les modules chargés avec leurs exports
```

**Tester un module** :
```js
require(['AlpineCommerce_StorePickup/js/view/store-pickup'], function (Module) {
    console.log(Module);
});
```

### 8.2 Erreurs courantes

| Erreur | Cause | Solution |
|--------|-------|----------|
| `Uncaught Error: Module name ... has not been loaded yet` | Dépendance mal orthographiée | Vérifier le nom dans `define([...])` |
| `$ is not a function` | jQuery pas chargé ou mal injecté | Vérifier l'ordre des paramètres dans `define()` |
| `ko is not defined` | Knockout pas déclaré comme dépendance | Ajouter `'ko'` dans `define([...])` |
| `define is not defined` | Fichier pas chargé via RequireJS | Utiliser `define()`, pas de `<script>` inline |
| `data-bind` ne fonctionne pas | Template KO pas lié au composant | Vérifier `template:` et `component:` dans le layout |

### 8.3 Activer les erreurs RequireJS

```js
// Dans la console navigateur
requirejs.onError = function (err) {
    console.error('RequireJS error:', err.requireModules);
};
```

### 8.4 Voir le réseau JS

```
F12 → Network → Filtrer par "JS"
```

Permet de voir quels fichiers sont chargés, dans quel ordre, et si un
fichier est manquant (404).

---

## 9. Exercices pratiques {#9}

### Exercice 1 : Premier module RequireJS

**Objectif** : créer un module qui affiche "Hello Magento" dans un `<div>`.

**Étapes** :
1. Créer `src/app/code/AlpineCommerce/Blog/view/frontend/web/js/hello.js`
2. Créer un template `src/app/code/AlpineCommerce/Blog/view/frontend/templates/hello.phtml`
3. Ajouter un layout `view/frontend/layout/blog_index_index.xml`
4. Ajouter `data-mage-init` dans le template

**Solution** :
```js
// hello.js
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';
    return {
        run: function () {
            $('#hello-container').html('<p>' + $t('Hello Magento') + '</p>');
        }
    };
});
```

```html
<!-- hello.phtml -->
<div id="hello-container"></div>
<script type="text/x-magento-init">
{
    "#hello-container": {
        "AlpineCommerce_Blog/js/hello": {}
    }
}
</script>
```

### Exercice 2 : Observable KO simple

**Objectif** : créer un compteur avec un bouton + et -.

**Solution** :
```js
define(['ko'], function (ko) {
    'use strict';
    return {
        initialize: function () {
            this.count = ko.observable(0);
        },
        increment: function () {
            this.count(this.count() + 1);
        },
        decrement: function () {
            this.count(this.count() - 1);
        }
    };
});
```

```html
<div data-bind="with: $parent">
    <p>Count: <span data-bind="text: count"></span></p>
    <button data-bind="click: increment">+</button>
    <button data-bind="click: decrement">-</button>
</div>
```

### Exercice 3 : AJAX vers le REST

**Objectif** : soumettre un formulaire et afficher la réponse.

**Solution** :
```js
define(['jquery', 'mage/storage', 'mage/translate'], function ($, storage, $t) {
    'use strict';
    return {
        submit: function () {
            var data = {
                title: $('#title').val(),
                detail: $('#detail').val()
            };
            storage.post('/rest/V1/alphacommerce/product-reviews',
                JSON.stringify(data), false, 'application/json'
            ).done(function () {
                alert($t('Submitted'));
            });
        }
    };
});
```

### Exercice 4 : Computed KO

**Objectif** : calculer le prix total (quantité × prix unitaire) en temps réel.

**Solution** :
```js
define(['ko'], function (ko) {
    'use strict';
    return function () {
        this.quantity = ko.observable(1);
        this.unitPrice = 29.99;
        this.total = ko.computed(function () {
            return (this.quantity() * this.unitPrice).toFixed(2);
        }, this);
    };
});
```

```html
<input type="number" data-bind="value: quantity, valueUpdate: 'input'">
<p>Total: $<span data-bind="text: total"></span></p>
```

---

## 10. Résumé {#10}

| Concept | Magento équivalent | AlpineCommerce exemple |
|---------|-------------------|------------------------|
| Module JS | `define([...], function (...) { ... })` | Tous les `.js` |
| Chargement modulaire | RequireJS | `requirejs-config.js` |
| UI réactive | KnockoutJS | StorePickup, LoyaltyProgram |
| DOM + AJAX | jQuery | ProductReviews, ProductQuestions |
| AJAX sécurisé | `mage/storage` | StorePickup `saveStore()` |
| Traduction | `mage/translate` | Tous les modules |
| Initialisation | `data-mage-init` | `review_form.phtml` |
| Composant UI | `js_config/component` | Layout XML checkout |
| Observable | `ko.observable()` | `selectedStore`, `pointsUsed` |
| Computed | `ko.computed()` | `isVisible`, `totalPrice` |
| Template | `web/template/*.html` | `store-pickup.html` |
| Alias | `requirejs-config.js` | `alphacommerceStorePickup` |

### Ce qu'il faut maîtriser

1. **`define([...], function (...) { ... })`** — structure obligatoire de tout fichier JS Magento
2. **`ko.observable()`** — pour les variables réactives
3. **`ko.computed()`** — pour les valeurs calculées
4. **`mage/storage`** — pour les appels REST
5. **`mage/translate`** — pour les traductions
6. **`data-bind`** — pour lier le HTML au JS
7. **`data-mage-init`** — pour initialiser un composant depuis un `.phtml`
8. **Layout XML** — pour intégrer un composant UI dans une page

### Prochaines étapes

- Lire les fichiers JS d'AlpineCommerce : `StorePickup/view/frontend/web/js/view/store-pickup.js`
- Créer un module JS simple (exercice 1)
- Ajouter un composant KO dans le checkout
- Explorer les fichiers `mage/*` dans `lib/web/`

---

*Last updated: 2026-08-11.*
