# Repository Guidelines

## Project Structure & Module Organization

This repository is a Laravel API for condominium administration. Application code lives in `app/`, with API controllers under `app/Http/Controllers/Api`, request validation under `app/Http/Requests/Api`, resources under `app/Http/Resources/Api`, domain services under `app/Domain`, and shared response helpers under `app/Support`.

Database schema and seed data live in `database/migrations` and `database/seeders`. API routes are split by module in `routes/api/*.php`; keep `routes/api.php` as the central loader only. Tests are in `tests/Feature` and `tests/Unit`.

## Development Phases

Completed phases:

- Phase 1: base Laravel API, Docker, Redis, Swagger/OpenAPI, standard API responses, centralized errors, and `api/health`.
- Phase 2: general catalogs, catalog items, `document_type`, soft deletes, catalog seeders, and catalog APIs.
- Phase 3: JWT authentication, refresh tokens, token revocation, auth sessions, admin user seeder, and split API routes.
- Phase 4: condominiums, users by condominium, roles, permissions, two-level menus, boards/directives with dates, condominium payment methods, seed data, and tests.
- Phase 5: blocks, units/houses, parking assignment through unit data, related people, owners, co-owners, tenants, residents, billing profiles, access invitations, user access enablement, unit aliquots, seed data, and tests.
- Phase 6: economic administration, billing settings by condominium, monthly fees, extraordinary fees, late fee foundations, advance payments, credit balances, payments, allocations, unit account movements, bank opening balances, bank movements, statement imports, reconciliations, expenses, treasury handovers, payment order foundations, seed data, and tests.

Upcoming phases:

- Phase 7: daily operation. Implement visits, visitor registration by owner/admin/security, visit authorization, entry/exit logs, visit status, common areas, reservations, availability validation, incidents, maintenances, and maintenance tasks. Expected tables include `visitors`, `visits`, `visit_logs`, `common_areas`, `common_area_reservations`, `incidents`, `maintenances`, and `maintenance_tasks`.
- Phase 8: internal audit. Register activity by session, user, condominium, endpoint, HTTP method, IP, user agent, action, affected model, affected record, before/after values, token/session events, invitation events, and relevant economic events. Expected tables include `audit_logs` and optionally `audit_log_changes` when field-level change storage is needed.
- Phase 9: notifications and email. Implement notification templates, internal notifications, delivery records, Redis-backed jobs, email invitations, account access emails, login notifications, payment reminders, visit alerts, reservation alerts, and failed delivery tracking. Expected tables include `notification_templates`, `notifications`, `notification_deliveries`, and `email_logs`.
- Phase 10: condominium modules. Implement system modules and per-condominium module activation for enabling or disabling functionality. Keep modules independent from menus and permissions: modules decide whether a feature is available; menus decide navigation; permissions decide allowed actions. Expected tables include `system_modules` and `condominium_modules`.
- Phase 11: reports, optimization, and closure. Implement economic summaries, unit account statements, payments, late fees, expenses, bank reconciliation, treasury handover, visits, and reservations reports. Review indexes, query performance, Swagger/OpenAPI coverage, seed data quality, and final project documentation. Create a `docs/` folder only in this phase if final documentation files are required.

Every new phase must include migrations, seeders with usable test data, Swagger updates, module-specific routes, FormRequest validation classes, reusable rules when needed, and tests.

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

## Request Validation & Rules

Do not place `$request->validate()` rules inside API controllers. Create FormRequest classes under `app/Http/Requests/Api/{Module}` and inject them into controller actions. Name requests with the controller/action intent, for example `PaymentStoreRequest`, `MonthlyFeeGenerateRequest`, or `TreasuryHandoverCalculateRequest`.

Controllers should call `$request->validated()` and remain focused on orchestration only. Cross-field, catalog, condominium-scoped, or reusable validation logic should live in dedicated rule classes under `app/Rules`, for example `ValidCatalogItem`, instead of being duplicated across requests.

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
