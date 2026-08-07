# AlpineCommerce — Apprendre Magento 2 avec un vrai projet e-commerce

> **Un dépôt pour deux usages :** une plateforme e-commerce réelle construite sur
> **Magento 2.4.8** (Adobe Commerce Open Source), et un **cours pratique complet** qui vous
> emmène de « je ne connais pas Magento » à « je développe un module professionnel ».

```
┌──────────────────────────────────────────────────────────────┐
│   AlpineCommerce                                              │
│   ├── Plateforme e-commerce (13 modules métier)               │
│   └── Cours Magento 2                                         │
│       ├── docs/README.md             → le hub de la documentation │
│       ├── docs/ENGINEERING_GUIDE.md  → les standards          │
│       └── docs/modules/{Module}.md   → le chapitre de chaque module │
└──────────────────────────────────────────────────────────────┘
```

---

## Qu'est-ce que Magento 2 ?

**Magento 2** est une plateforme e-commerce open source écrite en PHP, éditée par Adobe.
Elle se décline en deux éditions :

| Édition | Ce que c'est |
|---|---|
| **Adobe Commerce** (ex Magento Commerce) | Édition payante : fonctionnalités B2B, MSI avancé, Page Builder, support Adobe, cloud optionnel. |
| **Magento Open Source** | Édition gratuite : le cœur e-commerce complet, extensible par modules. **C'est celle que nous utilisons.** |

**Pourquoi Magento existe** : les boutiques en ligne deviennent vite complexes (multi-boutiques,
fichiers de millions de produits, workflows de validation, intégrations ERP, personnalisation
à grande échelle). Magento structure cette complexité avec une architecture modulaire, au lieu
de tout recoder à chaque projet.

**Les 4 idées à retenir**
1. **Modulaire** : tout est un module. On ajoute une fonctionnalité en créant un module, pas en modifiant le core.
2. **Extensible sans modification** : Plugins, Observers, Layout XML et DI permettent de changer le comportement sans toucher au code d'origine.
3. **Service Contracts** : chaque capacité métier est exposée par une interface — la même API sert au frontend, à l'admin et à la REST API.
4. **EAV + tables** : le modèle de données combine attributs flexibles (EAV) et tables plates optimisées.

**Architecture générale** (la phrase à retenir)

```
Requête HTTP
    ↓
Router (routeur d'URL)
    ↓
Controller (orchestre, ne fait pas de logique métier)
    ↓
Service Contract (interface métier)
    ↓
Repository (implémentation, encapsule l'accès données)
    ↓
ResourceModel (parle à la base de données)
    ↓
Database (MySQL)
    ↓
Response (HTML, JSON...)
```

Chaque étape est expliquée en détail dans `docs/ARCHITECTURE.md`.

---

## Pourquoi choisir Magento ?

**Les projets qui utilisent Magento** : grandes et moyennes enseignes avec catalogue large,
multi-boutiques, B2B et B2C, besoin de personnalisation profonde et d'intégrations
(ERP, PIM, paiement, logistique).

| ✅ Avantages | ❌ Inconvénients |
|---|---|
| Écosystème complet (commerce, CMS, promo, B2B) | Courbe d'apprentissage raide |
| Extensible sans toucher au core | Consomme des ressources (pas pour une vitrine) |
| Multiboutique / multi-langues natif | Coût d'infrastructure en production |
| Grande communauté et marché d'extensions | Les extensions peuvent se heurter |
| REST API + GraphQL | Hébergement spécialisé recommandé |

**Quand Magento n'est PAS une bonne solution**
- Boutique simple (< 100 produits, sans personnalisation) → un SaaS (Shopify, WooCommerce) suffit.
- Vitrine statique sans paiement → inutilement lourd.
- Équipe sans expérience PHP/Symfony → la courbe d'apprentissage sera un frein.

---

## La philosophie d'AlpineCommerce

> **Toujours étendre avant de créer.**

Nous **n'écrivons jamais** nos propres versions de Catalog, Customer, Sales, Checkout,
Inventory ou CMS : ces briques sont fournies par Magento et éprouvées. Nous les **étendons**.

Ordre d'extension (du moins intrusif au plus intrusif) :

```
Plugin        → intercepter une méthode existante
Observer      → réagir à un événement métier
Layout XML    → modifier la structure des pages
Preference DI → remplacer une classe (dernier recours)
Nouveau module→ uniquement pour une valeur métier nouvelle
```

Voir `docs/ENGINEERING_GUIDE.md` → section « ❌ Ce qu'il ne faut JAMAIS faire ».

---

## Le projet en bref

- **Version cible** : Magento 2.4.8 (PHP 8.2)
- **13 modules métier** dans `src/app/code/AlpineCommerce/` — chacun illustre des concepts
  Magento précis (voir `docs/README.md` → « Documents modules »)
- **Frontend** : thème custom Luma-based dans `src/app/design/`
- **API** : REST uniquement pour l'instant (GraphQL documenté mais non utilisé — choix assumé)
- **Environnement** : Docker (PHP-FPM, Nginx, MySQL, Redis, Elasticsearch)

## Documentation

