# CI/CD — Complete Guide for Beginners

> **Target audience**: beginners who want to understand **CI/CD** and how
> it applies to the AlpineCommerce project.

---

## 1. What is CI/CD?

### 1.1 CI = Continuous Integration

**CI** automatically verifies that every code change is
correct **before** it is accepted into the project.

**Without CI**: a developer commits code that breaks everything → everyone
waits for the bug to be noticed → urgent fix.

**With CI**: as soon as a developer proposes a change (Pull Request),
robots run automatic tests:
- Is the PHP code well written?
- Are the XML files valid?
- Does the application compile without errors?
- Do unit tests pass?

If a test fails, the PR is **blocked** until fixed.

### 1.2 CD = Continuous Deployment

**CD** goes further: when the code is validated ( merged into `main` ),
it is **automatically deployed** to an environment (staging, production).

**Without CD**: a developer merges → you have to log into the server →
run commands manually → risk of human error.

**With CD**: merge → deployment happens by itself → in a few minutes
the site is up to date.

---

## 2. Analogy: the car factory

| Step | CI/CD | Analogy |
|------|-------|---------|
| Developer writes code | — | Worker assembles a door |
| Pull Request | **CI** | The door goes through quality control |
| Automatic tests | **CI** | Checks: dimensions, paint, mechanism |
| If test fails | **CI blocked** | The door is rejected, back to the workshop |
| Merge into `main` | **CI passed** | The door is validated |
| Auto deployment | **CD** | The door is installed on the car automatically |

---

## 3. GitHub Actions — GitHub's CI/CD

GitHub offers **GitHub Actions**: automated workflows that
run on GitHub's servers when:
- You push code
- You open a Pull Request
- You create a tag (version)

Workflows are defined in YAML files in
`.github/workflows/`.

---

## 4. AlpineCommerce's CI workflow

### 4.1 File structure

```
.github/
└── workflows/
    ├── ci.yml    # Automatic tests (lint, XML, Docker, secrets)
    └── cd.yml    # Build + push Docker image on main
```

### 4.2 When does it run?

```yaml
on:
  push:
    branches: [ main ]          # On each push to main
  pull_request:
    branches: [ main ]          # On each PR to main
```

### 4.3 CI jobs (steps)

#### Job 1: PHP Lint
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

**What it does**: verifies that each PHP file is syntactically
correct (no parsing error).

**Duration**: ~30 seconds

#### Job 2: XML Validation
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

**What it does**: verifies that all XML files are well-formed
(correct open/close tags, no illegal characters).

**Duration**: ~20 seconds

#### Job 3: Composer Validate
```yaml
  composer-validate:
    name: Composer Validate
    steps:
      - run: composer validate --no-check-publish --working-dir=src
```

**What it does**: verifies that `composer.json` is valid (correct
dependencies, valid JSON format).

**Duration**: ~10 seconds

#### Job 4: Docker Build Test
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

**What it does**: builds the Docker image to verify there are no
errors in the `Dockerfile` (missing PHP extension, etc.).

**Duration**: ~2-3 minutes (first time), then ~30 seconds (cache)

#### Job 5: Secret Scanning
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

**What it does**: scans the code to detect passwords,
API keys, GitHub tokens, etc. that may have been committed by mistake.

**Duration**: ~30 seconds

#### Job 6: Markdown Lint
```yaml
  markdown-lint:
    name: Markdown Lint
    steps:
      - uses: DavidAnson/markdownlint-cli2-action@v19
        with:
          config: markdownlint.json
          globs: '**/*.md'
```

**What it does**: verifies that `.md` files follow formatting
rules (line length, well-structured headings, no trailing
spaces, etc.).

**Duration**: ~10 seconds

---

## 5. AlpineCommerce's CD workflow

### 5.1 When does it run?

```yaml
name: CD — Continuous Deployment

on:
  push:
    branches: [ main ]
```

As soon as someone pushes to `main` (after the PR has been merged).

### 5.2 What it does

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

**What it does**:
1. Builds the Docker image from the `Dockerfile`
2. Pushes it to **GitHub Container Registry** (`ghcr.io`)
3. Tags the image with:
   - `latest` (always the latest version on main)
   - The commit hash (e.g. `main-a1b2c3d`)
   - The branch name

**Result**: the image is available to be deployed on any
server.

---

## 6. How does it work in practice?

### 6.1 Scenario 1: Pull Request

```
1. Alice creates a branch: feature/add-blog-search
2. Alice commits her changes
3. Alice pushes to GitHub
4. Alice opens a Pull Request to main
5. GitHub Actions triggers automatically (CI)
6. Jobs run in parallel:
    - PHP Lint
    - XML Validation
    - Composer Validate
    - Docker Build
    - Secret Scan
    - Markdown Lint
7. If ALL jobs pass → ✅ the PR has a green "All checks passed" badge
8. Bob reviews the code
9. Bob clicks "Merge pull request"
10. The code is merged into main
11. The CD workflow triggers automatically
12. A new Docker image is built and pushed to ghcr.io
13. ✅ The project is deployed
```

