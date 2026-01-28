# Copilot / AI agent instructions — MiProyectoWembli

Purpose: quickly orient an AI coding agent to this Symfony 7.3 PHP project so it can make safe, runnable, and idiomatic edits.

- **Big picture**: This is a Symfony application (PSR-4 `App\` in `src/`). HTTP entry is [public/index.php](public/index.php). Controllers live in `src/Controller` and use PHP 8 attributes for routes (see `config/routes.yaml` which maps the controller directory as attribute-based routes). Data is persisted with Doctrine ORM (`src/Entity`, `migrations/`) and application state often stored in session (see `CestaCompra` service used by `src/Controller/BaseController.php`).

- **Key files/dirs**:
  - `src/Controller/` — controllers use attribute routing and return Twig templates in `templates/`.
  - `src/Entity/` — Doctrine entities; migrations live in `migrations/` (VersionYYYYMMDD*.php).
  - `config/` — packages, services (`config/services.yaml` enables autowire + autoconfigure), and `config/routes.yaml` (controllers configured as attributes).
  - `public/` — web root; static assets handled via `assets/` + asset mapper/importmap.
  - `bin/console` — Symfony console entry point for migrations, fixtures, cache, etc.

- **Run / build / test workflows** (what actually works in this repo):
  - Install dependencies: `composer install` (ensure PHP >= 8.2).
  - Run locally (recommended): `composer install` then either:
    - Docker: `docker compose up --build` (this repo includes `Dockerfile` and `compose.yaml`).
    - Local PHP server: `APP_ENV=dev APP_DEBUG=1 php -S localhost:8000 -t public/ public/index.php` (requires `symfony/runtime` installed via composer).
  - Symfony console: `php bin/console <command>` (e.g. `doctrine:migrations:migrate`, `doctrine:fixtures:load`).
  - Tests: `./bin/phpunit` (project ships a `bin/phpunit` wrapper).

- **Important project conventions & patterns**:
  - Controllers rely on attribute routing (see `#[Route(...)]`) and many controller-level guards use `#[IsGranted('ROLE_USER')]` (e.g., `BaseController`). Do not convert to YAML routes unless necessary.
  - Services are autowired and autoconfigured by default (`config/services.yaml`). Register explicit service configs only when constructor injection or tags are required.
  - Session-backed shopping cart: `App\Services\CestaCompra` is the canonical place handling basket read/write in session; controllers call its methods (`cargar_articulos`, `get_productos`, `get_unidades`, `calcular_coste`). Follow its API rather than duplicating session logic.
  - Entities and persistence: create and persist `Pedido` and `PedidoProducto` via Doctrine `EntityManagerInterface`; prefer migrations in `migrations/` rather than schema:update.
  - Template names follow `templates/` structure (examples: `templates/home/index.html.twig`, `templates/cesta/mostrar_cesta.html.twig`).

- **Integrations and external dependencies**:
  - Doctrine DBAL/ORM — database credentials come from environment (`DATABASE_URL`).
  - Mailer — uses Symfony Mailer; check `MAILER_DSN` in env for integration tests or sending emails.
  - Frontend uses Stimulus controllers in `assets/controllers/` and the asset-mapper/importmap pipeline (see `assets/` and composer auto-scripts).
  - EasyAdmin and other bundles are present (`easycorp/easyadmin-bundle`). Be conservative modifying admin-related code; tests or manual checks are recommended.

- **When making edits, follow these safe rules** (project-specific):
  - Keep attribute routing and controller signatures intact; parameter types are strict (PHP 8 type hints).
  - Use `php bin/console doctrine:migrations:diff` + `doctrine:migrations:migrate` for schema changes; don't edit `migrations/` manually unless adding a specific migration file.
  - Update Twig templates in `templates/` matching controller render calls.
  - For session/cart changes, modify `App\Services\CestaCompra` and adapt all callers in `src/Controller/BaseController.php` rather than touching session keys in multiple places.
  - Run `./bin/phpunit` after backend changes; for DB-related tests, ensure test database is configured (check env vars).

- **Examples (copy-paste patterns found in repo)**:
  - Route attribute: `#[Route('/cesta', name:'cesta')] public function cesta(CestaCompra $cesta)` — inject services via type-hint.
  - Persisting order: use `EntityManagerInterface $em`, `persist()` new `Pedido` and `PedidoProducto`, then `flush()`.

- **When unsure / safety checks**:
  - Look for session usage before changing keys: search for `get('productos'` or `CestaCompra` usage.
  - Prefer adding unit/integration tests for behavioral changes; run `./bin/phpunit`.
  - If changing database schema, add a migration and run it locally before pushing.

If anything above is ambiguous or you want environment-specific commands (e.g., CI, deployment, or DB credentials), tell me which environment you use and I'll add concrete run/CI steps.
