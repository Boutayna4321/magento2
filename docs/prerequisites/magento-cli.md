# Magento 2 — CLI (`bin/magento`) : Guide Pratique

> **Objectif** : maîtriser la ligne de commande Magento. Ces commandes sont
> utilisées **tous les jours** par les développeurs : installation de modules,
> compilation, cache, déploiement de contenu statique.

---

## 1. Qu'est-ce que `bin/magento` ?

`bin/magento` est le **CLI (Command Line Interface)** de Magento. C'est un
exécutable PHP qui donne accès à toutes les opérations de maintenance et de
développement.

**Pourquoi c'est important** :
- Activer/désactiver des modules : `module:enable`, `module:disable`
- Mettre à jour la base de données : `setup:upgrade`
- Compiler le code (production) : `setup:di:compile`
- Vider les caches : `cache:flush`
- Créer un admin : `admin:user:create`

---

## 2. Comment exécuter les commandes

### 2.1 Dans le conteneur Docker (AlpineCommerce)

```bash
# Méthode recommandée : entrer dans le conteneur PHP
docker compose exec php bash

# Puis exécuter les commandes Magento
php bin/magento module:status
php bin/magento cache:flush
```

### 2.2 Via un script (AlpineCommerce)

Le projet contient un script helper :

```bash
./scripts/magento-cli.sh module:status
./scripts/magento-cli.sh cache:flush
```

### 2.3 Directement (si PHP est installé localement)

```bash
cd /home/cartware/Desktop/magento/src
php bin/magento module:status
```

---

## 3. Commandes essentielles

### 3.1 Gestion des modules

```bash
# Lister tous les modules et leur statut
php bin/magento module:status

# Activer un module
php bin/magento module:enable AlpineCommerce_Blog

# Désactiver un module
php bin/magento module:disable AlpineCommerce_Blog

# Activer plusieurs modules
php bin/magento module:enable AlpineCommerce_Blog AlpineCommerce_Faq

# Vérifier les dépendances (qui dépend de quoi)
php bin/magento module:dependency:show AlpineCommerce_Blog

# Voir la configuration d'un module
php bin/magento module:config:show AlpineCommerce_Blog
```

### 3.2 Mise à jour de la base de données

```bash
# Appliquer les changements de db_schema.xml et data patches
php bin/magento setup:upgrade

# Avec données exemple (sample data)
php bin/magento setup:upgrade --sample-data=yes

# Vérifier le statut de la base de données
php bin/magento setup:db:status
```

**Quand l'utiliser ?**
- Après avoir créé/modifié un `db_schema.xml`
- Après avoir créé/modifié un Data Patch
- Après avoir activé un nouveau module

### 3.3 Compilation DI (Dependency Injection)

```bash
# Compiler le code pour la production
# Génère les interceptors, factories, proxies
php bin/magento setup:di:compile

# Vérifier la compilation
php bin/magento setup:di:compile --dry-run
```

**Quand l'utiliser ?**
- En production (obligatoire)
- En développement : seulement si tu modifies `di.xml` ou si tu as
  des erreurs "Class not found"
- Après avoir ajouté des plugins, préférences, virtual types

### 3.4 Cache

```bash
# Vider tous les caches
php bin/magento cache:flush

# Vider un cache spécifique
php bin/magento cache:clean layout
php bin/magento cache:clean block_html
php bin/magento cache:clean config

# Activer/désactiver un type de cache
php bin/magento cache:enable layout
php bin/magento cache:disable layout

# Voir le statut des caches
php bin/magento cache:status

# Vider le cache de configuration
php bin/magento app:config:dump
```