| Document | Rôle |
|---|---|
| `docs/README.md` | 🎯 Le hub de la documentation officielle |
| `docs/PROJECT_CHARTER.md` | 🎓 Vision, philosophie, cahier des charges et analyse fonctionnelle v1.0 |
| `docs/ENGINEERING_GUIDE.md` | 📐 L'Engineering Bible : standards et anti-patterns |
| `docs/ARCHITECTURE.md` | 🏗️ Architecture Magento et AlpineCommerce + registre ADR |
| `docs/ROADMAP.md` | 🗺️ La roadmap produit et l'historique des versions |
| `docs/CHANGELOG.md` | 📜 Historique des versions et des correctifs |
| `docs/BACKLOG.md` | 🛠️ La dette technique tracée (Phase C) |
| `docs/modules/*.md` | 📚 Le chapitre de chaque module (13 documents) |

---

---

# Magento 2.4.8 - Docker Development Environment

## What's in the repository

This repository contains the source code for the Magento 2.4.8 project along with a full Docker development environment.

### Tracked (committed to git)
- `src/app/code/` — Custom modules (AlpineCommerce/*, Cartware/*)
- `src/app/design/` — Custom themes
- `src/app/etc/config.php` — Module status/config (safe to commit)
- `src/composer.json` — Project dependencies manifest
- `src/bin/magento` — Magento CLI
- `docker-compose.yml` — Docker service definitions
- `Dockerfile`, `php.ini`, `nginx/` — PHP and Nginx config
- `scripts/` — Installation and management scripts
- `.env.example` — Template for Docker environment variables

### Excluded (NOT committed to git — regenerated locally)
- `src/vendor/` — Composer dependencies (840MB, restore via `composer install`)
- `src/generated/` — Generated code (regenerated by Magento)
- `src/pub/media/` — Uploaded images/products (273MB, user-generated content)
- `src/pub/static/` — Static assets (regenerated via `setup:static-content:deploy`)
- `src/var/` — Cache, sessions, logs, reports
- `src/app/etc/env.php` — Database credentials and environment-specific config
- `.env` — Docker environment variables (contains passwords)

## Prerequisites

1. **Docker Desktop** (or Docker Engine on Linux, Docker Desktop on macOS/Windows)
2. **Docker Compose** plugin (included with Docker Desktop 4.0+)
3. **Magento Marketplace account** (free) — get your keys at [developer.adobe.com](https://developer.adobe.com/commerce/marketplace/)

### Windows prerequisites
- **Docker Desktop for Windows** with WSL 2 backend
- **Git Bash** (comes with Git for Windows) **or** **PowerShell 7+**
- Use the `.ps1` scripts in `scripts/` instead of the `.sh` scripts

## Setup for new team members

### Linux / macOS

```bash
# 1. Clone the repository
git clone https://github.com/Boutayna4321/magento2.git
cd magento2

# 2. Copy the environment template and adjust values if needed
cp .env.example .env

# 3. Start Docker containers and install Magento
chmod +x scripts/install.sh
./scripts/install.sh
```

### Windows (PowerShell)

```powershell
# 1. Clone the repository
git clone https://github.com/Boutayna4321/magento2.git
cd magento2

# 2. Copy the environment template
copy .env.example .env

# 3. Start Docker containers and install Magento
.\scripts\install.ps1
```

When prompted for Magento auth keys, paste your Marketplace **Public Key** and **Private Key**. The script will:
- Build and start all containers (PHP, Nginx, MySQL, Elasticsearch, Redis)
- Install Magento via Composer
- Run `setup:install` with database and cache configuration
- Deploy static content and reindex

### Access after installation
| Service         | URL / Host          |
|-----------------|---------------------|
| Storefront      | http://localhost:8080 |
| Admin Panel     | http://localhost:8080/admin |
| Admin User      | admin / admin123   |
| MySQL           | localhost:3306     |
| Elasticsearch   | localhost:9200     |
| Redis           | localhost:6379     |

## Useful commands

### Linux / macOS
| Command                    | Description              |
|----------------------------|--------------------------|
| `scripts/start.sh`         | Start containers         |
| `scripts/stop.sh`          | Stop containers          |
| `scripts/magento-cli.sh`   | Run Magento CLI command  |

Example: `./scripts/magento-cli.sh cache:flush`

### Windows (PowerShell)
| Command                    | Description              |
|----------------------------|--------------------------|
| `.\scripts\start.ps1`      | Start containers         |
| `.\scripts\stop.ps1`       | Stop containers          |
| `.\scripts\magento-cli.ps1`| Run Magento CLI command  |

Example: `.\scripts\magento-cli.ps1 cache:flush`

## Restoring from a backup

If you have a database dump or media backup:
1. Start containers: `docker compose up -d`
2. Import database: `docker compose exec mysql mysql -u root -proot123 magento2 < backup.sql`
3. Copy media files: `cp -r /path/to/media/* src/pub/media/`
4. Reindex: `./scripts/magento-cli.sh indexer:reindex`
5. Flush cache: `./scripts/magento-cli.sh cache:flush`

## Git workflow

- **Never** commit `vendor/`, `var/`, `pub/media/`, `generated/`, or `app/etc/env.php`
- The `.gitignore` handles this automatically
- Custom modules live in `src/app/code/{Vendor}/{Module}/`
- Always test changes with `bin/magento setup:upgrade` after pulling new code