### 6.2 Scenario 2: Bug discovered by CI

```
1. Alice commits code with an XML error
2. Alice pushes and opens a PR
3. The XML Validation job fails:
    ❌ Error: Opening and ending tag mismatch in layout XML
4. GitHub displays a red badge ❌
5. Alice sees the error in the workflow logs
6. Alice fixes the XML
7. Alice pushes the fix
8. The workflow relaunches
9. This time, all jobs pass ✅
10. The PR can be merged
```

---

## 7. Key concepts

### 7.1 Workflow

A **workflow** is a YAML file that defines:
- When it runs (`on: push`, `on: pull_request`)
- Which jobs to execute
- In what order
- On what type of machine (`runs-on: ubuntu-latest`)

### 7.2 Job

A **job** is a step in the workflow. Multiple jobs can run
**in parallel** to save time.

```yaml
jobs:
  php-lint:      # Job 1
  xml-validation: # Job 2 (runs at the same time as Job 1)
  docker-build:  # Job 3 (runs at the same time as Job 1 and 2)
```

### 7.3 Step (étape)

A **step** is a command inside a job.

```yaml
jobs:
  php-lint:
    steps:
      - uses: actions/checkout@v4        # Step 1: download code
      - uses: shivammathur/setup-php@v2   # Step 2: install PHP
      - run: php -l file.php              # Step 3: execute the command
```

### 7.4 Runner

A **runner** is the virtual machine that executes the workflow.
- `ubuntu-latest`: Linux (most common)
- `windows-latest`: Windows
- `macos-latest`: macOS

### 7.5 Action

An **action** is a reusable package that does a specific task.

```yaml
- uses: actions/checkout@v4           # Action: downloads code
- uses: docker/build-push-action@v5   # Action: build + push Docker
- uses: trufflesecurity/trufflehog@main # Action: secret scan
```

There are thousands of actions on the GitHub Marketplace.

### 7.6 Secret

A **secret** is a sensitive variable (password, API key, token)
stored encrypted. It cannot be read in logs.

```yaml
- uses: docker/login-action@v3
  with:
    password: ${{ secrets.GITHUB_TOKEN }}  # Never displayed in logs
```

---

## 8. Read a CI/CD workflow (step by step)

```yaml
name: CI — Continuous Integration
#       ↑ Workflow name

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]
#       ↑ Trigger: when to run the workflow

jobs:
  php-lint:
    name: PHP Lint
#         ↑ Job name (displayed in GitHub interface)

    runs-on: ubuntu-latest
#            ↑ Virtual machine type

    steps:
      - uses: actions/checkout@v4
#             ↑ Action: downloads repo code

      - uses: shivammathur/setup-php@v2
#             ↑ Action: installs PHP 8.2
        with:
          php-version: '8.2'

      - name: Lint AlpineCommerce PHP files
#             ↑ Step name (displayed in logs)
        run: |
#           ↑ Command to execute
          find src/app/code/AlpineCommerce -name '*.php' -print0 | xargs -0 -n1 php -l
#           ↑ Bash command: find all PHP files and check syntax
```

---

## 9. Best practices

| Practice | Why |
|----------|-----|
| **Fast CI** (< 10 min) | Developers shouldn't wait long to merge |
| **Parallel tests** | Multiple jobs at once = time saved |
| **Dependency caching** | Docker, Composer, npm: avoid re-downloading each time |
| **Encrypted secrets** | NEVER hardcode passwords in a workflow |
| **Notifications** | Alert the team when CI fails (Slack, email) |
| **README badges** | Display CI status in the project README |

---

## 10. Status badge

Add this to the project's `README.md`:

```markdown
![CI](https://github.com/Boutayna4321/magento2/actions/workflows/ci.yml/badge.svg)
```

This displays a green (✅) or red (❌) badge directly in the README
to show whether the latest CI succeeded or failed.

---

## 11. Summary

| Concept | Definition | In AlpineCommerce |
|---------|-----------|---------------------|
| **CI** | Automatic tests at each modification | `ci.yml`: lint, XML, Docker, secrets |
| **CD** | Automatic deployment after merge | `cd.yml`: build + push Docker on main |
| **Workflow** | YAML file that defines CI/CD | `.github/workflows/ci.yml` |
| **Job** | Workflow step (can be parallel) | `php-lint`, `xml-validation`, `docker-build` |
| **Step** | Command inside a job | `php -l file.php` |
| **Action** | Reusable package | `actions/checkout@v4` |
| **Runner** | Machine that executes the workflow | `ubuntu-latest` |
| **Secret** | Encrypted sensitive variable | `secrets.GITHUB_TOKEN` |

---

## 12. Next steps

- Observe workflows in the repo's **Actions** tab
- Read logs when a job fails (it is formative)
- Add AlpineCommerce unit tests in `src/app/code/AlpineCommerce/*/Test/`
- Add a PHPStan job in the CI for static analysis
- Configure notifications (Slack/Discord) for CI alerts

---

*Last updated: 2026-08-11.*
