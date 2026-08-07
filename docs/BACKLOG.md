# Backlog technique AlpineCommerce

> Ce document centralise la **dette technique** détectée par l'audit des modules.
> Elle est traitée en **Phase C** (harmonisation progressive), après le gel des standards
> (Phase A) et la finalisation de la plateforme v1.0 (Phase B).
> Chaque entrée suit le format : **Problème → Pourquoi → Correctif → Priorité**.
>
> Sources : `10_BACKLOG.md` (Phase A) et audit v1.0 des 13 modules (ancien rapport Sprint 11, archivé).

---

## B-01 — Grilles admin non conformes XSD Magento 2.4.8 (6 fichiers)

**Impact : critique.** Les pages admin concernées plantent (ou peuvent planter) au
chargement : exception `ConfigurableObject` — *« Node "argument" with name "class" is
required for this type »*.

**Problème**
Les `<listing>` utilisent le format XML d'avant 2.4.x :
- `<primaryDataSource>` (supprimé)
- `<templates><filters><select customScope="...">` (obsolète)
- `<dataSource>` sans enfant `<dataProvider class="..." name="...">` (obligatoire en 2.4.8)

**Fichiers concernés**

| Fichier | Module |
|---|---|
| `Blog/view/adminhtml/ui_component/blog_post_listing.xml` | Blog |
| `Blog/view/adminhtml/ui_component/blog_category_listing.xml` | Blog |
| `Faq/view/adminhtml/ui_component/faq_faq_listing.xml` | **Faq (module canonique !)** |
| `LegalPages/view/adminhtml/ui_component/legal_page_listing.xml` | LegalPages |
| `ProductQuestions/view/adminhtml/ui_component/question_question_listing.xml` | ProductQuestions |
| `ProductReviews/view/adminhtml/ui_component/review_review_listing.xml` | ProductReviews |

**Correctif** (référence déjà appliquée)
`ProductLabels/view/adminhtml/ui_component/productlabels_label_grid.xml` est le seul à
être correct : suppression de `primaryDataSource`, ajout du `<dataProvider>` enfant avec
son `<settings>`, colonnes modernes (`selectionsColumn`, actions).

**Priorité** : Haute — l'admin de 6 modules est concerné, dont le module canonique.

---

## B-02 — StoreLocator : admin en style « legacy » (Block au lieu de UI Component)

**Impact : moyen.** Incohérence pédagogique et fonctionnelle avec le reste du dépôt.

**Problème**
- `StoreLocator/Block/Adminhtml/Store/Listing.php` : grille construite en **Block PHP
  historique** (pas de UI Component).
- `StoreLocator/Block/Adminhtml/Store/Edit.php` : formulaire en Block.
- Aucune interface dans `Api/` (pas de Service Contract).

**Pourquoi c'est un problème pour le projet**
Un apprenant qui compare StoreLocator au module canonique (Faq) voit **deux façons
différentes** de faire de l'admin — et ne sait plus laquelle est la bonne. Le gel des
standards (Phase A) suppose que tout nouveau module utilise UI Components.

**Correctif**
- Migrer la grille vers `view/adminhtml/ui_component/store_store_listing.xml` + DataProvider.
- Définir un Service Contract dans `Api/` + Repository.
- Réutiliser le pattern de `Faq` (module canonique).

**Priorité** : Moyenne (Phase C).

---

## B-03 — Modules sans Service Contract (Hreflang, StoreLocator, Training)

**Impact : faible à moyen — décision à documenter.**

**Problème**

| Module | Interfaces `Api/` | Analyse |
|---|---|---|
| Hreflang | 0 | Module SEO 100 % config (`system.xml` + Block). Pas d'entité métier → le Service Contract est **discutable**. |
| StoreLocator | 0 | A des entités (stores) → le Service Contract est **obligatoire** (voir B-02). |
| Training | 0 | Module d'introduction (premier module, observers). Voué à la simplicité → à **justifier explicitement**. |

**Pourquoi c'est un problème pour le projet**
La règle « toute logique métier est derrière un Service Contract » doit être **uniforme**.
Si des exceptions existent, elles doivent être **documentées comme des décisions assumées**,
pas comme des oublis.

**Correctif**
- StoreLocator : couvert par B-02.
- Hreflang et Training : ajouter une note « Décision » dans leur doc module
  (Phase D) expliquant pourquoi le Service Contract n'est pas applicable ici.
- Si un module ne peut pas être justifié, créer le Service Contract.

