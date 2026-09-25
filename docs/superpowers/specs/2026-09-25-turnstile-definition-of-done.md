# AlpineCommerce_Turnstile — Definition of Done (produit réutilisable)

- **Date :** 2026-09-25
- **Statut :** validé par l'utilisateur le 2026-09-25, puis mis à jour avec D3 = A, D4 = B (REST, SOAP, GraphQL), la correction S14/S15 (AJAX), la reformulation de S12, S27c (async / bulk, à confirmer par test réel), D6 = 1 + a, D7 = 1, K10 reformulé et les ajouts R1 (ConstructorDependencyTest, P17, P18) — aucune implémentation n'est lancée
- **Module :** `src/app/code/AlpineCommerce/Turnstile`
- **Sources :** code actuel du module (60 tests unitaires), `docs/superpowers/specs/2026-09-24-turnstile-register-design.md`,
  analyse cible (FormRegistry + Custom Forms + observer générique, sous-projets A / B / C)

## 0. Règles de ce document

- Une case n'est cochée **que sur preuve** : nom d'un test vert en CI, sortie de commande jointe, ou capture du
  scénario. Une affirmation (« ça marche », « c'est prévu ») ne coche rien.
- Statuts utilisés :
  - **Couvert** — déjà vrai aujourd'hui, avec la preuve existante citée.
  - **A / B / C** — à livrer dans le sous-projet A (serveur + registre + Protected Forms), B (JS + injection + `getToken` + CMS)
    ou C (Custom Forms + finitions + packaging).
  - **Hors périmètre** — volontairement non traité, documenté dans le README du module.
  - **Décision** — point d'architecture qui doit être tranché par l'utilisateur avant de figer la spec (voir §5).
- Commandes Magento toujours en `www-data` : `docker exec -u www-data magento2-php bin/magento …`.
- Tests unitaires :
  `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist --do-not-cache-result --no-extensions --testdox app/code/AlpineCommerce/Turnstile/Test/Unit`

---

## 1. ADMIN-CONFIGURABLE — protéger un formulaire sans déployer de code

**Objectif :** un administrateur protège un formulaire standard ou tiers uniquement depuis
`Stores > Configuration > AlpineCommerce > Cloudflare Turnstile`, puis vide le cache. Aucun fichier modifié,
aucun `setup:upgrade`, `setup:di:compile` ni `setup:static-content:deploy`.

### 1.1 Formulaire de test choisi

Le formulaire **RMA** du projet (`AlpineCommerce_Rma`, `rma/view/frontend/templates/request.phtml`,
`<form id="rma-request-form" method="post" action="…/rma/index/request">`, action serveur `rma_index_request`).
Il joue le rôle du « formulaire tiers » : il n'est déclaré dans aucun `di.xml` de Turnstile et le module RMA
ne connaît pas Turnstile.

Clés de test officielles Cloudflare (mode `developer` ou `default` uniquement, le mode `production` les refuse
par conception) :

| Usage | Site key | Secret key |
|---|---|---|
| Toujours valide | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` |
| Toujours refusé | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` |
| Token déjà utilisé | — | `3x0000000000000000000000000000000AA` |

### 1.2 Préconditions (preuves à joindre)

- [ ] `git status` sur `src/app/code` et `src/app/design` : aucune modification pendant tout le scénario (sortie jointe avant et après).
- [ ] Le hash de `generated/metadata` et de `pub/static/deployed_version.txt` est identique avant et après le scénario.
- [ ] Turnstile activé sur le store view de test, clés « toujours valide ».

### 1.3 Scénario de bout en bout (formulaire HTML classique)

| # | Action (admin ou navigateur) | Résultat attendu, vérifiable |
|---|---|---|
| E1 | Admin > Custom Forms : ajouter une ligne `id = rma_request`, sélecteur `#rma-request-form`, action `rma_index_request`, échec = « page précédente », mode de panne = hériter. Enregistrer. | Message « You saved the configuration. » ; la valeur est visible avec `bin/magento config:show alpinecommerce_turnstile` |
| E2 | Saisir un identifiant invalide (`../x`, `<b>`, vide, doublon) ou une action invalide. | Enregistrement refusé avec message d'erreur admin, config inchangée (`config:show` identique) |
| E3 | `bin/magento cache:clean config full_page` (seule commande autorisée). | Code de sortie 0 |
| E4 | Client connecté : ouvrir la page RMA. | Un seul widget Turnstile dans `#rma-request-form` ; `data-action="rma_request"` ; `api.js` chargé une fois (onglet Réseau) |
| E5 | Ouvrir une page sans formulaire protégé (fiche CMS simple). | `challenges.cloudflare.com/turnstile/v0/api.js` **n'est pas** chargé |
| E6 | Soumettre le RMA avec le widget validé. | Le RMA est créé (ligne en base, requête SQL jointe) ; `var/log/turnstile.log` ne contient pas de ligne d'erreur pour cette requête |
| E7 | Soumettre en supprimant le champ `cf-turnstile-response` (DevTools) ou via `curl` avec `form_key` valide. | Redirection vers la page précédente, message « Please complete the security check. », **aucun** RMA créé (comptage SQL avant/après) |
| E8 | Remplacer la secret key par `2x…AA`, vider le cache, soumettre. | Rejet, message d'échec, aucun RMA créé |
| E9 | Rejouer via `curl` un token déjà accepté en E6. | Rejet (`timeout-or-duplicate`), aucun RMA créé |
| E10 | Rejouer un token valide généré pour le formulaire **contact** vers `rma/index/request`. | Rejet `action-mismatch`, aucun RMA créé |
| E11 | Cloudflare injoignable (timeout forcé ; commande à faire approuver car elle modifie l'environnement), mode de panne du formulaire = **fermé**. | Rejet avec message « temporarily unavailable », ligne `error` dans `turnstile.log`, aucun RMA créé |
| E12 | Même situation, mode de panne du formulaire = **ouvert** (le mode global reste fermé). | Le RMA est créé ; ligne `warning` « request accepted (failure mode open) » avec `form_id = rma_request` |
| E13 | Désactiver la ligne `rma_request` (sans la supprimer), vider le cache. | Plus de widget ; la soumission sans token crée le RMA |
| E14 | Même ligne active sur le store view A seulement. | Widget et validation sur A ; aucun widget ni validation sur le store view B |
| E15 | `curl -G` (GET) vers `rma/index/request` avec les mêmes paramètres qu'en E6, sans token (le contrôleur RMA ne déclare aucune interface de méthode HTTP). | Requête rejetée par Turnstile (décision D3 = A, garantie S25), **aucun** RMA créé (comptage SQL avant/après) |

### 1.4 Variante AJAX / JSON (même principe, sans code)

Le formulaire est l'endpoint JSON existant `productreviews/index/submit` (`AlpineCommerce_ProductReviews`, retourne du JSON).

- [ ] E16 — Custom Form `product_review_ajax` sur l'action `productreviews_index_submit`, réponse d'échec « auto ».
- [ ] E17 — `fetch` sans token avec l'en-tête `X-Requested-With: XMLHttpRequest` : **HTTP 400**, corps JSON `{"success":false,"error":"turnstile", "message": …}` (format exact figé dans la spec A), aucun avis enregistré.
- [ ] E18 — Même requête avec `Accept: application/json` seul : même réponse 400 JSON.
- [ ] E19 — `fetch` avec l'en-tête `X-Turnstile-Token: <window.AlpineTurnstile.getToken('product_review_ajax')>` : réponse normale du contrôleur.
- [ ] E20 — Token placé dans la query string (`?cf-turnstile-response=…`) : **ignoré**, rejet 400.

### 1.5 Critères de validation de l'objectif 1

- [ ] Scénario E1–E15 exécuté et preuves jointes (captures, sorties SQL, extraits de log sans token).
- [ ] Scénario E16–E20 exécuté et preuves jointes.
- [ ] Les 8 formulaires préréglés apparaissent dans « Protected Forms » **sans** champ écrit à la main dans `system.xml`
      (preuve : `grep` sur `system.xml` ne trouve aucun identifiant de formulaire).
- [ ] « Protected Forms » est rendu par des **champs générés** à partir du registre (D6 = 1) : deux champs par formulaire,
      `alpinecommerce_turnstile/forms/<id>` et `alpinecommerce_turnstile/forms/<id>_failure_mode`, avec l'héritage de scope
      standard (case « Use Website » / « Use Default ») et modifiables par `bin/magento config:set` (sortie jointe).
- [ ] Le chemin existant `alpinecommerce_turnstile/forms/contact` est conservé : une valeur enregistrée avant la refonte
      est relue telle quelle, sans migration (test unitaire + `config:show` avant/après).
- [ ] Un préréglage dont le module Magento requis est désactivé n'apparaît pas dans l'admin et n'est jamais appliqué
      (D6 = a ; test unitaire du plugin de structure + ligne P7).
- [ ] Un module qui déclare son formulaire dans son propre `di.xml` le voit apparaître dans « Protected Forms » après
      `setup:di:compile` (test d'intégration du registre avec un formulaire fictif).
- [ ] Les options du widget (thème, taille, apparence, langue) changées dans l'admin se voient sur le widget après
      `cache:clean` (attributs `data-*` ou paramètres de `turnstile.render()` inspectés).
- [ ] Le bouton « Test Connection » affiche un message différent pour : secret valide, `invalid-input-secret`,
      Cloudflare injoignable ; la réponse HTTP du bouton ne contient jamais le secret (inspection réseau).
- [ ] Le widget CMS `{{widget type="…" form_id="…"}}` inséré dans un bloc CMS **et** dans Page Builder s'affiche sur
      Luma et sur Hyvä.

---

## 2. PORTABLE — installation sans erreur sur tout projet Magento

### 2.1 Critères de dépendance (vérifiables par lecture de fichiers)

- [ ] `composer.json` → `require` contient uniquement `php`, `magento/framework`, `magento/module-store`,
      `magento/module-config`, et un module de plus seulement si une classe réellement utilisée l'exige (justifié ligne par ligne dans la spec).
- [ ] Hyvä, Contact, Customer, Newsletter, Review, SendFriend, Wishlist, Csp, Widget, PageBuilder, Webapi, GraphQl et les modules `*GraphQl` des jumelles (D4 = B) figurent uniquement en `suggest`.
- [ ] `etc/module.xml` → `<sequence>` sans `Hyva_Theme`, sans `Magento_Contact`, sans aucun module métier.
- [ ] `grep -rn "Hyva\\\\\|Magento\\\\Contact\\\\\|Magento\\\\Customer\\\\" --include=*.php` sur le module : aucun
      `use` ni aucune signature de constructeur ne référence une classe d'un module optionnel (sinon `di:compile` échoue quand il est absent).
- [ ] Aucun `virtualType` ni argument DI `xsi:type="object"` du `di.xml` **global** ou **frontend** ne pointe vers une classe
      d'un module optionnel (les `di.xml` des zones `webapi_rest`, `webapi_soap` et `graphql` ne sont lus que si la zone existe).
- [ ] `ConstructorDependencyTest` (unitaire, statique, **premier test écrit de l'étape A**) : par réflexion sur toutes les
      classes de production du module (hors `Test/`), aucun paramètre de constructeur n'a un type hors de la liste autorisée
      (`Magento\Framework\`, `Magento\Store\`, `Magento\Config\`, `AlpineCommerce\Turnstile\`, `Psr\`, types natifs).
      Raison : un constructeur qui type-hinte une classe d'un paquet supprimé par Composer fait échouer `setup:di:compile`
      (`GetParameterClassTrait`, `ClassReader::getConstructor`).
- [ ] Le module est installable depuis un dépôt Composer séparé (`path` ou VCS) et **pas seulement** depuis `app/code`.

### 2.2 Matrice CI (nouveau job « Install matrix »)

Le CI actuel (`.github/workflows/ci.yml`) exécute lint, validation XML, `composer validate`, PHPUnit, PHPStan et PHPCS.
Il n'installe **pas** Magento. Il faut un nouveau job avec MySQL et OpenSearch en services.

Commandes exécutées pour **chaque** ligne ; chacune doit sortir avec le code 0 :

```
composer require alpinecommerce/module-turnstile:@dev        # dépôt path vers le paquet
bin/magento setup:install … [--disable-modules=<liste de la ligne>]
bin/magento module:enable AlpineCommerce_Turnstile
bin/magento setup:upgrade
bin/magento deploy:mode:set production --skip-compilation
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f en_US fr_FR
bin/magento module:status AlpineCommerce_Turnstile             # "enabled"
curl -fsS -o /dev/null <base_url>/                            # HTTP 200
curl -fsS -o /dev/null <base_url>/<chaque page de formulaire préréglé encore installé>
test ! -s var/log/exception.log
bin/magento module:disable AlpineCommerce_Turnstile && bin/magento setup:upgrade   # retrait propre
```

| # | Thème | Modules désactivés | Ce que la ligne prouve |
|---|---|---|---|
| P1 | Luma | aucun | Installation de référence sans Hyvä |
| P2 | Hyvä (default theme) | aucun | Compatibilité Hyvä |
| P3 | Breeze (blank) | aucun | Compatibilité Breeze |
| P4 | Luma | `Magento_Contact` | Aucune dépendance à Contact |
| P5 | Hyvä | `Magento_Contact` | Idem, sous Hyvä |
| P6 | Luma | `Magento_Customer` et tous les modules qui en dépendent | Aucune dépendance à Customer (voir note) |
| P7 | Luma | `Magento_Newsletter`, `Magento_Review`, `Magento_SendFriend`, `Magento_Wishlist` | Les préréglages de modules absents ne cassent rien |
| P8 | Luma | `Magento_Csp` | `csp_whitelist.xml` sans effet de bord |
| P9 | Luma | `Magento_PageBuilder`, `Magento_Widget` et leurs dépendants | Le widget CMS est optionnel |
| P10 | Luma | `Hyva_*` absents du `composer.json` du projet | Aucun code Hyvä requis |
| P11 | Luma | aucun, version Magento **minimale** supportée | Borne basse de compatibilité (versions : Décision D5) |
| P12 | Hyvä | aucun, version Magento **maximale** supportée (2.4.8 aujourd'hui) | Borne haute |
| P13 | Luma | `Magento_GraphQl` et tous ses dépendants (`Magento_Webapi` actif : REST + SOAP) | Le blocage REST / SOAP fonctionne sans GraphQL ; S27, S27b, S29, S30 exécutés sur cette ligne |
| P14 | Luma | `Magento_Webapi` et tous ses dépendants (REST + SOAP absents, `Magento_GraphQl` actif) | Le blocage GraphQL fonctionne sans REST ni SOAP ; S28 exécuté sur cette ligne |
| P15 | Luma | `Magento_GraphQl`, `Magento_Webapi` et tous leurs dépendants | Le blocage des jumelles (D4 = B) n'impose ni REST, ni SOAP, ni GraphQL |
| P16 | Luma | `Magento_WebapiAsync` (REST synchrone et SOAP actifs) | Le blocage des jumelles ne dépend pas de `Magento_WebapiAsync` ; S27, S27b exécutés sur cette ligne |
| P17 | Luma | **Paquets retirés par `replace`** (pas seulement désactivés) : `magento/module-graph-ql` et tous les `magento/module-*-graph-ql` | Aucune classe du module ne dépend d'un paquet GraphQL supprimé ; `setup:di:compile` réussit |
| P18 | Luma | **Paquet retiré par `replace`** : `magento/module-webapi-async` | Idem pour le REST asynchrone ; S27 et S27b exécutés sur cette ligne |

Note P6 : Magento ne permet pas de désactiver `Magento_Customer` seul (Checkout, Sales, etc. en dépendent).
La ligne est valide si l'installation réussit avec la liste complète calculée par
`bin/magento module:disable Magento_Customer` (liste des dépendants). Si Magento lui-même refuse cette installation,
la ligne est marquée « non applicable » avec la sortie d'erreur de Magento comme preuve. Le module Turnstile ne doit
jamais être la cause de l'échec.

### 2.3 Critères de validation de l'objectif 2

- [ ] Les lignes P1 à P18 sont vertes en CI (lien vers l'exécution), ou « non applicable » avec la preuve décrite ci-dessus.
      **P17 et P18 doivent être vertes en CI avant que l'objectif 2 (portabilité) soit coché**, sans exception « non applicable ».
- [ ] Sur P4, P6 et P7, la page admin Turnstile s'affiche (HTTP 200) et les préréglages dont le module Magento requis est
      absent **n'apparaissent pas** (D6 = a).
- [ ] Sur P1, P2 et P3, le scénario E4 et E6–E7 passe sur le formulaire de contact (widget + validation).
- [ ] PHPStan niveau 5 et PHPCS Magento2 sans erreur sur le module (jobs existants).
- [ ] `composer validate` passe sur le `composer.json` du paquet autonome.

---

## 3. TOUS LES USE CASES — types de formulaires dans un projet Magento

| # | Type de formulaire | Exemple réel | Statut | Comment c'est prouvé |
|---|---|---|---|---|
| U1 | POST HTML classique avec redirection, conteneur `form.additional.info` | Contact, création de compte, connexion, mot de passe oublié, envoyer à un ami | **Couvert** pour contact ; **A** pour les 4 autres | Test d'intégration par préréglage + E2E sur le formulaire |
| U2 | POST HTML classique **sans** conteneur de layout | Newsletter (pied de page), partage de wishlist | **B** (injection par sélecteur) ; en A, protection serveur prête mais désactivée par défaut | E2E Luma + Hyvä + Breeze |
| U3 | POST HTML d'un module du projet ou d'un module tiers | RMA (`rma_index_request`), Blog, ProductQuestions, CustomerCare | **A** (déclaration par `di.xml` du module) **ou C** (Custom Forms, sans code) | Scénario §1.3 |
| U4 | Formulaire soumis en AJAX / `fetch` en `x-www-form-urlencoded` ou `multipart` | `productreviews/index/submit` (JSON) | **A** (réponse 400 JSON) + **B** (`getToken`) | Scénario §1.4 |
| U5 | AJAX avec corps **JSON** (`Content-Type: application/json`) | Connexion AJAX Luma `customer/ajax/login` | **A** via l'en-tête `X-Turnstile-Token` uniquement ; le token dans le JSON n'est pas lu | Test unitaire « token lu depuis l'en-tête quand le corps est JSON » |
| U6 | Formulaire avec fichiers (`multipart/form-data`) | Pièces jointes RMA / CustomerCare | **A** (token dans le corps POST) | Test d'intégration multipart |
| U7 | Plusieurs formulaires protégés sur la même page | Newsletter du pied de page + contact | **B** (un widget et une `action` par formulaire) | E2E : deux tokens, chacun accepté seulement par son action |
| U8 | Formulaire affiché après le chargement (modal, onglet, contenu chargé en AJAX, navigation de type SPA de Breeze) | Modal Hyvä, popup Luma, Breeze turbo | **B** (nouvelle détection après le chargement) | E2E : ouvrir le modal puis vérifier le widget |
| U9 | Formulaire placé dans un bloc CMS ou Page Builder (HTML libre vers une route Magento) | Formulaire de demande de devis en bloc CMS | **B** (widget CMS) + **C** (route en Custom Forms) | E2E Page Builder |
| U10 | Formulaire en **GraphQL** | Avis produit sous Hyvä (`fetch …/graphql`), PWA / headless | **Hors périmètre** (GraphQL exclu) | README : liste des mutations non protégées |
| U11 | **API REST / SOAP / GraphQL « jumelles »** des formulaires protégés | REST anonymes : `POST /V1/customers`, `PUT /V1/customers/password`, `POST /V1/integration/customer/token`. SOAP anonymes (mêmes services) : `customerAccountManagementV1CreateAccount`, `customerAccountManagementV1InitiatePasswordReset`, `integrationCustomerTokenServiceV1CreateCustomerAccessToken`. GraphQL : `createCustomer`, `createCustomerV2`, `generateCustomerToken`, `requestPasswordResetEmail`, `subscribeEmailToNewsletter`, `contactUs`, `createProductReview`, `sendEmailToFriend` | **A** : blocage par interrupteur admin (D4 = B). Protection **par Turnstile** de ces API : **hors périmètre** (sous-projet D futur) | S26–S31 ; voir U-B2 |
| U12 | Formulaire qui envoie vers un **domaine externe** | HubSpot, Mailchimp, Typeform intégrés | **Hors périmètre** : Magento ne voit jamais la requête, donc il ne peut pas valider | README : à protéger côté fournisseur |
| U13 | Formulaire en iframe tierce | Widget de chat, formulaire hébergé | **Hors périmètre** (même raison que U12) | README |
| U14 | Formulaires GET vers une route non protégée | Recherche, filtres de catalogue | **Hors périmètre volontaire** : ces routes ne sont pas enregistrées, la requête est ignorée (un GET vers une route protégée suit S25) | `…ObserverTest::testRequestToUnprotectedRouteIsIgnored` |
| U15 | Formulaires admin (connexion backend, mot de passe oublié admin) | `/admin` | **Hors périmètre** (non demandé) | README |
| U16 | Checkout / passage de commande | Place order, connexion dans le checkout | **Hors périmètre, reporté** par décision utilisateur | README |
| U17 | Même formulaire déjà protégé par Google reCAPTCHA (Magento_ReCaptcha*) | Contact avec reCAPTCHA activé | **A** : avertissement dans l'admin ; double protection non supportée | Test de l'avertissement |

### 3.1 Angles morts signalés explicitement

- **U-B1 — Contrôleurs qui acceptent aussi GET.** La protection vise uniquement les POST. Or les contrôleurs
  `AlpineCommerce\Rma\Controller\Index\Request` et `AlpineCommerce\ProductReviews\Controller\Index\Submit` étendent `Action`
  **sans** `HttpPostActionInterface` : ils exécutent la même action en GET, et le contrôle CSRF de Magento ne s'applique
  pas aux GET. Un robot peut donc contourner Turnstile en envoyant les mêmes paramètres en GET.
  → **Décision D3 = A** : sur une route protégée, toute méthode autre que POST est rejetée, sauf si le contrôleur
  déclare explicitement `HttpGetActionInterface`. Garantie S25. Limite restante, à écrire dans le README : un contrôleur
  qui déclare `HttpGetActionInterface` **et** effectue une action en GET n'est pas protégé en GET (choix du développeur
  de ce contrôleur).
- **U-B2 — API REST / SOAP / GraphQL jumelles.** Protéger `customer_account_createpost` ne protège pas la création de compte :
  `POST /rest/V1/customers` (anonyme), l'opération SOAP `customerAccountManagementV1CreateAccount` et la mutation
  `createCustomer` font la même chose sans passer par le contrôleur.
  Il en va de même pour la newsletter, le contact (GraphQL 2.4.7+), le mot de passe oublié et la connexion.
  → **Décision D4 = B** : un interrupteur admin par endpoint jumeau, désactivé par défaut, réglable par store view
  (S26–S31). Limites restantes, à écrire dans le README et dans l'admin : (1) tant que l'interrupteur est désactivé
  (valeur par défaut), la jumelle (REST, SOAP ou GraphQL) contourne Turnstile ; (2) l'activer casse les clients headless / PWA / applications
  mobiles qui utilisent cet endpoint en anonyme ; (3) la protection **par Turnstile** de ces API n'existe pas (sous-projet D futur).
- **U-B3 — Avis produit sous Hyvä.** Le préréglage « product review » protège `review_product_post` (Luma, Breeze)
  mais pas le formulaire Hyvä, qui passe par la mutation GraphQL `createProductReview`. L'admin doit l'indiquer à côté
  du préréglage. Bloquer la jumelle `createProductReview` (D4 = B) **casse** le formulaire d'avis Hyvä : exception
  documentée dans l'admin et le README (S31).
- **U-B4 — AJAX avec corps JSON.** Le token n'est accepté que dans l'en-tête ; un développeur qui le met dans le JSON
  sera rejeté. C'est le comportement voulu, mais il doit être documenté avec un exemple `fetch` dans le README.

### 3.2 Critères de validation de l'objectif 3

- [ ] Chaque ligne U1–U17 a son statut final validé par l'utilisateur.
- [ ] Chaque ligne « A / B / C » a sa preuve (test ou E2E) listée dans le plan d'implémentation du sous-projet.
- [ ] Chaque ligne « Hors périmètre » et chaque angle mort U-B1 à U-B4 figure dans le README du module, section
      « Limites connues », avec la solution conseillée.
- [ ] Les décisions D3 et D4 sont tranchées et inscrites dans la spec A avant l'implémentation.

---

## 4. SÉCURITÉ ROBUSTE — garanties et test qui les prouve

Légende du type de test : **U** = unitaire (PHPUnit, existant ou à écrire), **I** = intégration (Magento), **E** = E2E manuel avec preuve.

| # | Garantie | Test qui la vérifie | Type | Statut |
|---|---|---|---|---|
| S1 | Par défaut, le mode de panne est **fermé** | `ConfigTest::testFailureModeDefaultsToClosed` | U | **Couvert** |
| S2 | Le mode ouvert n'accepte **que** l'indisponibilité de Cloudflare, jamais une erreur de configuration | `ValidatorTest::testInvalidSecretIsRejectedEvenInOpenMode`, `testTransportFailureOpenIsAcceptedWithWarning`, `testInternalErrorClosedIsUnavailable` | U | **Couvert** |
| S3 | Le mode par formulaire remplace le mode global, et « hériter » reprend le mode global | `ConfigTest::testFormFailureModeOverridesGeneral`, `testInheritFallsBackToGeneral` (à écrire) | U | **A** |
| S4 | Clés manquantes alors que Turnstile est activé : comportement explicite et visible | `ConfigTest::testMissingSecretDisablesAndLogsWarning` (aujourd'hui : la vérification est **ignorée**) | U | **Décision D1** |
| S5 | Un token déjà utilisé est refusé | `ValidatorTest::testDuplicateTokenIsUserError` + E9 | U + E | **Couvert** (U) / **A** (E) |
| S6 | Un token généré pour un autre formulaire est refusé (`action` ≠ identifiant du formulaire) | `ValidatorTest::testActionMismatchIsRejected`, `testSuccessWithoutActionIsRejected`, `testTestingKeyResponseWithOtherActionIsRejected` + E10 | U + E | **Couvert** (U) |
| S7 | Les clés de test sont refusées en mode production | `ValidatorTest::testTestingKeyResponseIsRejectedInProduction` | U | **Couvert** |
| S8 | Token vide, trop long ou sous forme de tableau : refusé **sans** appel à Cloudflare | `ValidatorTest::testEmptyTokenIsUserErrorWithoutHttpCall`, `testOversizedTokenIsUserErrorWithoutHttpCall`, `FormGuardTest::testArrayTokenIsTreatedAsEmpty` | U | **Couvert** |
| S9 | La route protégée est déterminée côté serveur (`getFullActionName()` du routeur), jamais depuis un paramètre du client | `FormPredispatchObserverTest::testClientFormIdParamIsIgnored` (à écrire) | U | **A** |
| S10 | La comparaison de route ne dépend pas de la casse (`customer/account/CreatePost` est aussi protégé) | `…ObserverTest::testRouteMatchIsCaseInsensitive` + requête `curl` en E2E | U + E | **A** |
| S11 | Une URL réécrite (URL rewrite) vers une action protégée reste protégée | Test d'intégration : POST sur l'URL réécrite sans token → rejet | I | **A** |
| S12 | Les POST vers une route protégée sont validés ; les autres méthodes vers une route protégée suivent S25 ; les requêtes vers une route non protégée sont ignorées. Token lu dans le corps POST ou l'en-tête `X-Turnstile-Token`, **jamais** dans la query string | `FormGuardTest::testQueryTokenIsIgnored`, `testHeaderTokenIsAccepted`, `…ObserverTest::testRequestToUnprotectedRouteIsIgnored` (à écrire) | U | **A** |
| S13 | En cas d'échec, le contrôleur n'est **jamais** exécuté, même si la réponse n'est pas HTTP | `FormGuardTest::testNonHttpResponseStillBlocksDispatch` (à écrire) | U | **A** |
| S14 | Pour les POST **non-AJAX**, le contrôle CSRF (`form_key`) de Magento reste actif et s'exécute **avant** Turnstile | Faits vérifiés : `FrontController::processRequest` valide la requête (CSRF) avant `dispatchPreDispatchEvents` ; `CsrfValidator::validateRequest` (ligne 74) exige le `form_key` pour un POST sans en-tête `X-Requested-With`. Test I : POST non-AJAX sans `form_key` et avec un token valide → rejet CSRF, **aucun** appel à Cloudflare. Test statique : le module ne contient ni `CsrfAwareActionInterface` ni plugin sur `CsrfValidator` | I + statique | **A** |
| S15 | Pour les POST **non-AJAX**, un token valide ne dispense pas du `form_key` | Même test d'intégration que S14 | I | **A** |
| S15b | Pour les POST **AJAX** (`X-Requested-With: XMLHttpRequest`), Magento ne vérifie **pas** le `form_key` (`CsrfValidator::validateRequest`, ligne 74 : `isXmlHttpRequest()` suffit) : **Turnstile est la seule barrière** sur une route protégée | Test I : POST AJAX sur une route protégée, sans `form_key` et sans token → rejet Turnstile (HTTP 400 JSON), contrôleur non exécuté. Test I : même requête avec un token valide → le contrôleur s'exécute (preuve que le rejet précédent vient bien de Turnstile, pas du CSRF) | I | **A** |
| S16 | Les logs ne contiennent jamais le token, le secret, un mot de passe ni le corps POST | `ValidatorTest::testLogsNeverContainTokenOrSecret`, `SiteVerifyClientTest::testTransportErrorThrowsWithoutLeakingSecret` + nouveaux tests sur l'observer, les persisters et le test de connexion | U | **Couvert** (cœur) / **A**, **C** (nouveaux composants) |
| S17 | Les mots de passe et les champs sensibles ne sont jamais conservés pour pré-remplir le formulaire | `CustomerSessionFormDataPersisterTest::testPasswordsAreNeverKept`, `FormDataFilterTest::testSensitiveAndTokenFieldsRemoved` (à écrire) ; liste par défaut : `password`, `password_confirmation`, `current_password`, `cf-turnstile-response`, `form_key` | U | **A** |
| S18 | Les sélecteurs, identifiants et routes saisis dans l'admin sont validés (liste blanche de caractères) et rejetés sinon | `CustomFormsBackendModelTest::testRejectsInvalidId`, `testRejectsInvalidRoute`, `testRejectsDuplicateId` + E2 | U + E | **C** |
| S19 | La config envoyée au navigateur est sérialisée en JSON sûr (pas de sortie de `<script>`, pas d'injection) | `WidgetConfigTest::testSelectorWithScriptTagIsEscaped` avec le sélecteur `</script><script>alert(1)</script>` → aucune balise dans la sortie (`JSON_HEX_TAG`) | U | **B** |
| S20 | Pas de redirection ouverte : l'URL de redirection d'un Custom Form est relative ou sur le même domaine, et « page précédente » utilise le referer validé par Magento | `CustomFormsBackendModelTest::testRejectsExternalRedirectUrl`, `…ObserverTest::testExternalRefererFallsBackToBaseUrl` | U | **C** |
| S21 | Le secret n'est jamais envoyé au navigateur (widget, config JSON, réponse du test de connexion) | `WidgetTest` / `WidgetConfigTest::testSecretNeverInOutput`, `TestConnectionControllerTest::testResponseNeverContainsSecret` | U | **Couvert** (widget) / **B**, **C** |
| S22 | Le test de connexion est réservé à l'ACL `AlpineCommerce_Turnstile::config`, en POST avec `form_key` admin, et ne journalise pas le secret | `TestConnectionControllerTest::testAclResource`, `testPostOnly`, `testLogsNeverContainSecret` | U | **C** |
| S23 | Le widget est compatible avec le cache de page : aucune donnée propre au client dans le HTML | Test I : deux clients différents → HTML du widget identique ; en-tête `X-Magento-Cache-Debug: HIT` au 2ᵉ appel | I | **B** |
| S24 | Limitation du nombre de requêtes (rate limiting) | Non couvert par le module aujourd'hui. Chaque POST avec un token non vide appelle Cloudflare (jusqu'à 30 s de timeout configurable) : un robot peut occuper les processus PHP | — | **Décision D2** |
| S25 | Pas de contournement par GET (décision D3 = A) : sur une route protégée, toute méthode autre que POST est rejetée, **sauf** si le contrôleur déclare explicitement `HttpGetActionInterface` (alors GET/HEAD passent sans validation, comme page d'affichage) | `…ObserverTest::testGetOnRouteWithoutMethodInterfaceIsRejected`, `testPutOnRouteWithoutMethodInterfaceIsRejected`, `testGetOnRouteWithExplicitGetInterfaceIsNotValidated`, `testPostStillValidatedWhenActionAlsoDeclaresGet` + E15 sur le RMA (`curl -G` → rejet, aucun RMA créé) | U + E | **A** |
| S26 | Jumelles REST / SOAP / GraphQL (décision D4 = B) : un interrupteur par endpoint jumeau, **désactivé par défaut**, réglable par store view, **indépendant de l'activation générale de Turnstile** (décision M2 = 1, 2026-09-25 : le blocage n'appelle pas Cloudflare, couper Turnstile ne rouvre jamais une jumelle) | `ConfigTest::testTwinBlockingDefaultsToOff`, `testTwinBlockingIsReadPerStoreView`, `testTwinBlockingIgnoresTheGeneralSwitch` ; `TwinRegistryPresetsTest::testEveryTwinIsSwitchedOffByDefault` (valeur par défaut de chaque interrupteur dans `config.xml`) | U + I | **A** |
| S27 | Interrupteur activé : l'appel **anonyme** à la jumelle REST est refusé (HTTP 403), le service n'est pas exécuté | Test I par endpoint REST : `POST /rest/<store>/V1/customers`, `PUT /rest/<store>/V1/customers/password`, `POST /rest/<store>/V1/integration/customer/token` → 403, aucun client créé / aucun e-mail / aucun token émis | I | **A** |
| S27b | Interrupteur activé : l'appel **anonyme** à l'opération SOAP jumelle est refusé (SOAP Fault), le service n'est pas exécuté. Un même interrupteur couvre le endpoint REST et l'opération SOAP du même service (même modèle que `Magento_ReCaptchaWebapiRest`, qui protège déjà REST et SOAP) | Test I par opération sur `/soap/<store>?services=…` : `customerAccountManagementV1CreateAccount`, `customerAccountManagementV1InitiatePasswordReset`, `integrationCustomerTokenServiceV1CreateCustomerAccessToken` → SOAP Fault, aucun client créé / aucun e-mail / aucun token émis | I | **A** |
| S27c | Interrupteur REST activé : le même service appelé en **asynchrone** (`/rest/<store>/async/V1/…`) ou **en masse** (`/rest/<store>/async/bulk/V1/…`) en anonyme est refusé **au moment de la requête** (403, aucune opération mise en file). Un même interrupteur couvre REST synchrone, asynchrone, bulk et SOAP du même service. Hypothèse **déduite du code, pas encore prouvée** : `AsynchronousRequestProcessor` passe par `RequestValidator::validate()` (comme le REST synchrone), le point où `Magento_ReCaptchaWebapiRest` se branche | Test I : `POST /async/V1/customers` et `POST /async/bulk/V1/customers` (2 clients) → 403 ; table `magento_operation` sans nouvelle ligne ; aucun client créé après exécution du consumer `async.operations.all`. **Ce test doit être réellement exécuté** avant que la ligne passe au statut A ; s'il échoue, arrêt et signalement | I | **À confirmer par test réel**, puis **A** |
| S28 | Interrupteur activé : la mutation GraphQL jumelle renvoie une erreur GraphQL (`errors[]`), le resolver n'est pas exécuté | Test I par mutation : `createCustomer`, `createCustomerV2`, `generateCustomerToken`, `requestPasswordResetEmail`, `subscribeEmailToNewsletter`, `contactUs`, `createProductReview`, `sendEmailToFriend` → erreur, aucun effet en base | I | **A** |
| S29 | Interrupteur désactivé, ou activé sur un **autre** store view : la jumelle fonctionne normalement | Test I : même appel qu'en S27/S27b/S28 sur le store view B (interrupteur actif sur A seulement) → réponse normale du service | I | **A** |
| S30 | Le blocage vise seulement les appels **anonymes** : un appel authentifié (token client ou intégration admin) n'est pas bloqué | Test I : `POST /V1/customers` **et** l'opération SOAP `customerAccountManagementV1CreateAccount` avec un token d'intégration admin → client créé malgré l'interrupteur actif | I | **A** |
| S31 | Exception Hyvä documentée : le formulaire d'avis produit de Hyvä passe par la mutation `createProductReview`. L'activer casse les avis sous Hyvä | Commentaire admin affiché sous l'interrupteur `createProductReview` (vérifié dans `system.xml` et à l'écran) ; section « Limites connues » du README ; E2E : Hyvä, interrupteur désactivé → un avis se publie ; interrupteur activé → le formulaire Hyvä affiche une erreur (comportement attendu et documenté) | E + doc | **A** |

### 4.1 Critères de validation de l'objectif 4

- [ ] Toutes les lignes marquées « Couvert » restent vertes après la refonte (aucun test existant supprimé sans remplaçant équivalent nommé dans le plan).
- [ ] Chaque ligne A / B / C a son test écrit **avant** le code (TDD) et vert en CI.
- [ ] Les décisions D1 et D2 sont tranchées ; la ligne correspondante porte un test ou une mention « Hors périmètre » documentée (D3 et D4 sont tranchées : S25–S31).
- [ ] `grep -rniE "secret|token|password" var/log/turnstile.log` après tout le scénario E2E : aucune **valeur** sensible (seuls des noms de clés ou des codes d'erreur).
- [ ] Les tests K1 à K10 (§4.2) sont écrits avant le code et verts.

### 4.2 Conservation de la saisie après un rejet (D7 = 1)

Mécanisme (D7 = 1) : un `SessionFormDataPersister` générique, configuré dans `di.xml` par des chaînes (type de session,
setter, champ lu, champs exclus). La session n'est créée qu'au moment d'un rejet, et seulement si le module requis est
activé. Chaque test d'intégration ci-dessous : POST rejeté par Turnstile (sans token) sur la route du préréglage, puis
vérification que **le bon setter a été appelé sur la bonne session**. La « bonne session » est l'instance partagée du
type que le contrôleur Magento natif reçoit par DI. On relit la valeur avec le getter natif que Magento utilise pour
pré-remplir le formulaire. Aucun mot de passe, aucun `cf-turnstile-response`, aucun `form_key` dans la valeur relue.
Newsletter est exclue (Magento ne conserve rien pour ce formulaire).

| # | Préréglage | Session (type DI utilisé par Magento) | Setter attendu | Relecture native (preuve) | Test | Type | Statut |
|---|---|---|---|---|---|---|---|
| K1 | Contact | `DataPersistor` (framework, **pas une session de module**) clé `contact_us` | `set('contact_us', …)` | `Magento\Contact\Helper\Data` → `get('contact_us')` | `FormDataPersistenceTest::testContactRejectKeepsInput` | I | **A** |
| K2 | Création de compte | `Magento\Customer\Model\Session` | `setCustomerFormData(array)` | `Register::getFormData()` → `getCustomerFormData(true)` : `email`, `firstname` présents ; `password`, `password_confirmation` absents | `FormDataPersistenceTest::testCustomerCreateRejectKeepsInputWithoutPasswords` | I | **A** |
| K3 | Connexion | `Magento\Customer\Model\Session` | `setUsername(string)` avec la valeur de `login[username]` | `Login::getUsername()` ; `login[password]` jamais stocké | `FormDataPersistenceTest::testLoginRejectKeepsUsernameOnly` | I | **A** |
| K4 | Mot de passe oublié | `Magento\Customer\Model\Session` | `setForgottenEmail(string)` avec la valeur de `email` | `ForgotPassword` → `getForgottenEmail()` | `FormDataPersistenceTest::testForgotPasswordRejectKeepsEmail` | I | **A** |
| K5 | Envoyer à un ami | `Magento\Catalog\Model\Session` | `setSendfriendFormData(array)` | `SendFriend\Controller\Product\Send` → `getSendfriendFormData()` | `FormDataPersistenceTest::testSendFriendRejectKeepsInput` | I | **A** |
| K6 | Partage de wishlist | `Magento\Wishlist\Model\Session` (virtualType de `Framework\Session\Generic`, namespace `wishlist`) | `setSharingForm(array)` | `Wishlist\Block\Customer\Sharing` → `getData('sharing_form', true)` | `FormDataPersistenceTest::testWishlistShareRejectKeepsInput` | I | **A** |
| K7 | Avis produit | `Magento\Review\Model\Session` (virtualType de `Framework\Session\Generic`, namespace `review`) | `setFormData(array)` | `Review\CustomerData\Review` → `getFormData(true)` | `FormDataPersistenceTest::testReviewRejectKeepsInput` | I | **A** |
| K8 | Module requis désactivé : le persister ne crée aucune session et ne lève aucune erreur ; le rejet Turnstile reste appliqué | — | aucun | — | `SessionFormDataPersisterTest::testMissingModuleSkipsPersistenceWithoutError` | U | **A** |
| K9 | Requête acceptée : aucune session créée, aucun setter appelé | — | aucun | — | `SessionFormDataPersisterTest::testNothingPersistedWhenRequestPasses` | U | **A** |
| K10 | Configuration `di.xml` **détectable** invalide : type de session inexistant, type qui n'est pas une session (`SessionManagerInterface`), ou setter mal formé (ne respecte pas `^set[A-Z][A-Za-z0-9]*$`) → erreur explicite en mode développeur, log `error` sans données du formulaire en production, rejet Turnstile maintenu. **Limite :** un setter bien formé mais mal orthographié (ex. `setCustomerFormDat`) n'est **pas** détectable par le code, car les sessions Magento acceptent tout `set*` via `__call` ; ce cas est détecté uniquement par K1–K7 (relecture avec le getter natif) | — | — | — | `SessionFormDataPersisterTest::testUnknownSessionTypeIsReported`, `testNonSessionTypeIsReported`, `testMalformedSetterIsReported` (aucune donnée du formulaire dans le message ni le log) | U | **A** |

---

## 5. Décisions à prendre avant de figer la spec A

Aucune n'est tranchée dans ce document. Chaque point sera présenté avec ses options, puis choisi par l'utilisateur.

| # | Question |
|---|---|
| D1 | Turnstile activé mais clé manquante : continuer à ignorer la vérification (aujourd'hui, avec un log), ou rejeter, ou ignorer avec une alerte visible dans l'admin ? |
| D2 | Limitation du nombre de requêtes : dans le module, ou déléguée à Cloudflare WAF / au serveur web (documentée) ? |
| D3 | **Tranchée le 2026-09-25 : A** — sur une route protégée, rejeter toute méthode autre que POST, sauf `HttpGetActionInterface` explicite (S25) |
| D4 | **Tranchée le 2026-09-25 : B** — interrupteur admin par endpoint jumeau (REST, SOAP et GraphQL), désactivé par défaut, par store view, avec l'exception Hyvä / `createProductReview` documentée (S26–S31, S27b) |
| D5 | Versions Magento / PHP supportées (borne basse de la matrice P11) |
| D6 | **Tranchée le 2026-09-25 : 1 + a** — champs générés à partir du registre (`forms/<id>`, `forms/<id>_failure_mode`, chemin `forms/contact` conservé) ; préréglage masqué si le module Magento requis est absent (§1.5, §2.3) |
| D7 | **Tranchée le 2026-09-25 : 1** — `SessionFormDataPersister` générique configuré par `di.xml` (chaînes), chargement à la demande, aucune dépendance à la compilation ; un test d'intégration « rejet → champ pré-rempli » par préréglage (§4.2, K1–K10) |

## 6. Definition of Done globale (fin du sous-projet C)

- [ ] Objectifs 1 à 4 : toutes leurs cases cochées avec preuve.
- [ ] CI verte : lint, XML, `composer validate`, PHPUnit, PHPStan niveau 5, PHPCS, matrice d'installation.
- [ ] README du paquet : installation Composer, configuration, intégration d'un formulaire par `di.xml`, exemple `fetch` avec `getToken`, limites connues.
- [ ] `CHANGELOG.md` du paquet avec version SemVer.
- [ ] `docs/modules/TURNSTILE.md` mis à jour.
- [ ] Les traductions `fr_FR`, `de_DE`, `es_ES` et `it_IT` couvrent toutes les nouvelles phrases (vérification avec un vrai parseur CSV).
