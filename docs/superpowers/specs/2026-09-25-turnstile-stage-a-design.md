# AlpineCommerce_Turnstile — Étape A : registre, observer générique, Protected Forms, jumelles API — Design

- **Date :** 2026-09-25
- **Statut :** brouillon v2 (R1 et R4 vérifiés, R2 et R3 intégrés), en attente de validation par l'utilisateur (aucun code écrit)
- **Le quoi :** `docs/superpowers/specs/2026-09-25-turnstile-definition-of-done.md` (DoD). Ce document décrit uniquement
  le **comment** des lignes marquées **A** dans la DoD.
- **Remplace :** `2026-09-24-turnstile-register-design.md` et `plans/2026-09-24-turnstile-register.md` (jamais exécutés,
  non commités). Leurs décisions sont reprises ici : observer générique, persisters, mots de passe jamais conservés,
  mode de panne par formulaire, token lu dans le corps POST, jamais d'échec ouvert quand le type de réponse est inattendu.
- **Décisions appliquées :** D3 = A, D4 = B (REST, SOAP, GraphQL, async/bulk à confirmer), D6 = 1 + a, D7 = 1.
- **Décisions non tranchées, comportement inchangé en A :** D1 (clé manquante → vérification ignorée + log, comme
  aujourd'hui), D2 (pas de limitation du nombre de requêtes), D5 (versions supportées).

## 1. Faits vérifiés dans le code (base du design)

| Fait | Source |
|---|---|
| Le CSRF (`form_key`) est validé **avant** `controller_action_predispatch` ; un POST avec `X-Requested-With` n'est pas soumis au `form_key` | `framework/App/FrontController.php:177-212`, `framework/App/Request/CsrfValidator.php:74` |
| `FLAG_NO_DISPATCH` empêche l'exécution du contrôleur, y compris les contrôleurs `AbstractAction` (legacy) | `FrontController::getActionResponse()` ligne 240 |
| Un contrôleur sans interface `Http*ActionInterface` accepte toutes les méthodes | `framework/App/Request/HttpMethodValidator.php:84-92` |
| Magento place **tous les modules `Magento_*` avant les autres**, puis ne réordonne que selon `<sequence>` : même avec `<sequence>` réduit à Store et Config, `AlpineCommerce_Turnstile` est chargé après Customer, Contact, Review, SendFriend | `framework/Module/ModuleList/Loader.php` `sortBySequence()` + `prearrangeModules()` |
| Les layouts des **thèmes** sont fusionnés **après** ceux de tous les modules | `framework/View/Layout/File/Collector/Aggregated.php` `getFiles()` |
| Une référence de layout placée **avant** la déclaration du conteneur est bien appliquée : sous Hyvä, `form.additional.info` est déclaré par le thème (fusionné après notre module) et le widget s'affiche aujourd'hui dans le formulaire de contact | Vérifié le 2026-09-25 : `GET /french/contact/` (thème `AlpineCommerce/hyva`) → HTTP 200, `div.cf-turnstile data-action="contact"` **à l'intérieur** de `<form id="contact">` |
| `setup:di:compile` échoue si un constructeur d'une classe de production du module type-hinte une classe **absente** (`new ReflectionClass()` → `ReflectionException` → `COMPILATION_ERROR` → « Error during compilation »). Les dossiers `Test` sont exclus. Les `di.xml` d'une zone (`webapi_rest`, `webapi_soap`, `graphql`) ne sont lus que si cette zone est déclarée, c'est-à-dire si le module qui la déclare est présent | `framework/GetParameterClassTrait.php:41`, `framework/Code/Reader/ClassReader.php:42-57`, `setup/.../Di/Code/Reader/Decorator/Interceptions.php:79-81`, `setup/.../Di/Compiler/Log/Log.php:92`, `setup/.../Di/App/Task/Operation/Area.php:93`, `DiCompileCommand.php:262` ; zones déclarées par `module-webapi/etc/di.xml` et `module-graph-ql/etc/di.xml` |
| Structure de la config admin : tableau converti puis mis en cache (`backend_system_configuration_structure`, type de cache `config`) ; enfants d'un groupe sous `children`, clé `_elementType` | `module-config/Model/Config/Structure/Converter.php:42-43,132`, `Structure/Data.php` |
| Jumelles REST/SOAP : services `Magento\Customer\Api\AccountManagementInterface::createAccount`, `::initiatePasswordReset`, `Magento\Integration\Api\CustomerTokenServiceInterface::createCustomerAccessToken` | `module-customer/etc/webapi.xml`, `module-integration/etc/webapi.xml` |
| Point d'accroche REST : `Magento\Webapi\Controller\Rest\RequestValidator::validate()` + `Rest\Router::match()` ; SOAP : `Magento\Webapi\Controller\Soap\Request\Handler::__call()` | `module-re-captcha-webapi-rest/etc/di.xml`, `Plugin/RestValidationPlugin.php:84` |
| Les requêtes async et bulk appellent aussi `RequestValidator::validate()` | `module-webapi-async/Controller/Rest/Asynchronous/InputParamsResolver.php:124-128` — **non prouvé en exécution (S27c)** |
| Sessions de conservation de saisie : voir DoD §4.2 (K1–K7) | contrôleurs et blocs natifs cités dans la DoD |
| Conteneurs de layout (Luma, repris par Breeze) : `form.additional.info` pour contact, création de compte, connexion, mot de passe oublié, envoyer à un ami ; `form.additional.review.info` pour l'avis produit ; **aucun** pour newsletter et partage de wishlist | layouts `module-*/view/frontend/layout/*.xml` ; Hyvä : mêmes conteneurs sauf avis (GraphQL) |

## 2. Vue d'ensemble

```
            controller_action_predispatch (frontend)
                         │
               FormPredispatchObserver ──► FormRegistry.findByAction(fullActionName)
                         │                          │ (null → sortie immédiate)
                         ▼                          ▼
                     FormGuard ──► Config (activé ? mode de panne du formulaire)
                         │     ──► MethodPolicy (D3 = A)
                         │     ──► TokenReader (corps POST, puis en-tête X-Turnstile-Token)
                         │     ──► ValidatorInterface (cœur existant, inchangé)
                         ▼
                 échec → FailureResponder (JSON 400 ou redirection + message)
                       → FormDataPersisterInterface (DataPersistor ou SessionFormDataPersister, D7 = 1)

 Admin : ConfigStructurePlugin (afterConvert) ──► champs générés à partir de FormRegistry et TwinRegistry (D6 = 1 + a)

 Jumelles (D4 = B) : TwinRegistry ──► RestTwinGuard (RequestValidator)   [webapi_rest]
                                 ──► SoapTwinGuard (Soap Handler)        [webapi_soap]
                                 ──► GraphQlTwinGuard (ResolverInterface) [graphql]
```

Le cœur existant (`Validator`, `SiteVerifyClient`, `ValidationResult`, `ValidatorInterface`) ne change pas, sauf
un point : `Validator` transmet l'identifiant du formulaire à `Config::getFailureMode()` (mode de panne par formulaire).

## 3. Composants

### 3.1 Définition d'un formulaire

**`Api/FormDefinitionInterface`** (`@api`) — objet de données, sans logique :

| Méthode | Rôle |
|---|---|
| `getId(): string` | Identifiant ; utilisé aussi comme `action` Turnstile. Format `^[a-z0-9_]{1,32}$` (compatible avec la limite Cloudflare de 32 caractères) |
| `getLabel(): string` | Libellé admin (traduit) |
| `getActions(): string[]` | Noms d'action complets protégés (`route_controller_action`), comparés en minuscules |
| `getRequiredModule(): ?string` | Module Magento requis ; formulaire masqué et jamais appliqué s'il est désactivé (D6 = a) |
| `getFailureResponse(): string` | `auto` (défaut), `redirect` ou `json` |
| `getRedirectPath(): ?string` / `getRedirectParams(): array` | Cible de redirection ; `null` = page précédente validée par Magento (`RedirectInterface::getRefererUrl()`, qui refuse les URL externes), repli sur l'URL de base |
| `getPersister(): ?FormDataPersisterInterface` | Conservation de la saisie en cas de rejet ; `null` = rien |
| `getSortOrder(): int` | Ordre dans l'admin |

**`Model/FormDefinition`** : implémentation générique ; tous les préréglages sont des `virtualType` de cette classe dans
`etc/di.xml`. Un module tiers déclare le sien de la même manière dans son propre `di.xml` (exemple dans le README).

**`Model/FormRegistry`** : reçoit `array $forms` par DI (`FormDefinitionInterface[]`, clé = id).
- Contrôles au premier accès (erreurs de développeur → `\InvalidArgumentException`, message sans données) : format de
  l'id, id unique, clé du tableau = `getId()`, une action n'appartient qu'à un seul formulaire.
- `getAll()` : formulaires dont le module requis est activé (`Magento\Framework\Module\Manager::isEnabled`).
- `findByAction(string $fullActionName): ?FormDefinitionInterface` : comparaison en minuscules sur la table construite
  une fois par requête.

### 3.2 Préréglages (etc/di.xml)

| Id | Action(s) | Module requis | Redirection en cas d'échec | Persister | Défaut `config.xml` |
|---|---|---|---|---|---|
| `contact` | `contact_index_post` | `Magento_Contact` | `contact/index` | DataPersistor `contact_us` (K1) | **1** (inchangé) |
| `customer_create` | `customer_account_createpost` | `Magento_Customer` | `customer/account/create` (`_secure`) | Session client, `setCustomerFormData`, sans mots de passe (K2) | **1** (spec register approuvée) |
| `customer_login` | `customer_account_loginpost` | `Magento_Customer` | `customer/account/login` (`_secure`) | Session client, `setUsername` ← `login/username` (K3) | **1** |
| `customer_forgot_password` | `customer_account_forgotpasswordpost` | `Magento_Customer` | `customer/account/forgotpassword` | Session client, `setForgottenEmail` ← `email` (K4) | **1** |
| `sendfriend` | `sendfriend_product_sendmail` | `Magento_SendFriend` | page précédente | Session catalogue, `setSendfriendFormData` (K5) | **1** |
| `product_review` | `review_product_post` | `Magento_Review` | page précédente | Session avis, `setFormData` (K7) | **1** |
| `newsletter` | `newsletter_subscriber_new` | `Magento_Newsletter` | page précédente | aucun | **0** (widget impossible avant l'étape B) |
| `wishlist_share` | `wishlist_index_send` | `Magento_Wishlist` | `wishlist/index/share` | Session wishlist, `setSharingForm` (K6) | **0** (widget impossible avant l'étape B) |

*Défauts validés par l'utilisateur le 2026-09-25 (R3) :* les préréglages dont le widget peut s'afficher dès l'étape A
sont activés par défaut, comme `contact` aujourd'hui ; newsletter et partage de wishlist restent désactivés jusqu'à l'étape B. Turnstile reste **désactivé globalement** par défaut
(`general/enabled = 0`), donc aucun site n'est protégé sans action de l'admin.

### 3.3 Affichage du widget en A (layout, comme aujourd'hui)

- Fichiers de layout : `contact_index_index.xml` (existant), `customer_account_create.xml`, `customer_account_login.xml`,
  `customer_account_forgotpassword.xml`, `sendfriend_product_send.xml` → conteneur `form.additional.info` ;
  `catalog_product_view.xml` et `review_product_list.xml` → `form.additional.review.info` (Luma/Breeze uniquement ;
  le template d'avis Hyvä ne rend pas ce conteneur, ce qui est voulu : l'avis Hyvä passe par GraphQL, U-B3).
- Newsletter et partage de wishlist : pas de layout en A (étape B).
- Même template `widget.phtml` et même `ViewModel\Widget` qu'aujourd'hui (réécriture en JavaScript pur à l'étape B). Le
  template fonctionne déjà sans `$hyvaCsp`. Un `referenceContainer` vers un handle ou un conteneur absent est ignoré par
  Magento : aucune erreur quand le module du préréglage est absent (prouvé par P4, P6, P7).
- Ordre de fusion (R4, vérifié) : sous Luma et Breeze, le conteneur est déclaré par le module natif, chargé avant nous
  (`prearrangeModules`) ; sous Hyvä, il est déclaré par le thème, fusionné après nous, et la référence est quand même
  appliquée (preuve sur le contact). Le plan garde un test E2E « widget présent dans le formulaire » par préréglage et
  par thème (Hyvä, Luma), qui échoue si ce fait change.
- Limite connue de A, levée en B : si deux formulaires protégés sont sur la même page, `api.js` est inclus deux fois
  (sans effet fonctionnel, Cloudflare le gère).

### 3.4 Observer générique

**`Observer/FormPredispatchObserver`**, déclaré une seule fois sur `controller_action_predispatch` dans
`etc/frontend/events.xml` (remplace l'événement `controller_action_predispatch_contact_index_post`).

1. `request` et `controller_action` lus dans l'événement. Si la requête n'est pas `Magento\Framework\App\Request\Http` :
   sortie.
2. `$form = FormRegistry::findByAction(strtolower($request->getFullActionName()))` ; `null` → sortie (S12,
   `testRequestToUnprotectedRouteIsIgnored`). L'identité vient du routeur Magento, jamais d'un paramètre (S9) ; les URL
   réécrites aboutissent au même nom d'action (S11).
3. `FormGuard::guard($form, $request, $action)`.

### 3.5 FormGuard (refonte de la classe existante)

`guard(FormDefinitionInterface $form, Http $request, ActionInterface $action): bool`

1. `Config::isEnabledFor($form->getId(), $storeId)` faux → `true` (rien à faire). Comportement D1 inchangé.
2. **MethodPolicy (D3 = A, S25)** : si la méthode n'est pas POST : si `GET`/`HEAD` **et** `$action instanceof
   HttpGetActionInterface` → `true` (page d'affichage déclarée) ; sinon → échec `ERROR_USER` code `method-not-allowed`
   (sans appel à Cloudflare). Un POST est toujours validé, même si l'action déclare aussi GET.
3. **TokenReader (S12)** : `Http::getPostValue('cf-turnstile-response')`, sinon en-tête `X-Turnstile-Token`. Jamais la
   query string. Valeur non chaîne → vide.
4. `ValidatorInterface::validate($token, $remoteIp, $formId, $storeId)` (inchangé).
5. Échec → `FailureResponder::respond($form, $request, $result)` puis, **en mode redirection uniquement**,
   `$form->getPersister()?->persist($request)`. Retour `false`.

### 3.6 FailureResponder

- Toujours d'abord : `ActionFlag::set('', FLAG_NO_DISPATCH, true)` (S13 : le contrôleur n'est jamais exécuté, même si
  la réponse n'est pas HTTP).
- Choix du format : `json` si la définition l'impose ; `redirect` si elle l'impose ; en `auto` → JSON si
  `$request->isXmlHttpRequest()` ou si l'en-tête `Accept` contient `application/json`, sinon redirection.
- JSON : HTTP **400**, `Content-Type: application/json`, corps
  `{"success": false, "error": "turnstile", "code": "<missing|invalid|unavailable|config|method>", "message": "<message traduit>"}`
  (le champ `code` ne contient jamais les codes d'erreur bruts de Cloudflare).
- Redirection : message d'erreur existant (4 phrases déjà traduites + « method-not-allowed » réutilise « The security
  check failed. Please try again. »), puis `setRedirect()` vers la cible de la définition.
- Réponse qui n'est pas `HttpInterface` : seulement le flag (jamais d'échec ouvert).

### 3.7 Conservation de la saisie (D7 = 1)

- `Model/FormDataPersister/FormDataPersisterInterface::persist(Http $request): void`.
- `Model/FormDataFilter::filter(Http $request, string[] $excludedPaths): array` : corps POST uniquement ; retire
  toujours `cf-turnstile-response`, `form_key`, `password`, `password_confirmation`, `current_password` et les chemins
  exclus de la définition (notation `login/password` pour les champs imbriqués).
- `DataPersistorFormDataPersister` (arguments : `key`) — contact (K1). Classe du framework, typée.
- `SessionFormDataPersister` (arguments **chaînes** : `requiredModule`, `sessionType`, `setter`, `valueField` optionnel,
  `excludedFields`) :
  1. module requis désactivé → sortie silencieuse (K8) ;
  2. contrôle de configuration : `sessionType` doit exister (classe ou virtualType connu de l'ObjectManager) et être un
     `Magento\Framework\Session\SessionManagerInterface` ; `setter` doit respecter `^set[A-Z][A-Za-z0-9]*$`. Sinon :
     exception en mode développeur, log `error` (sans données du formulaire) en production, le rejet Turnstile est
     maintenu (K10) ;
  3. valeur = tableau filtré, ou seulement `valueField` (ex. `login/username`) ;
  4. `ObjectManagerInterface::get($sessionType)` → **l'instance partagée**, la même que celle reçue par le contrôleur
     natif ; appel du setter.
  L'ObjectManager n'est utilisé que dans cette classe (rôle de fabrique). Aucune classe de module optionnel n'apparaît
  dans une signature PHP : `setup:di:compile` ne dépend pas de ces modules.

### 3.8 Configuration

- `Config::getFailureMode(?int $storeId, ?string $formId = null)` : `forms/<id>_failure_mode` si `closed` ou `open`,
  sinon mode général (S3). Nouveau source model `FailureModeOverride` (Utiliser le réglage général / Fermé / Ouvert).
- `Config::isEnabledFor()` : inchangé, lit `forms/<id>` ; valeur absente = désactivé.
- `Config::isTwinBlocked(string $twinId, ?int $storeId)` : lit `api_twins/<id>`, défaut 0 (S26).
- `config.xml` : défauts des 8 préréglages (tableau §3.2), des `_failure_mode` (vide = général) et des 11 interrupteurs
  de jumelles (0). Un module tiers ajoute le défaut de son formulaire dans son propre `config.xml` (README).

### 3.9 Champs générés dans l'admin (D6 = 1 + a)

- `system.xml` garde la section, le groupe `general` et deux groupes **vides** : `forms` (« Protected Forms ») et
  `api_twins` (« API Twins — anonymous calls »). Le champ `contact` écrit à la main est supprimé.
- **`Plugin/Config/GeneratedFieldsPlugin`** : plugin `afterConvert` sur
  `Magento\Config\Model\Config\Structure\Converter`, déclaré dans `etc/adminhtml/di.xml`. Il ajoute sous
  `alpinecommerce_turnstile/forms/children`, pour chaque formulaire de `FormRegistry::getAll()` :
  `<id>` (Oui/Non, `Magento\Config\Model\Config\Source\Yesno`) et `<id>_failure_mode` (`FailureModeOverride`),
  `showInDefault/Website/Store = 1`, libellé traduit. Même principe pour `api_twins` à partir de `TwinRegistry`, avec un
  commentaire par interrupteur (S31 pour `createProductReview`).
- Résultat mis en cache avec la structure (type de cache `config`) : activer ou désactiver un module passe par
  `setup:upgrade`, qui vide ce cache.
- **Point à vérifier en premier dans le plan** : `bin/magento config:set` accepte les chemins générés (la commande émule
  la zone adminhtml, qui charge `etc/adminhtml/di.xml`). Si ce n'est pas le cas, le plugin passe dans `etc/di.xml`.
- Chemin `forms/contact` inchangé : aucune migration (critère §1.5).
- **Avertissement reCAPTCHA (U17)** : chaque définition peut porter le chemin de config reCAPTCHA de Magento
  équivalent (ex. `recaptcha_frontend/type_for/contact`). Un message système admin
  (`Magento\Framework\Notification\MessageInterface`) liste les formulaires activés à la fois dans Turnstile et dans
  reCAPTCHA sur un même store view.

### 3.10 Jumelles API (D4 = B)

- **`Model/Twin/TwinDefinition`** (virtualTypes dans `etc/di.xml`) : `id`, `label`, `comment`, `requiredModule`, et soit
  `serviceClass` + `serviceMethod` (REST sync/async/bulk et SOAP), soit `mutation` (GraphQL). **`TwinRegistry`** comme
  `FormRegistry`.
- 11 interrupteurs : `customer_create_account`, `customer_password_reset`, `customer_token` (REST + SOAP) ;
  `gql_create_customer`, `gql_create_customer_v2`, `gql_generate_customer_token`, `gql_request_password_reset_email`,
  `gql_subscribe_newsletter`, `gql_contact_us`, `gql_create_product_review` (commentaire S31), `gql_send_email_to_friend`.
- **Appel anonyme** = `Magento\Authorization\Model\UserContextInterface::getUserType()` vaut `null` ou
  `USER_TYPE_GUEST` (REST, SOAP) ; en GraphQL, `$context->getUserType()` identique (S30).
- **Règle de constructeur (R1)** : les trois guards ne type-hintent dans leur constructeur que des classes du framework,
  de `Magento_Store`, de `Magento_Config` ou du module. Les collaborateurs d'un module optionnel (`Rest\Router`,
  `Soap\Config`, `UserContextInterface`) sont des paramètres **non typés** (type documenté en PHPDoc) injectés par le
  `di.xml` de la zone concernée (`<argument xsi:type="object">`). Si le paquet est supprimé, la zone n'existe plus, ce
  `di.xml` n'est pas lu, et la réflexion du constructeur ne rencontre aucune classe absente. Les types de paramètres de
  **méthodes** (`beforeValidate(RequestValidator $subject)`) ne sont pas résolus par la compilation.
- **REST (`etc/webapi_rest/di.xml`)** : `RestTwinGuard::beforeValidate` sur `RequestValidator` ; route via
  `Rest\Router::match($request)` → service/méthode ; interrupteur actif et appel anonyme →
  `Magento\Framework\Webapi\Exception` code HTTP **403** (S27). La jumelle est identifiée par le **service**, pas par
  l'URL : le même code couvre async et bulk si S27c se confirme.
- **SOAP (`etc/webapi_soap/di.xml`)** : `SoapTwinGuard::before__call` sur `Soap\Request\Handler` ; opération → service
  via `Magento\Webapi\Model\Soap\Config::getServiceMethodInfo()` ; même exception → SOAP Fault (S27b).
- **GraphQL (`etc/graphql/di.xml`)** : `GraphQlTwinGuard::beforeResolve` sur
  `Magento\Framework\GraphQl\Query\ResolverInterface` ; sortie immédiate si le parent n'est pas `Mutation` ; sinon
  `GraphQlAuthorizationException` (S28).
- Log `warning` « Turnstile API twin blocked » avec `twin_id`, `channel`, `store_id` ; jamais le contenu de la requête.

### 3.11 Dépendances (DoD §2.1)

- `composer.json` → `require` : `php`, `magento/framework`, `magento/module-store`, `magento/module-config`.
  `suggest` : Hyvä, Contact, Customer, Newsletter, Review, SendFriend, Wishlist, Catalog, Csp, Webapi, WebapiAsync,
  GraphQl, Authorization, Integration.
- `module.xml` → `<sequence>` : `Magento_Store` et `Magento_Config` uniquement (DoD §2.1 : ni `Hyva_Theme`, ni
  `Magento_Contact`, ni module métier). L'ordre de fusion des layouts reste correct sans ces entrées (R4 vérifié, §6).
- Aucune classe d'un module optionnel dans un **constructeur** de classe de production (règle R1, §3.10). Test unitaire
  statique `ConstructorDependencyTest` : il parcourt par réflexion toutes les classes du module hors `Test/` et échoue si
  un paramètre de constructeur a un type hors de la liste autorisée (`Magento\Framework\`, `Magento\Store\`,
  `Magento\Config\`, `AlpineCommerce\Turnstile\`, `Psr\`, types natifs). Proposé à la DoD (§6, R1).

## 4. Environnement de test

- **Unitaires** : commande existante (DoD §0), CI existante.
- **Intégration** : le framework est présent (`dev/tests/integration/phpunit.xml.dist`) mais **pas configuré**
  (`install-config-mysql.php` absent) et **pas exécuté en CI**. Préalable du plan, **accord de l'utilisateur donné le
  2026-09-25, à condition que rien ne soit jamais commité** : créer une base dédiée `magento_integration_tests` dans le conteneur MySQL et un préfixe d'index
  Elasticsearch dédié ; écrire `dev/tests/integration/etc/install-config-mysql.php` (contient un mot de passe : jamais
  commité, ajout au `.gitignore` vérifié). Les tests d'intégration du module vont dans
  `app/code/AlpineCommerce/Turnstile/Test/Integration`.
- **E2E** : store view `french`, mode développeur, clés de test Cloudflare (DoD §1.1) ; chaque commande qui modifie la
  config ou l'environnement est annoncée avant exécution.
- **Premier test du plan : S27c** (async/bulk) exécuté réellement ; en cas d'échec, arrêt et signalement (DoD).

## 5. Correspondance DoD → tests de l'étape A

| Lignes DoD | Tests |
|---|---|
| S1, S2, S5–S8 (couvertes) | `ValidatorTest`, `SiteVerifyClientTest`, `ConfigTest` existants, gardés verts |
| S3 | `ConfigTest::testFormFailureModeOverridesGeneral`, `testInheritFallsBackToGeneral` ; `ValidatorTest` : l'id du formulaire est transmis |
| S9, S10, S12, S25 | `FormPredispatchObserverTest` / `FormGuardTest` (noms exacts de la DoD) |
| S11, S14, S15, S15b | `Test/Integration/FormProtectionTest` |
| S13 | `FormGuardTest::testNonHttpResponseStillBlocksDispatch` |
| S16, S17 | `FormDataFilterTest`, `SessionFormDataPersisterTest`, test de log de l'observer |
| S26–S31, S27b, S27c | `ConfigTest` (défauts, scope) ; `Test/Integration/ApiTwinBlockingTest` (REST, SOAP, async/bulk, GraphQL) ; E2E Hyvä pour S31 |
| K1–K10 | `Test/Integration/FormDataPersistenceTest`, `SessionFormDataPersisterTest` |
| §1.5 champs générés, `forms/contact`, préréglage masqué | `GeneratedFieldsPluginTest` + `config:set` / `config:show` |
| §2.1 dépendances | lecture de `composer.json` / `module.xml` + `grep` de la DoD |
| U17 | `RecaptchaConflictMessageTest` |
| Remplacements | `ContactFormObserverTest` → `FormPredispatchObserverTest` (mêmes cas repris : pas de contrôleur, requête acceptée, saisie gardée sans token) ; `FormGuardTest` adapté à la nouvelle signature, ses 8 cas conservés |

## 6. Risques et points ouverts

- **R1 — Paquet supprimé par Composer (`replace`) — vérifié en lecture, risque confirmé.** Un constructeur qui
  type-hinte une classe d'un paquet supprimé fait échouer `setup:di:compile` (faits §1). La conception initiale
  (`Rest\Router`, `Soap\Config`, `UserContextInterface` typés dans les constructeurs) aurait cassé la compilation d'un
  projet sans `magento/module-webapi`. Les déclarations de plugins, elles, sont sans risque : elles sont dans le `di.xml`
  de zones qui disparaissent avec le paquet. **Correctif intégré** : règle de constructeur (§3.10) + test statique
  (§3.11). **Proposé à la DoD, à valider** : le test `ConstructorDependencyTest` (§2.1) et deux lignes de matrice :
  P17 = Luma avec tous les paquets `magento/module-*-graph-ql` et `magento/module-graph-ql` remplacés par `replace`
  (cas courant des projets Luma) ; P18 = Luma avec `magento/module-webapi-async` remplacé. Non vérifié en exécution :
  ce sera fait par P17/P18.
- **R2 — K10 : intégré** dans la DoD le 2026-09-25 (limite : faute d'orthographe dans un setter détectée seulement par K1–K7).
- **R3 — Défauts des préréglages : validés** le 2026-09-25 (§3.2).
- **R4 — Ordre de fusion des layouts sans `<sequence>` : vérifié, pas de correctif nécessaire.** Luma/Breeze : le
  conteneur est déclaré par un module `Magento_*`, toujours chargé avant `AlpineCommerce_Turnstile` (`prearrangeModules`).
  Hyvä : le conteneur est déclaré par le thème, fusionné après tous les modules, et la référence est appliquée quand même
  (preuve réelle sur le contact, §1). Garde-fou : test E2E « widget dans le formulaire » par préréglage et par thème (§3.3).
  Limite : `prearrangeModules` est vérifié en 2.4.8 ; sa présence dans la version minimale supportée sera vérifiée avec
  D5 (ligne P11).
