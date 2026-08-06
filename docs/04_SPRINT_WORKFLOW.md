# Workflow des Sprints

## Philosophie

Chaque sprint est une itération fermée, traçable et validée.

Nous ne développons jamais plusieurs fonctionnalités en parallèle.
Nous ne faisons jamais de refactoring non demandé.
Nous ne modifions jamais un module sans validation.

---

## Cycle de vie d'un Sprint

```
┌─────────────┐
│   ANALYSE    │  Comprendre le besoin métier
│   EXISTANT   │  et l'existant technique
└──────┬──────┘
       ▼
┌─────────────┐
│   PLANIF.    │  Proposer l'architecture
│  APPROUVA.  │  et attendre validation
└──────┬──────┘
       ▼
┌─────────────┐
│ DÉVELOPPEM. │  Coder le module ou
│  PROGRESSIF │  l'extension Magento
└──────┬──────┘
       ▼
┌─────────────┐
│ COMPILATION │  setup:upgrade, di:compile,
│  & TESTS    │  cache:clean, indexer:reindex
└──────┬──────┘
       ▼
┌─────────────┐
│    AUDIT     │  Vérifier la conformité
│  TECHNIQUE  │  avec les standards
└──────┬──────┘
       ▼
┌─────────────┐
│   RAPPORT    │  Documenter ce qui a été
│  & STOP     │  fait, puis s'arrêter
└─────────────┘
```

---

## Rôle de l'AI pendant un Sprint

L'AI est un **Tech Lead et Software Architect**.

### Responsabilités

- Analyser l'existant
- Expliquer les choix techniques
- Proposer l'architecture
- Coder les fonctionnalités validées
- Vérifier la conformité (PSR-12, Magento Best Practices)
- Produire des rapports d'audit
- Documenter les décisions

### Ce que l'AI ne fait PAS

- Proposer des refactorings non demandés
- Modifier du code sans validation
- Créer des modules sans justification
- Développer plusieurs fonctionnalités en parallèle

---

## Étapes détaillées

### Étape 1 : Analyse de l'existant

**Actions :**
- Lister les fichiers existants
- Vérifier les dépendances
- Identifier les patterns utilisés
- Détecter les problèmes potentiels

**Livrable :** État des lieux complet

### Étape 2 : Planification et validation

**Actions :**
- Expliquer le besoin métier
- Proposer l'architecture
- Expliquer les choix techniques
- Attendre la validation

**Livrable :** Plan de travail approuvé

### Étape 3 : Développement progressif

**Actions :**
- Développer un fichier à la fois
- Valider chaque étape
- Respecter les conventions

**Livrable :** Code fonctionnel

### Étape 4 : Compilation et tests

**Commandes obligatoires :**

```bash
# Mise à jour de la base de données
bin/magento setup:upgrade

# Compilation du DI
bin/magento setup:di:compile

# Déploiement du contenu statique (si nécessaire)
bin/magento setup:static-content:deploy

# Nettoyage du cache
bin/magento cache:clean

# Vidage du cache
bin/magento cache:flush

# Réindexation
bin/magento indexer:reindex

# Vérification du statut des modules
bin/magento module:status
```

**Vérifications :**
- Aucune erreur PHP (`php -l`)
- Aucune erreur XML
- Aucune erreur DB
- Aucune erreur DI
- Module visible dans `module:status`

### Étape 5 : Audit technique

**Vérifier :**
- Conformité PSR-12
- Respect des conventions Magento
- Cohérence des namespaces
- Absence de code mort
- Absence de références à d'autres modules AlpineCommerce
- Tests unitaires (si applicable)

### Étape 6 : Rapport et STOP

**Produire un rapport contenant :**
- Résumé du travail effectué
- Fichiers modifiés/créés
- Validation des commandes
- Prochaines étapes

**Puis s'arrêter et attendre la validation.**

---

## Règles strictes

### Jamais

- ❌ Modifier plusieurs fonctionnalités en même temps
- ❌ Faire du refactoring non demandé
- ❌ Modifier un autre module sans validation
- ❌ Proposer de recréer Catalog, Customer, Checkout, Sales
- ❌ Ignorer les erreurs de compilation

### Toujours

- ✅ Travailler module par module
- ✅ Attendre la validation avant chaque étape importante
- ✅ Vérifier que Magento ne fait pas déjà la fonctionnalité
- ✅ Documenter les décisions
- ✅ Produire un rapport après chaque sprint

---

## Validation par l'utilisateur

L'utilisateur valide :

1. Le plan de travail (Étape 2)
2. Le développement terminé (Étape 3)
3. L'audit technique (Étape 5)
4. Le rapport final (Étape 6)

**Aucune étape ne peut être franchie sans validation explicite.**
