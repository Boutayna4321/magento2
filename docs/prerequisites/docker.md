# Docker — Complete Guide for AlpineCommerce

> **Target audience**: beginners who have never used Docker. This guide is
> practical: every concept is illustrated with the AlpineCommerce project.

---

## 1. What is Docker?

Docker is a tool that packages an application and all its dependencies
(PHP, MySQL, Elasticsearch, Redis, Nginx, etc.) into a standardized unit
called a **container**.

**Why Docker for Magento?**
- Magento requires a specific PHP version, many PHP extensions, a web server,
  a database, Elasticsearch, Redis… installing all this manually is painful.
- Docker guarantees that every developer on the team has **exactly the same
  environment** (no more "it works on my machine").
- One command starts the whole project: `docker compose up -d`.

---

## 2. Core concepts

### 2.1 Image

An **image** is a template (a snapshot) that contains an OS + software.
Example: the `php:8.2-fpm` image contains Debian + PHP 8.2 + FPM.

### 2.2 Container

A **container** is a running instance of an image. You can start, stop,
and delete a container without affecting the image.

### 2.3 Volume

A **volume** is persistent storage. Containers are ephemeral (deleted = data
lost). Volumes keep data safe:
- MySQL data (`mysql-data` volume)
- Magento `pub/media/`, `var/`, `generated/` (bind mounts)

### 2.4 Network

Docker containers communicate via a private network. The PHP container can
reach MySQL at hostname `mysql`, Nginx reaches PHP-FPM at `php`, etc.

### 2.5 Dockerfile

A `Dockerfile` is a recipe to build a **custom image**. The AlpineCommerce
project uses one to add PHP extensions and configurations needed by Magento.

### 2.6 Docker Compose

`docker-compose.yml` defines **multiple containers** (services) that work
together: php, nginx, mysql, elasticsearch, redis. One command starts them all.

---

## 3. Docker installation

### 3.1 Linux (Ubuntu/Debian)

```bash
# Update packages
sudo apt update

# Install dependencies
sudo apt install -y apt-transport-https ca-certificates curl software-properties-common

# Add Docker GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add repository
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Add your user to the docker group (avoid sudo)
sudo usermod -aG docker $USER
newgrp docker

# Verify
docker --version
docker compose version
```

### 3.2 macOS

Install **Docker Desktop** from https://www.docker.com/products/docker-desktop/
(Apple Silicon: choose the `arm64` version).

### 3.3 Windows

Install **Docker Desktop** with WSL 2 backend:
1. Enable WSL 2 in Windows Features
2. Install Docker Desktop
3. In Settings → General, check "Use WSL 2 based engine"

---

## 4. The AlpineCommerce docker-compose.yml

### 4.1 Services

| Service | Image / Build | Port | Role |
|---------|--------------|------|------|
| `php` | Custom (`Dockerfile`) | 9000 | PHP-FPM 8.2 |
| `nginx` | `nginx:alpine` | 8080 | Web server |
| `mysql` | `mysql:8.0` | 3306 | Database |
| `elasticsearch` | `elasticsearch:8.x` | 9200 | Search engine |
| `redis` | `redis:alpine` | 6379 | Cache / session |

### 4.2 Volumes

| Volume | Type | Purpose |
|--------|------|---------|
| `mysql-data` | Named volume | MySQL data persistence |
| `src/` | Bind mount | Live code sync (host → container) |
| `docker/php/conf/` | Bind mount | Custom PHP configuration |

### 4.3 Networks

All services are on the `magento` network. Containers reach each other by
service name (e.g., `mysql`, `php`).

---

## 5. Essential Docker commands

```bash
# Start all services in background
docker compose up -d

# Stop all services
docker compose stop

# Stop and delete containers (keeps volumes)
docker compose down

# Stop, delete containers AND volumes (WARNING: data lost)
docker compose down -v

# View logs of all services
docker compose logs -f

# View logs of a specific service
docker compose logs -f php

# Execute a command inside a running container
docker compose exec php bash
docker compose exec mysql mysql -u root -p

# Rebuild the PHP image after Dockerfile changes
docker compose build php

# Restart a service
docker compose restart php

# List running containers
docker compose ps

# List volumes
docker compose ls -v
```