**Quand utiliser `cache:flush` vs `cache:clean` ?**
- `cache:clean` : vide le cache mais garde la configuration
- `cache:flush` : vide TOUT (plus radical, en dev c'est OK)

### 3.5 Contenu statique

```bash
# Déployer le contenu statique (CSS, JS, fonts)
php bin/magento setup:static-content:deploy -f

# Pour une locale spécifique
php bin/magento setup:static-content:deploy -f fr_FR de_DE

# Pour un thème spécifique
php bin/magento setup:static-content:deploy -f --theme="AlpineCommerce/theme"

# En mode développement : pas besoin de cette commande
# Les fichiers sont générés à la volée
```

### 3.6 Indexation

```bash
# Réindexer tous les indexeurs
php bin/magento indexer:reindex

# Réindexer un indexeur spécifique
php bin/magento indexer:reindex catalogsearch_fulltext

# Voir le statut des indexeurs
php bin/magento indexer:status

# Mettre en mode "update on schedule" (cron)
php bin/magento indexer:set-mode schedule

# Mettre en mode "update on save" (immédiat)
php bin/magento indexer:set-mode realtime
```

### 3.7 Gestion des déploiements

```bash
# Mettre le site en maintenance
php bin/magento maintenance:enable

# Mettre le site hors maintenance
php bin/magento maintenance:disable

# Voir qui est en mode maintenance
php bin/magento maintenance:status

# Autoriser une IP à accéder pendant la maintenance
php bin/magento maintenance:enable --ip=192.168.1.100
```

### 3.8 Admin

```bash
# Créer un utilisateur admin
php bin/magento admin:user:create \
    --admin-name="Admin" \
    --admin-email="admin@example.com" \
    --admin-firstname="Admin" \
    --admin-lastname="User" \
    --admin-user="admin" \
    --admin-password="Admin123!"

# Lister les admins
php bin/magento admin:user:list

# Changer le mot de passe
php bin/magento admin:user:change-password --admin-user=admin

# Supprimer un admin
php bin/magento admin:user:delete --admin-user=admin
```

---

## 4. Commandes utiles pour le développement

### 4.1 Mode de Magento

```bash
# Voir le mode actuel
php bin/magento deploy:mode:show

# Passer en mode développement
php bin/magento deploy:mode:set developer

# Passer en mode production
php bin/magento deploy:mode:set production

# Passer en mode default
php bin/magento deploy:mode:set default
```

| Mode | Usage | Caractéristiques |
|------|-------|-----------------|
| **developer** | Développement local | Pas de compilation, erreurs affichées, cache simplifié |
| **production** | Serveur live | Code compilé, erreurs masquées, cache agressif |
| **default** | Entre les deux | Compilation optionnelle, erreurs affichées |

### 4.2 Informations système

```bash
# Voir la version de Magento
php bin/magento --version

# Voir toutes les commandes disponibles
php bin/magento list

# Voir les infos d'environnement
php bin/magento info:backup:info
```

### 4.3 Gestion des thèmes

```bash
# Voir les thèmes installés
php bin/magento theme:list

# Installer un thème
php bin/magento theme:install AlpineCommerce_theme
```

### 4.4 Gestion des traductions

```bash
# Générer les fichiers de traduction
php bin/magento i18n:collect-phrases -f -o src/app/code/AlpineCommerce/Blog/i18n/fr_FR.csv src/app/code/AlpineCommerce/Blog

# Vérifier les traductions manquantes
php bin/magento i18n:check src/app/code/AlpineCommerce/Blog/i18n/fr_FR.csv
```

---

## 5. Workflow de développement typique

### 5.1 Après avoir modifié un module

```bash
# 1. Activer le module (si nouveau)
php bin/magento module:enable AlpineCommerce_Blog

# 2. Mettre à jour la DB (si db_schema.xml ou data patch modifié)
php bin/magento setup:upgrade

# 3. Compiler (si erreur "class not found" ou modification di.xml)
php bin/magento setup:di:compile

# 4. Vider les caches
php bin/magento cache:flush

# 5. Si tu modifies du JS/CSS :
php bin/magento setup:static-content:deploy -f
# Ou en mode developer : juste vider le cache
```

### 5.2 Après avoir modifié un layout ou un template

```bash
# En mode DEVELOPER : juste vider le cache
php bin/magento cache:flush

# Les modifications sont prises en compte immédiatement
```

### 5.3 Après avoir modifié des fichiers PHP (hors di.xml)

```bash
# En mode DEVELOPER : rien à faire !
# Magento régénère le code automatiquement

# En mode PRODUCTION :
php bin/magento setup:di:compile
```

### 5.4 Workflow complet après un git pull

```bash
# 1. Mettre à jour les dépendances Composer
composer install --no-dev

# 2. Activer les modules (si nouveaux)
php bin/magento module:enable AlpineCommerce_Blog AlpineCommerce_Faq

# 3. Mettre à jour la DB
php bin/magento setup:upgrade

# 4. Compiler
php bin/magento setup:di:compile

# 5. Déployer le contenu statique
php bin/magento setup:static-content:deploy -f

# 6. Réindexer
php bin/magento indexer:reindex

# 7. Vider les caches
php bin/magento cache:flush

# 8. Vérifier le mode
php bin/magento deploy:mode:set developer
```

---

## 6. Les commandes par scénario

### 6.1 "J'ai créé un nouveau module"

```bash
# 1. Créer les fichiers (registration.php, module.xml, etc.)
# 2. Activer le module
php bin/magento module:enable AlpineCommerce_MonModule

# 3. Mettre à jour la DB (si db_schema.xml)
php bin/magento setup:upgrade

# 4. Compiler
php bin/magento setup:di:compile

# 5. Vider les caches
php bin/magento cache:flush
```

### 6.2 "J'ai modifié un template PHTML"

```bash
# En mode developer : juste vider le cache
php bin/magento cache:flush
```

### 6.3 "J'ai modifié un layout XML"

```bash
# En mode developer : juste vider le cache
php bin/magento cache:flush
```

### 6.4 "J'ai ajouté un plugin dans di.xml"

```bash
# Recompiler
php bin/magento setup:di:compile

# Vider les caches
php bin/magento cache:flush
```

### 6.5 "J'ai ajouté une colonne dans db_schema.xml"

```bash
# Mettre à jour la DB
php bin/magento setup:upgrade

# Recompiler
php bin/magento setup:di:compile

# Vider les caches
php bin/magento cache:flush
```

### 6.6 "Le site est lent / les CSS ne se chargent pas"

```bash
# Redéployer le contenu statique
php bin/magento setup:static-content:deploy -f

# Vider les caches
php bin/magento cache:flush

# Réindexer
php bin/magento indexer:reindex
```

### 6.7 "Je veux tester en mode production"

```bash
# Passer en mode production
php bin/magento deploy:mode:set production

# Compiler
php bin/magento setup:di:compile

# Déployer le contenu statique
php bin/magento setup:static-content:deploy -f
```

---

## 7. Erreurs courantes

### 7.1 "Area code is not set"

**Cause** : tu exécutes une commande qui nécessite un area, mais Magento
ne sait pas dans quel contexte s'exécuter.

**Solution** :
```bash
# Ajouter l'option --area
php bin/magento setup:upgrade --area=frontend
```

### 7.2 "Class not found"

**Cause** : le code n'est pas compilé (mode production) ou les interceptors
sont obsolètes.

**Solution** :
```bash
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### 7.3 "Permission denied" sur var/, pub/, generated/

**Cause** : les permissions de fichiers sont incorrectes.

**Solution** :
```bash
# Linux
sudo chown -R 1000:1000 src/var/ src/pub/ src/generated/
sudo chmod -R 755 src/var/ src/pub/ src/generated/

# Ou dans le conteneur
docker compose exec php bash -c "chown -R www-data:www-data /var/www/html/var /var/www/html/pub /var/www/html/generated"
```

### 7.4 "The command did not stop after 10 seconds"

**Cause** : `setup:upgrade` est bloqué (data patch lent, DB inaccessible).

**Solution** :
```bash
# Augmenter le timeout
php -d max_execution_time=600 bin/magento setup:upgrade
```

### 7.5 "Cache storage is not writable"

**Cause** : permissions sur `var/cache/` ou `var/page_cache/`.

**Solution** :
```bash
sudo chmod -R 777 src/var/cache/ src/var/page_cache/
```

---

## 8. Tableau de référence rapide

| Tâche | Commande |
|--------|---------|
| Activer un module | `module:enable AlpineCommerce_Blog` |
| Mettre à jour la DB | `setup:upgrade` |
| Compiler le code | `setup:di:compile` |
| Vider les caches | `cache:flush` |
| Déployer le contenu statique | `setup:static-content:deploy -f` |
| Réindexer | `indexer:reindex` |
| Créer un admin | `admin:user:create` |
| Voir le mode | `deploy:mode:show` |
| Passer en dev | `deploy:mode:set developer` |
| Passer en prod | `deploy:mode:set production` |
| Maintenance ON | `maintenance:enable` |
| Maintenance OFF | `maintenance:disable` |
| Voir toutes les commandes | `list` |

---

## 9. Résumé

| Concept | Explication |
|---------|------------|
| `bin/magento` | CLI Magento, point d'entrée de toutes les commandes |
| `module:enable/disable` | Active/désactive un module |
| `setup:upgrade` | Applique les changements de schema/DB |
| `setup:di:compile` | Génère le code DI (interceptors, factories, proxies) |
| `cache:flush` | Vide tous les caches |
| `setup:static-content:deploy` | Génère CSS, JS, fonts (production) |
| `indexer:reindex` | Réindexe les données (recherche, catégories...) |
| `deploy:mode` | Bascule entre developer / production |
| `maintenance:enable` | Active le mode maintenance |

### Ordre de travail quotidien (développement)

```bash
# Matin : démarrer Docker
docker compose up -d

# Après avoir codé :
php bin/magento cache:flush

# Si erreur "class not found" :
php bin/magento setup:di:compile
php bin/magento cache:flush

# Si modification CSS/JS :
php bin/magento setup:static-content:deploy -f

# Si modification DB (db_schema.xml / data patch) :
php bin/magento setup:upgrade
php bin/magento cache:flush
```

---

*Last updated: 2026-08-11.*