**Priorité** : Faible (documentation d'abord, code si nécessaire).

---

## B-04 — Documents d'ingénierie enseignant le mauvais format UI Component

**Impact : moyen (pédagogique).** L'ancien `docs/02_ENGINEERING_GUIDE.md` (section UI Components)
montrait un exemple XML **au format pré-2.4.8** (sans `<dataProvider>` enfant) — le même
que les fichiers cassés de B-01. Un apprenant qui suit le guide reproduisait le bug.

**Correctif** : remplacé par le format 2.4.8 conforme dans `ENGINEERING_GUIDE.md`
(exemple de référence : `productlabels_label_grid.xml`).

**Priorité** : ✅ Corrigé en Phase A (voir `ENGINEERING_GUIDE.md`).

---

## B-05 — Formulaires UI à auditer (risque résiduel)

**Impact : à vérifier.** Les `<form>` admin (`*_form.xml`) n'utilisent pas le même
mécanisme que les `<listing>`, mais doivent être validés un par un sur le même standard.

**Action**
- Auditer tous les `view/adminhtml/ui_component/*_form.xml` contre le XSD 2.4.8.
- Vérifier le chargement HTTP de chaque page admin des modules concernés.

**Priorité** : Moyenne (à faire pendant l'audit de Phase C, avant la doc module).

---

## B-06 — Phase 2 des correctifs de validation (issues résiduelles)

**Impact : moyen.** Reste à traiter après la Phase 1 (14 bugs critiques corrigés,
voir `CHANGELOG.md`) — issues identifiées par les rapports de validation/integration :

| # | Module | Problème |
|---|---|---|
| P1 | Gdpr, LegalPages, ProductQuestions, ProductReviews, StoreLocator, StorePickup | 6 listings admin XSD-invalides (bien-formés) : `<massAction>` (mauvaise casse), `<deps>` texte, `<primaryDataSource>`, `<param>` dans massaction, `<options>` inline |
| P2 | StorePickup | `etc/adminhtml/routes.xml` absent → URLs admin `alphacommerce_pickup/*` irrésolues |
| P3 | StorePickup | `etc/adminhtml/menu.xml` : item sans attribut `action` (menu non cliquable) |
| P4 | Gdpr | `GdprDeleteService` n'anonymise pas les données client (adresses, emails commande) — conformité Art. 17 RGPD incomplète |
| P5 | ProductLabels | Observer `getLabelsByProductId` : requêtes N+1 à optimiser (batch) |
| P6 | ProductLabels | SQL direct dans `getLabelsByProductId` (ne suit pas le pattern ResourceModel) |
| P7 | Hreflang | `adminhtml/routes.xml` orphelin (aucun controller admin) à nettoyer |
| P8 | Gdpr | Aide CLI `gdpr:export` trompeuse (`--help` vs argument positionnel `<customer_id>`) |

**Priorité** : Moyenne (Phase C), hors Phase 1.

---

## B-07 — Absence de tests automatisés (bloquant v1.0)

**Impact : critique (qualité).** L'audit v1.0 a relevé **0 test** (unitaires, intégration,
fonctionnels, API) sur l'ensemble des 13 modules — alors que la charte exige une couverture
minimum de 80 % et que les rapports de validation ont dû reposer sur des tests manuels/curl.

**Correctif**
- Mettre en place le cadre de test (`Test/Unit`, `Test/Integration`, `Test/functional`).
- Commencer par les modules canoniques (Faq, Blog, ProductLabels).
- Configurer le CI/CD pour exécuter les tests à chaque commit.

**Priorité** : Haute (sprint dédié avant/avec la validation v1.0).

---

## B-08 — Module Training incohérent

**Impact : moyen.** Module de démonstration avec des données de démo anormales.

**Problème**
- Data patch `Setup/Patch/Data/CreateStores.php` crée 4 store views et assigne un thème
  (risque de régression sur une installation existante).
- `config.xml` contient des configurations de store/currency/payment/shipping (données de
  démo, pas de la configuration module).

**Correctif** (décision d'architecture assumée : module de **démonstration pédagogique**)
- Supprimer le data patch `CreateStores.php`.
- Supprimer `config.xml`.
- Vider/supprimer `di.xml`.
- Documenter le propos pédagogique du module.

**Priorité** : Moyenne (Phase B).

---

## B-09 — Hreflang : périmètre à documenter (configuration-only)

**Impact : faible.** Module 100 % configuration, sans entités métier.

**Décision assumée** : rester configuration-only (injection automatique des balises
hreflang basées sur les store views actives). Le mapping manuel langue→store view est
reporté en v1.1. Aucun Service Contract n'est requis tant que le module reste
config-only — à documenter dans sa doc module.

**Priorité** : Faible (documentation, Phase D).

---

## Règles du backlog

1. **On ne corrige pas en Phase B.** La priorité de la Phase B est la finalisation des
   fonctionnalités. Sauf bug bloquant critique, la dette reste listée ici.
2. **Chaque correction de Phase C** suit le pattern « migration » : un module à la fois,
   vérifié (grille HTTP OK + `setup:di:compile` OK) avant de passer au suivant.
3. **Un nouveau module ne doit PAS créer de nouvelle entrée ici** — c'est l'objet du gel
   des standards (Phase A → `ENGINEERING_GUIDE.md`).

---

*Dernière mise à jour : 2026-08-06 (audit initial Phase A + compléments rapports de validation).*
