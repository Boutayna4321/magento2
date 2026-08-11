# Magento 2 — Debug & Workflow

> **Objectif** : savoir comment trouver et corriger les erreurs dans
> Magento 2. Ce guide couvre les logs, le mode developer, Xdebug, et les
> erreurs les plus courantes.

---

## 1. Les logs Magento

### 1.1 Où se trouvent les logs

```
src/var/
├── log/                          ← Logs système
│   ├── system.log                ← Logs généraux
│   ├── exception.log             ← Exceptions PHP
│   ├── debug.log                 ← Logs de debug (si activés)
│   └── {module_name}.log         ← Logs spécifiques à un module
├── report/                       ← Rapports d'erreur PHP
│   └── 20260811120000_error_id   ← Fichier d'erreur avec date
├── cache/                        ← Cache
├── page_cache/                   ← Cache des pages
└── session/                      ← Sessions utilisateur
```

### 1.2 Activer les logs

```bash
# Vérifier si les logs sont activés
php bin/magento config:show dev/log/active

# Activer les logs
php bin/magento config:set dev/log/active 1

# Désactiver les logs (production)
php bin/magento config:set dev/log/active 0
```

### 1.3 Lire les logs

```bash
# Voir les dernières lignes de system.log
tail -f src/var/log/system.log

# Voir les dernières lignes d'exception.log
tail -f src/var/log/exception.log

# Rechercher un mot-clé
grep -i "customer" src/var/log/system.log

# Voir toutes les erreurs du jour
ls -la src/var/report/
cat src/var/report/20260811120000_error_id
```

### 1.4 Écrire dans les logs depuis le code

```php
// Dans un Block, Model, Controller, Observer, Plugin...
use Psr\Log\LoggerInterface;

class MyClass
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function doSomething(): void
    {
        // Info (niveau bas)
        $this->logger->info('Processing customer ID: ' . $customerId);
        
        // Warning (niveau moyen)
        $this->logger->warning('Customer not found, using default');
        
        // Error (niveau haut)
        $this->logger->error('Failed to save order: ' . $e->getMessage());
        
        // Debug (seulement si debug activé)
        $this->logger->debug('SQL query: ' . $sql);
    }
}
```

### 1.5 Les logs dans AlpineCommerce

```php
// StoreSetup Observer
$this->logger->error('Training CustomerLogin: ' . $e->getMessage());
$this->logger->info("Training DataPatch: Created store '$code' (ID: {$store->getId()})");
```

---

## 2. Le mode Developer

### 2.1 Activer le mode developer

```bash
# Vérifier le mode actuel
php bin/magento deploy:mode:show

# Passer en mode developer
php bin/magento deploy:mode:set developer

# Passer en mode production
php bin/magento deploy:mode:set production
```

### 2.2 Différences entre les modes

| Élément | Developer | Production |
|---------|-----------|------------|
| Erreurs PHP | Affichées à l'écran | Masquées (page blanche) |
| Logs | Détail maximal | Minimal |
| Cache | Simplifié | Complet |
| Contenu statique | Généré à la volée | Pré-généré |
| Compilation DI | À la demande | Pré-compilé |
| Templates | Fichiers source | Fichiers compilés |

### 2.3 Le mode developer est obligatoire pour

- Développer de nouvelles fonctionnalités
- Débugger des erreurs
- Travailler sur les templates et layouts
- Tester des modules

---

## 3. Xdebug — Debug pas à pas

### 3.1 Qu'est-ce que Xdebug ?

Xdebug est une extension PHP qui permet de :
- Mettre des **breakpoints** (points d'arrêt)
- Exécuter le code **ligne par ligne**
- Inspecter les **variables** à chaque étape
- Voir la **pile d'appels** (qui appelle qui)

### 3.2 Configuration dans Docker

```yaml
# docker-compose.yml (extrait)
services:
  php:
    build:
      context: ./php
      dockerfile: Dockerfile
    volumes:
      - ./src:/var/www/html
    environment:
      - XDEBUG_MODE=develop,debug
      - XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003
```

### 3.3 Configuration VS Code

`.vscode/launch.json` :
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug (Docker)",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}/src"
            }
        }
    ]
}
```

### 3.4 Utilisation

1. Dans VS Code, cliquer sur **Run** → **Start Debugging** (F5)
2. Dans le code PHP, ajouter un breakpoint (clic à gauche du numéro de ligne)
3. Dans le navigateur, déclencher l'action (clic sur un bouton, chargement de page)
4. VS Code s'arrête sur le breakpoint
5. Examiner les variables, avancer pas à pas (F10 = step over, F11 = step into)

---

## 4. Erreurs courantes et solutions

### 4.1 Page blanche (HTTP 500)

**Cause** : erreur PHP fatale.

**Solution** :
```bash
# 1. Vérifier les logs
tail -f src/var/log/exception.log
tail -f src/var/log/system.log