---

## 6. Practical workflow with AlpineCommerce

### 6.1 First start

```bash
cd /home/cartware/Desktop/magento

# Start containers
docker compose up -d

# Wait for MySQL to be ready (10-15 seconds)
sleep 15

# Install Magento (first time only)
./scripts/install.sh
```

### 6.2 Daily development

```bash
# Start
docker compose up -d

# Access the store
# Storefront: http://localhost:8080
# Admin:     http://localhost:8080/admin

# View logs if something is wrong
docker compose logs -f nginx
docker compose logs -f php

# Enter the PHP container to run Magento CLI
docker compose exec php bash
# Inside container:
php bin/magento cache:flush
php bin/magento setup:upgrade
exit

# Stop at the end of the day
docker compose stop
```

### 6.3 The .env file

`.env.example` → `.env` (copy it). This file contains:
- Database credentials (`MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`)
- Magento auth keys (`MAGENTO_PUBLIC_KEY`, `MAGENTO_PRIVATE_KEY`)
- PHP memory limits, Elasticsearch memory

> **Security**: `.env` is in `.gitignore`. Never commit it.

---

## 7. Common issues and fixes

### 7.1 Port 8080 already in use

```bash
# Find what uses the port
sudo lsof -i :8080

# Either stop the conflicting service, or change the port in docker-compose.yml:
# ports:
#   - "8081:80"
```

### 7.2 MySQL refuses to start

```bash
# Check logs
docker compose logs mysql

# Common cause: volume corruption
docker compose down -v
docker compose up -d
# WARNING: you lose all data. Use only for local dev.
```

### 7.3 Elasticsearch needs more memory

```bash
# In .env, increase:
ES_JAVA_OPTS=-Xmx2g -Xms2g
```

### 7.4 Permission errors on `var/`, `pub/media/`, `generated/`

```bash
# Fix ownership (Linux/macOS)
sudo chown -R 1000:1000 src/var/ src/pub/media/ src/generated/
# Or enter the container and run:
docker compose exec php bash -c "chown -R www-data:www-data /var/www/html/var /var/www/html/pub/media /var/www/html/generated"
```

---

## 8. Dockerfile explained

The project's `Dockerfile` extends `php:8.2-fpm` and adds:

```dockerfile
# Base image
FROM php:8.2-fpm

# Install system dependencies (libzip-dev, libicu-dev, etc.)
RUN apt-get update && apt-get install -y \
    libzip-dev libicu-dev libxml2-dev libxslt1-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath intl pdo_mysql zip xsl gd opcache sockets \
    && docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy custom PHP configuration
COPY docker/php/conf/ /usr/local/etc/php/conf.d/

WORKDIR /var/www/html
```

**What this does:**
- Installs all PHP extensions Magento needs (`intl`, `gd`, `zip`, `xsl`, etc.)
- Installs Composer (PHP package manager)
- Adds custom `php.ini` settings (memory limits, upload sizes)

---

## 9. Key takeaways

| Concept | Analogy | In AlpineCommerce |
|---------|---------|-------------------|
| Image | A cake recipe | `php:8.2-fpm`, `nginx:alpine` |
| Container | A baked cake | Running PHP, MySQL, Nginx |
| Volume | A refrigerator | MySQL data, media files |
| Network | A telephone network | Containers talking to each other |
| Dockerfile | A custom recipe | `Dockerfile` at project root |
| Compose | A meal plan | `docker-compose.yml` (5 services) |

---

## 10. Next steps

Once Docker is running:
1. Start containers: `docker compose up -d`
2. Run the install script: `./scripts/install.sh`
3. Access http://localhost:8080
4. Learn PHP OOP: see `docs/prerequisites/php-oop.md`

---

*Last updated: 2026-08-11.*
