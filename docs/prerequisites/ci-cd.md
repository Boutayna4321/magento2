# CI/CD — Guide Complet pour Débutants

> **Target audience**: débutants qui veulent comprendre **CI/CD** et comment
> ça s'applique au projet AlpineCommerce.

---

## 1. Qu'est-ce que CI/CD ?

### 1.1 CI = Continuous Integration (Intégration Continue)

La **CI** vérifie automatiquement que chaque modification du code est
correcte **avant** qu'elle ne soit acceptée dans le projet.

**Sans CI** : un développeur commit du code qui casse tout → tout le monde
attend qu'on s'aperçoive du bug → correction urgente.

**Avec CI** : dès qu'un développeur propose une modification (Pull Request),
des robots exécutent des tests automatiques :
- Le code PHP est bien écrit ?
- Les fichiers XML sont valides ?
- L'application compile sans erreur ?
- Les tests unitaires passent ?

Si un test échoue, la PR est **bloquée** jusqu'à correction.

### 1.2 CD = Continuous Deployment (Déploiement Continu)

Le **CD** va plus loin : quand le code est validé ( mergé dans `main` ),
il est **déployé automatiquement** vers un environnement (staging, production).

**Sans CD** : un développeur merge → il faut se connecter au serveur →
lancer des commandes manuellement → risque d'erreur humaine.

**Avec CD** : merge → le déploiement se fait tout seul → en quelques minutes
le site est à jour.

---

## 2. Analogie : l'usine de voitures

| Étape | CI/CD | Analogie |
|-------|-------|----------|
| Développeur écrit du code | — | Ouvrier assemble une porte |
| Pull Request | **CI** | La porte passe par un contrôle qualité |
| Tests automatiques | **CI** | Vérifications : dimensions, peinture, mécanisme |
| Si test échoue | **CI bloquée** | La porte est rejetée, retour à l'atelier |
| Merge dans `main` | **CI réussie** | La porte est validée |
| Déploiement auto | **CD** | La porte est installée sur la voiture automatiquement |

---

## 3. GitHub Actions — La CI/CD de GitHub

GitHub propose **GitHub Actions** : des workflows automatisés qui
s'exécutent sur les serveurs de GitHub quand :
- Tu push du code
- Tu ouvres une Pull Request
- Tu crées un tag (version)

Les workflows sont définis dans des fichiers YAML dans
`.github/workflows/`.

---

## 4. Le workflow CI d'AlpineCommerce

### 4.1 Structure du fichier

```
.github/
└── workflows/
    ├── ci.yml    # Tests automatiques (lint, XML, Docker, secrets)
    └── cd.yml    # Build + push Docker image sur main
```

### 4.2 Quand s'exécute-t-il ?

```yaml
on:
  push:
    branches: [ main ]          # Sur chaque push vers main
  pull_request:
    branches: [ main ]          # Sur chaque PR vers main
```

### 4.3 Les jobs (étapes) de la CI

#### Job 1 : PHP Lint
```yaml
jobs:
  php-lint:
    name: PHP Lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Lint AlpineCommerce PHP files
        run: |
          find src/app/code/AlpineCommerce -name '*.php' -print0 | xargs -0 -n1 php -l
```