# 2. Activer l'affichage des erreurs
php bin/magento config:set dev/debug/error_hints 1
php bin/magento cache:flush

# 3. Vérifier la syntaxe PHP
php -l src/app/code/AlpineCommerce/Blog/Model/PostRepository.php
```

### 4.2 "Class not found"

**Cause** : classe pas compilée ou mauvaise namespace.

**Solution** :
```bash
# 1. Vérifier le namespace dans le fichier
#    namespace AlpineCommerce\Blog\Model;

# 2. Vérifier le chemin
#    src/app/code/AlpineCommerce/Blog/Model/PostRepository.php

# 3. Compiler
php bin/magento setup:di:compile

# 4. Vider les caches
php bin/magento cache:flush
```

### 4.3 "No such entity"

**Cause** : entité non trouvée en base (mauvais ID, entité supprimée).

**Solution** :
```php
// Vérifier directement en DB
mysql -u root -p magento2 -e "SELECT * FROM alphacommerce_blog_post WHERE entity_id = 1;"

// Ou dans le code, vérifier avant d'utiliser
try {
    $post = $postRepository->getById($id);
} catch (NoSuchEntityException $e) {
    $this->logger->error('Post not found: ' . $id);
    // Gérer le cas : afficher un message, rediriger, etc.
}
```

### 4.4 Layout XML ignoré

**Cause** : mauvais nom de fichier, mauvais nom de block, cache.

**Solution** :
```bash
# 1. Vérifier le nom du fichier
#    URL: /blog → fichier: blog_index_index.xml ✓

# 2. Activer le template hints (voir section 6)

# 3. Vider le cache
php bin/magento cache:flush

# 4. Vérifier les logs pour erreurs XML
grep -i "xml" src/var/log/system.log
```

### 4.5 "Access denied" (admin)

**Cause** : ACL manquante ou rôle admin sans permission.

**Solution** :
```bash
# 1. Vérifier l'ACL dans etc/acl.xml
# 2. Assigner le rôle dans Stores > Settings > Admin Users > User Roles
# 3. Se déconnecter/reconnecter (les ACL sont chargées au login)
```

### 4.6 "The request is not valid"

**Cause** : formulaire avec clé de sécurité (form key) manquante ou expirée.

**Solution** :
```php
// Dans le template .phtml, ajouter la form key
<input type="hidden" name="form_key" value="<?= $block->getFormKey() ?>">
```

---

## 5. Outils de debug

### 5.1 Template Hints (frontend)

Affiche le nom des blocs et templates utilisés sur chaque zone de la page :

```bash
# Activer via CLI
php bin/magento config:set dev/template/allow_symlink 1
php bin/magento cache:flush
```

Puis dans l'admin : **Stores > Configuration > Advanced > Developer > Debug >
Enabled Template Paths for Storefront = Yes**

### 5.2 Block Hints (admin)

Affiche les noms des blocs dans l'admin :

```bash
php bin/magento config:set dev/debug/template_hints_admin 1
php bin/magento cache:flush
```

### 5.3 Profiler

```bash
# Activer le profiler
php bin/magento config:set dev/profiler/enabled 1

# Les temps de chargement de chaque block apparaissent en bas de page
```

### 5.4 Developer Mode dans le .htaccess

```apache
# .htaccess à la racine de Magento
SetEnv MAGE_MODE developer
```

---

## 6. Débugger le JavaScript

### 6.1 Chrome DevTools

```
F12 → Console
```

**Voir les modules RequireJS** :
```js
require.s.contexts._.defined
// Affiche tous les modules chargés
```

**Tester un module** :
```js
require(['AlpineCommerce_StorePickup/js/view/store-pickup'], function (Module) {
    console.log(Module);
});
```

**Inspecter un observable KO** :
```js
// Si tu as accès au composant dans la console :
$t('Pickup store saved.');
```

### 6.2 Erreurs courantes JS

| Erreur | Cause | Solution |
|--------|-------|----------|
| `Uncaught Error: Module name ... has not been loaded yet` | Dépendance mal orthographiée | Vérifier le nom dans `define([...])` |
| `$ is not a function` | jQuery mal injecté | Vérifier l'ordre des paramètres |
| `ko is not defined` | Knockout pas déclaré | Ajouter `'ko'` dans `define([...])` |
| `define is not defined` | Fichier pas chargé via RequireJS | Utiliser `define()`, pas de `<script>` inline |

---

## 7. Débugger le PHP

### 7.1 Vérifier ce qui est chargé

```bash
# Voir les modules actifs
php bin/magento module:status

