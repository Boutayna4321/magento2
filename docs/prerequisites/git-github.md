# Git & GitHub — Complete Guide for Beginners

> **Target audience**: developers new to version control. Every concept is
> illustrated with the AlpineCommerce project.

---

## 1. What is Git?

**Git** is a **version control system**. It records every change made to your
code, so you can:
- Go back to a previous version if you break something
- Work on multiple features simultaneously (branches)
- Collaborate with other developers without overwriting each other's work

**GitHub** is a website that hosts Git repositories online. It adds:
- A web interface to browse code
- Pull Requests (code review)
- Issues (bug tracking)
- Actions (CI/CD)

---

## 2. Installation

### 2.1 Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y git
git --version
```

### 2.2 macOS

```bash
# With Homebrew
brew install git

# Or download from https://git-scm.com/download/mac
```

### 2.3 Windows

Download from https://git-scm.com/download/win

---

## 3. First configuration

```bash
# Set your identity (required for commits)
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Set default editor (nano is simple for beginners)
git config --global core.editor nano

# Verify
git config --list
```

---

## 4. Core concepts

### 4.1 Repository (repo)

A **repository** is a folder tracked by Git. The AlpineCommerce project is
a Git repository.

```bash
cd /home/cartware/Desktop/magento
git status  # tells you if this folder is a Git repo
```

### 4.2 Commit

A **commit** is a snapshot of your code at a specific moment. Each commit has:
- A unique ID (hash)
- An author
- A date
- A message explaining what changed

```bash
git add README.md
git commit -m "docs: update README with new module list"
```

### 4.3 Branch

A **branch** is an independent line of development. The default branch is
`main` (or `master`).

```
main ─── A ─── B ─── C
               \
feature-x       D ─── E
```

### 4.4 Staging area (index)

Before committing, you must **stage** the files you want to include in the
commit. This lets you commit only selected files.

```
Working directory → git add → Staging area → git commit → Repository
```

---

## 5. Essential commands

### 5.1 Starting a project

```bash
# Clone an existing repo from GitHub
git clone https://github.com/Boutayna4321/magento2.git
cd magento2
```

### 5.2 Daily workflow

```bash
# See what changed
git status

# See the actual changes (diff)
git diff README.md

# Stage a file
git add README.md

# Stage all changes
git add .

# Commit staged changes
git commit -m "docs: add StoreSetup module documentation"

# See commit history
git log --oneline
```

### 5.3 Branching

```bash
# Create a new branch
git checkout -b feature/add-new-module

# Switch to existing branch
git checkout main

# List branches
git branch -a

# Delete a branch
git branch -d feature/add-new-module
```

### 5.4 Syncing with GitHub

```bash
# Download changes from GitHub
git pull origin main

# Upload your commits to GitHub
git push origin main

# Push a new branch
git push origin feature/add-new-module
```

---

## 6. Git workflow for AlpineCommerce

### 6.1 Before starting work

```bash
git checkout main
git pull origin main
```

Always start from the latest `main`.

### 6.2 Create a feature branch

```bash
git checkout -b docs/add-prerequisites
```

### 6.3 Work and commit often

```bash
# Make changes to files...

git add docs/prerequisites/docker.md
git commit -m "docs: add Docker guide"

git add docs/prerequisites/php-oop.md
git commit -m "docs: add PHP OOP guide"

git push origin docs/add-prerequisites
```

### 6.4 Create a Pull Request on GitHub

1. Go to https://github.com/Boutayna4321/magento2
2. Click **"Compare & pull request"**
3. Write a title and description
4. Click **"Create pull request"**

### 6.5 Review and merge

- The team reviews the code
- Changes are discussed
- Once approved: **"Merge pull request"**
- Delete the feature branch

---

## 7. Useful commands

```bash
# See commit history with graph
git log --oneline --graph --all

# See who changed what in a file
git blame README.md

# Discard changes in working directory (CAREFUL!)
git checkout -- README.md

# Undo last commit (keep changes in working directory)
git reset --soft HEAD~1

# Unstage a file (keep changes in working directory)
git reset HEAD README.md

# Stash uncommitted changes temporarily
git stash
git stash pop

# See which branch you're on
git branch --show-current
```

---

## 8. .gitignore

The `.gitignore` file tells Git which files **not to track**. The
AlpineCommerce project ignores:

- `vendor/` — Composer dependencies (840 MB, regenerated)
- `var/` — Cache, sessions, logs
- `pub/media/` — Uploaded images
- `pub/static/` — Static assets
- `generated/` — Generated code
- `.env` — Secrets (passwords, API keys)

**Rule**: never commit generated files, secrets, or large binaries.

---

## 9. Common beginner mistakes

| Mistake | Fix |
|---------|-----|
| Committed secrets (`.env`, passwords) | Rotate credentials immediately, add to `.gitignore`, force-push to rewrite history |
| `git add .` committed unwanted files | Use `.gitignore`, or `git reset HEAD <file>` to unstage |
| Merge conflicts | Read the conflict markers (`<<<<<<<`), choose the correct code, `git add`, `git commit` |
| Accidentally deleted a branch | `git reflog` to find the commit, `git checkout -b <branch> <commit>` |
| Forgot to pull before pushing | `git pull --rebase origin main`, then `git push` |

---

## 10. GitHub basics

### 10.1 Fork vs Clone

| Action | Result |
|--------|--------|
| `git clone` | Download repo to your machine |
| Fork (on GitHub) | Create your own copy of the repo on GitHub |

### 10.2 Pull Request (PR)

A **Pull Request** is a proposal to merge changes from your branch into
another branch (usually `main`). It enables:
- Code review
- Automated tests (CI)
- Discussion before merging

### 10.3 Issues

**Issues** track bugs, tasks, and feature requests. Each issue has:
- A title and description
- Labels (`bug`, `enhancement`, `documentation`)
- Assignees
- Comments

### 10.4 Actions (CI)

GitHub Actions run automated checks on every push/PR:
- PHP lint
- Unit tests
- XML validation
- Docker build

Check `.github/workflows/` in the repo.

---

## 11. Summary

| Concept | Git equivalent | GitHub equivalent |
|---------|---------------|-------------------|
| Project folder | Repository (local) | Repository (remote) |
| Snapshot | Commit | Commit (visible on GitHub) |
| Parallel work | Branch | Branch |
| Code review | — | Pull Request |
| Bug tracking | — | Issue |
| Automated tests | — | Action / Workflow |
| Ignore files | `.gitignore` | `.gitignore` |

---

## 12. Next steps

- Read the project's `CONTRIBUTING.md` (if it exists)
- Browse `.github/workflows/` to see CI checks
- Continue with `docs/prerequisites/magento-intro.md`

---

*Last updated: 2026-08-11.*