**Ce que ça fait** : vérifie que chaque fichier PHP est syntaxiquement
correct (pas d'erreur de parsing).

**Durée** : ~30 secondes

#### Job 2 : XML Validation
```yaml
  xml-validation:
    name: XML Validation
    steps:
      - name: Validate module.xml files
        run: |
          find src/app/code/AlpineCommerce -name 'module.xml' -print0 | xargs -0 -n1 php -r '...'
      - name: Validate layout XML files
        run: |
          find src/app/code/AlpineCommerce -path '*/layout/*.xml' -print0 | xargs -0 -n1 php -r '...'
      - name: Validate ui_component XML files
        run: |
          find src/app/code/AlpineCommerce -path '*/ui_component/*.xml' -print0 | xargs -0 -n1 php -r '...'
      - name: Validate db_schema.xml files
        run: |
          find src/app/code/AlpineCommerce -name 'db_schema.xml' -print0 | xargs -0 -n1 php -r '...'
```

**Ce que ça fait** : vérifie que tous les fichiers XML sont bien formés
(ouvrants/fermants corrects, pas de caractères illégaux).

**Durée** : ~20 secondes

#### Job 3 : Composer Validate
```yaml
  composer-validate:
    name: Composer Validate
    steps:
      - run: composer validate --no-check-publish --working-dir=src
```

**Ce que ça fait** : vérifie que `composer.json` est valide (dépendances
correctes, format JSON valide).

**Durée** : ~10 secondes

#### Job 4 : Docker Build Test
```yaml
  docker-build:
    name: Docker Build Test
    steps:
      - uses: docker/setup-buildx-action@v3
      - uses: docker/build-push-action@v5
        with:
          context: .
          file: ./php/Dockerfile
          push: false
          tags: magento2-alpinecommerce:ci
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

**Ce que ça fait** : construit l'image Docker pour vérifier qu'il n'y a
pas d'erreur dans le `Dockerfile` (extension PHP manquante, etc.).

**Durée** : ~2-3 minutes (première fois), puis ~30 secondes (cache)

#### Job 5 : Secret Scanning
```yaml
  secret-scan:
    name: Secret Scanning
    steps:
      - uses: trufflesecurity/trufflehog@main
        with:
          path: ./
          base: main
          head: HEAD
```

**Ce que ça fait** : scanne le code pour détecter des mots de passe,
clés API, tokens GitHub, etc. qui auraient été commités par erreur.

**Durée** : ~30 secondes

#### Job 6 : Markdown Lint
```yaml
  markdown-lint:
    name: Markdown Lint
    steps:
      - uses: DavidAnson/markdownlint-cli2-action@v19
        with:
          config: markdownlint.json
          globs: '**/*.md'
```

**Ce que ça fait** : vérifie que les fichiers `.md` respectent des règles
de formatage (longueur de lignes, titres bien hiérarchisés, pas d'espace
en fin de ligne, etc.).

**Durée** : ~10 secondes

---

## 5. Le workflow CD d'AlpineCommerce

### 5.1 Quand s'exécute-t-il ?

```yaml
name: CD — Continuous Deployment

on:
  push:
    branches: [ main ]
```

Dès que quelqu'un push vers `main` (après que la PR ait été mergée).

### 5.2 Ce qu'il fait

```yaml
jobs:
  build-and-push:
    name: Build & Push Docker Image
    steps:
      - uses: actions/checkout@v4
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3
      - name: Log in to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ghcr.io/${{ github.repository_owner }}/magento2
          tags: |
            type=ref,event=branch
            type=sha,prefix={{branch}}-
            type=raw,value=latest
      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: .
          file: ./php/Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.tags }}
```

**Ce que ça fait** :
1. Construit l'image Docker à partir du `Dockerfile`
2. La pousse vers **GitHub Container Registry** (`ghcr.io`)
3. Tague l'image avec :
   - `latest` (toujours la dernière version sur main)
   - Le hash du commit (ex: `main-a1b2c3d`)
   - Le nom de la branche

**Résultat** : l'image est disponible pour être déployée sur n'importe
quel serveur.

---

## 6. Comment ça marche en pratique ?

### 6.1 Scénario 1 : Pull Request

```
1. Alice crée une branche : feature/add-blog-search
2. Alice commit ses modifications
3. Alice push vers GitHub
4. Alice ouvre une Pull Request vers main
5. GitHub Actions se déclenche automatiquement (CI)
6. Les jobs s'exécutent en parallèle :
   - PHP Lint
   - XML Validation
   - Composer Validate
   - Docker Build
   - Secret Scan
   - Markdown Lint
7. Si TOUS les jobs passent → ✅ la PR a un badge vert "All checks passed"
8. Bob review le code
9. Bob clique sur "Merge pull request"
10. Le code est mergé dans main
11. Le workflow CD se déclenche automatiquement
12. Une nouvelle image Docker est buildée et poussée vers ghcr.io
13. ✅ Le projet est déployé
```

### 6.2 Scénario 2 : Bug découvert par la CI

```
1. Alice commit du code avec une erreur XML
2. Alice push et ouvre une PR
3. Le job XML Validation échoue :
   ❌ Error: Opening and ending tag mismatch in layout XML
4. GitHub affiche un badge rouge ❌
5. Alice voit l'erreur dans les logs du workflow
6. Alice corrige le XML
7. Alice push la correction
8. Le workflow se relance
9. Cette fois, tous les jobs passent ✅
10. La PR peut être mergée
```

---

## 7. Les concepts clés

### 7.1 Workflow

Un **workflow** est un fichier YAML qui définit :
- Quand il s'exécute (`on: push`, `on: pull_request`)
- Quels jobs exécuter
- Dans quel ordre
- Sur quel type de machine (`runs-on: ubuntu-latest`)

### 7.2 Job

Un **job** est une étape du workflow. Plusieurs jobs peuvent s'exécuter
**en parallèle** pour gagner du temps.

```yaml
jobs:
  php-lint:      # Job 1
  xml-validation: # Job 2 (s'exécute en même temps que Job 1)
  docker-build:  # Job 3 (s'exécute en même temps que Job 1 et 2)
