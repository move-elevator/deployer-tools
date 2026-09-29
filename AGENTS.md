# AGENTS.md

Guidance for coding agents working in this repository.

## Project overview

Deployer Tools (`move-elevator/deployer-tools`) is a PHP library of [Deployer](https://deployer.org/) recipes, maintained for move:elevator projects and not intended for general external use. It bundles reusable deployment recipes for TYPO3 and Symfony applications plus standalone tasks (notifications, database backup, security checks, requirement checks, feature-branch deployment).

The repository is the library itself: task definitions are loaded via `require_once` into a consuming project's `deploy.php`. There is no application runtime here.

- PHP `^8.1`, `deployer/deployer` `^8.0`, `sourcebroker/deployer-extended` `^24.2`, `sourcebroker/deployer-loader` `^5.0`
- Optional dependency: `mittwald/api-client`

## Structure

Two kinds of code with different conventions:

- `src/` holds PSR-4 autoloaded classes (`MoveElevator\DeployerTools\{Database,Utility}`), typed, with `declare(strict_types=1)`.
- `deployer/` holds procedural Deployer recipe files (`namespace Deployer;`, `task()`, `set()`, `get()`, `run()`), chained via `require_once`. Only the `Loader` classes in `deployer/symfony/` and `deployer/typo3/` are autoloaded.
- `docs/` holds one Markdown file per recipe (`FEATURE.md`, `DATABASE.md`, `TYPO3.md`, and others).
- `autoload.php` in the root is the entrypoint for the app-agnostic recipes.

Recipes under `deployer/`: `backup`, `build`, `debug`, `dev`, `feature`, `notification`, `requirements`, `rsync`, `security`, `symfony`, `sync`, `typo3`. Recipe directories are structured consistently where applicable:

- `config/set.php`: Deployer `set()` defaults
- `config/options.php`: CLI options and arguments
- `task/*.php`: one file per `task('name', ...)`
- `functions.php`: recipe-local helpers
- `autoload.php`: entrypoint that chains config and tasks in dependency order
- `example/`: reference `deploy.php.dist`, `hosts.yaml` and templates
- `dist/`: scaffolding shipped to the target server

`symfony` and `typo3` are application-specific recipes. Their `Loader.php` bootstraps `recipe/common.php` and `sourcebroker/deployer-extended`. Their `autoload.php` resolves the vendor root explicitly because it is required from inside `vendor/`.

Key classes in `src/`:

- `Database/`: `ManagerInterface` with three interchangeable managers (`Root`, `Simple`, `MittwaldApi`), selected via `set('database_manager_type', ...)`. Configuration is documented in `docs/DATABASE.md`.
- `Utility/`: `EnvUtility` (parses a remote `.env` over SSH), `VarUtility` (resolves database credentials by convention), `FeatureUtility`.

Feature-branch deployment (`deployer/feature/`, documented in `docs/FEATURE.md`) runs isolated instances of one application on a host, keyed by `--feature=`.

## Development commands

```bash
composer install
composer lint          # composer normalize --dry-run, editorconfig-cli (ec), php-cs-fixer --dry-run
composer fix           # same tools, applying fixes
composer sca:php       # phpstan analyse --memory-limit=2G
composer migration     # rector process -c rector.php
```

## Testing

There is no test suite (no `phpunit.xml`, no `tests/` directory). Do not invent test commands.

## Code style and static analysis

- PHP CS Fixer (`.php-cs-fixer.php`): `@PSR12`, short array syntax, trailing commas in multiline, Yoda style.
- PHPStan level 5. `src/Database/Manager/MittwaldApi.php` is excluded because it needs the optional `mittwald/api-client`.
- Rector via `eliashaeussler/rector-config`, PHP 8.1 target.
- All three tools are scoped to `src/`. The `deployer/` recipe scripts are not analysed.
- EditorConfig is enforced through `ec` (`.editorconfig`).

CI (`.github/workflows/cgl.yml`) calls a shared reusable workflow on every branch push with PHP 8.3 for lint and static analysis. `release.yml` runs a shared release workflow on tag push.

## Git workflow

- Commit format: `<type>: <description>`
- Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`
- No co-author trailers
- One commit per logical change
