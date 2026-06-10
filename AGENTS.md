# Repository Guidelines

## Project Structure & Module Organization

This repository is a Laravel API for condominium administration. Application code lives in `app/`, with API controllers under `app/Http/Controllers/Api`, request validation under `app/Http/Requests/Api`, resources under `app/Http/Resources/Api`, domain services under `app/Domain`, and shared response helpers under `app/Support`.

Database schema and seed data live in `database/migrations` and `database/seeders`. API routes are split by module in `routes/api/*.php`; keep `routes/api.php` as the central loader only. Tests are in `tests/Feature` and `tests/Unit`.

## Development Phases

Completed phases:

- Phase 1: base Laravel API, Docker, Redis, Swagger/OpenAPI, standard API responses, centralized errors, and `api/health`.
- Phase 2: general catalogs, catalog items, `document_type`, soft deletes, catalog seeders, and catalog APIs.
- Phase 3: JWT authentication, refresh tokens, token revocation, auth sessions, admin user seeder, and split API routes.
- Phase 4: condominiums, users by condominium, roles, permissions, menus, boards, condominium payment methods, seed data, and tests.

Upcoming phases:

- Phase 5: blocks, units, owners, residents, and unit-user relationships.
- Phase 6: billing concepts, fees, fee items, payments, allocations, attachments, and balances.
- Phase 7: common areas, reservations, incidents, maintenances, and maintenance tasks.
- Phase 8: internal audit logs by session, user, condominium, endpoint, action, and before/after changes.
- Phase 9: notification templates, notifications, deliveries, Redis-backed jobs, emails, and reminders.
- Phase 10: condominium modules for enabling or disabling functionality per condominium.
- Phase 11: reports, optimization, final Swagger review, and final project documentation.

Every new phase must include migrations, seeders with usable test data, Swagger updates, module-specific routes, and tests.

## Build, Test, and Development Commands

- `docker compose up -d`: start the Laravel app and Redis containers.
- `docker compose exec app php artisan migrate --seed`: run migrations and seed required test data.
- `docker compose exec app php artisan test`: run the full test suite.
- `php artisan l5-swagger:generate`: regenerate OpenAPI/Swagger documentation.
- `./vendor/bin/pint --dirty`: format changed PHP files with Laravel Pint.

Use Docker commands when validating database-backed behavior because the project is configured against the local MySQL server through the container.

## Coding Style & Naming Conventions

Follow Laravel conventions and PSR-12 formatting through Pint. Use 4-space indentation for PHP. Name controllers by module and action scope, for example `CondominiumController`, `RoleController`, and `MenuController`.

Use English technical identifiers for code, tables, routes, and permission codes, while user-facing database seed names may be Spanish. Permission codes use `module.action`, for example `roles.manage` or `boards.view`. API responses should use `App\Support\Api\ApiResponse`.

## Testing Guidelines

Feature tests should cover each API module and live under `tests/Feature/Api/{Module}`. Test class names should describe the module or phase, such as `CondominiumPhaseTest`.

Run module tests first when changing a specific area:

```bash
docker compose exec app php artisan test --filter=CondominiumPhaseTest
```

Then run the full suite before finishing:

```bash
docker compose exec app php artisan test
```

## Commit & Pull Request Guidelines

The current history does not define a strict commit convention. Use short, imperative commit messages such as `Add condominium phase migrations` or `Split API routes by module`.

Pull requests should include a concise summary, affected modules, migration/seeder notes, commands executed, and any API contract changes. Include Swagger regeneration when routes or request/response payloads change.

## Security & Configuration Tips

Do not commit real secrets. JWT configuration belongs in `.env`, especially `JWT_SECRET`, token TTL values, and database credentials. Keep seed credentials limited to local development data only.