```

### 7.3 Step (étape)

Un **step** est une commande à l'intérieur d'un job.

```yaml
jobs:
  php-lint:
    steps:
      - uses: actions/checkout@v4        # Step 1: télécharger le code
      - uses: shivammathur/setup-php@v2   # Step 2: installer PHP
      - run: php -l file.php              # Step 3: exécuter la commande
```

### 7.4 Runner

Un **runner** est la machine virtuelle qui exécute le workflow.
- `ubuntu-latest` : Linux (le plus courant)
- `windows-latest` : Windows
- `macos-latest` : macOS

### 7.5 Action

Une **action** est un package réutilisable qui fait une tâche spécifique.

```yaml
- uses: actions/checkout@v4           # Action : télécharge le code
- uses: docker/build-push-action@v5   # Action : build + push Docker
- uses: trufflesecurity/trufflehog@main # Action : scan de secrets
```

Il existe des milliers d'actions sur le GitHub Marketplace.

### 7.6 Secret

Un **secret** est une variable sensible (mot de passe, clé API, token)
stockée de manière chiffrée. Il ne peut pas être lu dans les logs.

```yaml
- uses: docker/login-action@v3
  with:
    password: ${{ secrets.GITHUB_TOKEN }}  # Jamais affiché dans les logs
```

---

## 8. Lire un workflow CI/CD (exemple pas à pas)

```yaml
name: CI — Continuous Integration
#       ↑ Nom du workflow

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]
#       ↑ Déclencheur : quand exécuter le workflow

jobs:
  php-lint:
    name: PHP Lint
#         ↑ Nom du job (affiché dans l'interface GitHub)

    runs-on: ubuntu-latest
#            ↑ Type de machine virtuelle

    steps:
      - uses: actions/checkout@v4
#             ↑ Action : télécharge le code du repo

      - uses: shivammathur/setup-php@v2
#             ↑ Action : installe PHP 8.2
        with:
          php-version: '8.2'

      - name: Lint AlpineCommerce PHP files
#             ↑ Nom de l'étape (affiché dans les logs)
        run: |
#           ↑ Commande à exécuter
          find src/app/code/AlpineCommerce -name '*.php' -print0 | xargs -0 -n1 php -l
#           ↑ Commande bash : trouver tous les PHP et vérifier la syntaxe
```

---

## 9. Bonnes pratiques

| Pratique | Pourquoi |
|----------|----------|
| **CI rapide** (< 10 min) | Les développeurs ne doivent pas attendre longtemps pour merger |
| **Tests parallèles** | Plusieurs jobs en même temps = gain de temps |
| **Cache des dépendances** | Docker, Composer, npm : éviter de retélécharger à chaque fois |
| **Secrets chiffrés** | JAMAIS de mot de passe en dur dans un workflow |
| **Notifications** | Prévenir l'équipe quand la CI échoue (Slack, email) |
| **Badges README** | Afficher le statut de la CI dans le README du projet |

---

## 10. Le badge de statut

Ajoute ceci dans le `README.md` du projet :

```markdown
![CI](https://github.com/Boutayna4321/magento2/actions/workflows/ci.yml/badge.svg)
```

Cela affiche un badge vert (✅) ou rouge (❌) directement dans le README
pour montrer si la dernière CI a réussi ou échoué.

---

## 11. Résumé

| Concept | Définition | Dans AlpineCommerce |
|---------|-----------|---------------------|
| **CI** | Tests automatiques à chaque modification | `ci.yml` : lint, XML, Docker, secrets |
| **CD** | Déploiement automatique après merge | `cd.yml` : build + push Docker sur main |
| **Workflow** | Fichier YAML qui définit la CI/CD | `.github/workflows/ci.yml` |
| **Job** | Étape du workflow (peut être parallèle) | `php-lint`, `xml-validation`, `docker-build` |
| **Step** | Commande à l'intérieur d'un job | `php -l file.php` |
| **Action** | Package réutilisable | `actions/checkout@v4` |
| **Runner** | Machine qui exécute le workflow | `ubuntu-latest` |
| **Secret** | Variable sensible chiffrée | `secrets.GITHUB_TOKEN` |

---

## 12. Prochaines étapes

- Observer les workflows dans l'onglet **Actions** du repo GitHub
- Lire les logs quand un job échoue (c'est formatif)
- Ajouter des tests unitaires AlpineCommerce dans `src/app/code/AlpineCommerce/*/Test/`
- Ajouter un job PHPStan dans la CI pour l'analyse statique
- Configurer les notifications (Slack/Discord) pour les alertes CI

---

*Last updated: 2026-08-11.*