# Voir la config d'un module
php bin/magento config:show AlpineCommerce_Blog

# Voir les routes d'un module
php bin/magento route:list | grep blog
```

### 7.2 Tester une requête REST

```bash
# GET
curl -H "Authorization: Bearer <token>" \
     https://localhost:8080/rest/V1/alphacommerce/blog/posts

# POST
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{"title":"Test","content":"Hello"}' \
     https://localhost:8080/rest/V1/alphacommerce/blog/posts
```

### 7.3 Vérifier la base de données

```bash
# Se connecter à MySQL
docker compose exec mysql mysql -u root -proot123 magento2

# Voir les tables d'un module
SHOW TABLES LIKE 'alphacommerce_%';

# Voir les données
SELECT * FROM alphacommerce_blog_post LIMIT 10;

# Voir la configuration
SELECT * FROM core_config_data WHERE path LIKE 'blog/%';
```

### 7.4 Tester un Data Patch

```bash
# Voir les patches appliqués
php bin/magento setup:db-data:status

# Appliquer un patch spécifique
php bin/magento setup:upgrade --keep-generated
```

---

## 8. Workflow de debug recommandé

### 8.1 Face à une erreur 500

```
1. Lire l'erreur affichée (si mode developer)
   ou consulter src/var/log/exception.log

2. Identifier le fichier et la ligne fautifs

3. Si c'est une erreur PHP :
   - Vérifier la syntaxe : php -l fichier.php
   - Vérifier les dépendances (use statements)
   - Vérifier les injections DI (constructeur)

4. Si c'est une erreur XML :
   - Valider le fichier : xmllint --noout fichier.xml
   - Vérifier les noms des attributs (case sensitive)

5. Corriger → recompiler si nécessaire → vider le cache
```

### 8.2 Face à un affichage incorrect

```
1. Activer les template hints
2. Identifier quel template est utilisé
3. Identifier quel block fournit les données
4. Vérifier le layout XML qui crée ce block
5. Vérifier le Block PHP (méthodes getData)
6. Vérifier le template .phtml (boucles, conditions)
```

### 8.3 Face à une erreur AJAX/JS

```
1. Ouvrir la console navigateur (F12)
2. Voir les erreurs dans l'onglet "Console"
3. Voir la requête AJAX dans l'onglet "Network"
4. Vérifier la réponse (status, body)
5. Vérifier le code JS (erreur RequireJS, KO, jQuery)
6. Utiliser require([...], function(...){ console.log(...); }) pour tester
```

---

## 9. Checklist de debug

| Problème | Vérifier |
|----------|----------|
| Page blanche | `exception.log`, mode developer, `php -l` |
| Erreur 500 | `exception.log`, `report/` |
| Template pas trouvé | Nom du fichier, `template` dans layout XML |
| Block invisible | `referenceContainer` correct, `before`/`after`, cache |
| Données vides | Block PHP (`getData`), DataProvider, Repository |
| AJAX échoue | Console réseau, URL, headers, token |
| Module pas activé | `module:status`, `config.php` |
| Erreur "class not found" | Namespace, chemin, `setup:di:compile` |
| Cache pas à jour | `cache:flush` |
| Layout ignoré | Nom de fichier, XML valide, cache |
| Permission denied | `var/`, `pub/`, `generated/` ownership |

---

## 10. Résumé

| Outil | Usage |
|-------|-------|
| `src/var/log/system.log` | Logs généraux |
| `src/var/log/exception.log` | Exceptions PHP |
| `src/var/report/` | Rapports d'erreur détaillés |
| `php bin/magento deploy:mode:set developer` | Activer le mode debug |
| Template Hints | Voir quel template/bloc est utilisé |
| Xdebug | Debug pas à pas du PHP |
| Chrome DevTools | Debug JS (console, réseau, RequireJS) |
| `php -l` | Vérifier la syntaxe PHP |
| `grep` | Rechercher dans les logs |
| `tail -f` | Suivre les logs en temps réel |

---

*Last updated: 2026-08-11.*
