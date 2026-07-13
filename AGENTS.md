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
- Phase 7: daily operation, visits, visitor registration by owner/admin/security, visit authorization, entry/exit logs, visit status, common areas, reservations, availability validation, incidents, maintenances, maintenance tasks, seed data, and tests.

Additional completed modules:

- Locations: normalized `countries`, `provinces`, and `cities` tables, location seed data, public location APIs, Swagger/OpenAPI documentation, and tests. Use this module for geographic selections instead of generic catalogs.
- Administrator access invitations: condominium creation creates or reuses the administrator from `admin_*`, assigns the condominium administrator role, keeps `is_access_enabled = false`, creates a 24-hour single-use invitation with a hashed token, and queues the activation email after the database transaction commits. Access is enabled only after the user defines a strong password through `POST /api/auth/activate-access`. Condominium administrator assignment through `POST /api/condominiums/{condominium}/administrators` creates or resends access invitations when the user still has access disabled. Existing unit-user invitations remain supported.
- Administrator visibility: `GET /api/condominiums/{condominium}/administrators` lists condominium administrators only inside the route condominium. Senior administrators may access any active condominium context. Condominium administrators may list only administrators in condominiums where they have the required permission. Do not reintroduce global condominium-administrator routes.
- Platform administrators: senior/platform administrators are users with the global `administrador_senior` role through `role_user`. They do not belong to condominiums and must never be assigned through `condominium_user` or `condominium_user_role`. Use the independent `/api/platform-administrators` module for senior administrator CRUD, status changes, invitation creation, session/refresh-token revocation on disable, and global-role removal on delete.
- Condominium-scoped users and administrators: official routes exist only under `/api/condominiums/{condominium}/users` and `/api/condominiums/{condominium}/administrators`. These routes derive business context from the URL and must not require `condominium_id` or `condominium_ids` in JSON bodies. Do not maintain or add legacy global routes for condominium-owned users or administrators.

Upcoming phases:

- Phase 8: internal audit. Register activity by session, user, condominium, endpoint, HTTP method, IP, user agent, action, affected model, affected record, before/after values, token/session events, invitation events, and relevant economic events. Expected tables include `audit_logs` and optionally `audit_log_changes` when field-level change storage is needed.
- Phase 9: notifications and email. Implement notification templates, internal notifications, delivery records, Redis-backed jobs, email invitations, account access emails, login notifications, payment reminders, visit alerts, reservation alerts, and failed delivery tracking. Expected tables include `notification_templates`, `notifications`, `notification_deliveries`, and `email_logs`.
- Phase 10: condominium modules. Implement system modules and per-condominium module activation for enabling or disabling functionality. Keep modules independent from menus and permissions: modules decide whether a feature is available; menus decide navigation; permissions decide allowed actions. Expected tables include `system_modules` and `condominium_modules`.
- Phase 11: reports, optimization, and closure. Implement economic summaries, unit account statements, payments, late fees, expenses, bank reconciliation, treasury handover, visits, and reservations reports. Review indexes, query performance, Swagger/OpenAPI coverage, seed data quality, and final project documentation. Create a `docs/` folder only in this phase if final documentation files are required.

Every new phase must include migrations, seeders with usable test data, Swagger updates, module-specific routes, FormRequest validation classes, reusable rules when needed, and tests.

## Multi-Condominium API Architecture

The architectural rule for condominium-owned resources is: the URL defines business context and the JWT defines the actor. Any resource that belongs to a condominium should be exposed through nested routes using `condominiums/{condominium}`.

Correct:

```http
POST /api/condominiums/{condominium}/units
POST /api/condominiums/{condominium}/users
POST /api/condominiums/{condominium}/administrators
POST /api/condominiums/{condominium}/common-areas
```

Incorrect for condominium-scoped APIs:

```http
POST /api/units
{
  "condominium_id": 15
}
```

Do not put `condominium_id` or `condominium_ids` in the body when the route already contains `{condominium}`. FormRequests may use the route-bound `Condominium` to validate child resources, such as `role_id`, `unit_id`, `condominium_block_id`, payment methods, common areas, or reservations, but the request payload should contain only attributes of the resource being created or updated.

Authorization for condominium-scoped routes must follow this chain:

1. JWT is valid.
2. User is active and session is active.
3. `Condominium` is resolved from the route through route model binding.
4. If `condominiums.is_active` is false, return `403 condominium_inactive` for operational condominium-scoped endpoints.
5. If the actor is `User::isPlatformAdmin()`, allow platform-level access.
6. Otherwise validate active membership, active role, and effective permission in the route condominium.
7. If the actor has no scope over the route condominium, return `403 condominium_forbidden`.
8. If a child resource does not belong to the route condominium, return `404`.

Policies for condominium-owned resources should receive the `Condominium` context explicitly where practical and use existing user helpers instead of duplicating authorization logic:

```php
if ($user->isPlatformAdmin()) {
    return true;
}

return $user->hasPermission('units.create', $condominium);
```

Controllers for condominium-scoped resources should remain orchestration-only:

- receive the route-bound `Condominium`;
- call `Gate::authorize()` or a shared condominium-context authorization helper;
- call a service with the `Condominium` model and validated data;
- return `ApiResponse`.

Services for condominium-scoped resources should receive the `Condominium` model, not a loose `condominium_id`. Prefer signatures like:

```php
UnitService::create(Condominium $condominium, array|CreateUnitData $data);
```

Global platform resources are exceptions and should not be nested under a condominium. Examples include `/api/platform-administrators`, `/api/permissions`, platform-level menus, authentication, public catalogs, and location endpoints.

## Build, Test, and Development Commands

- `docker compose up -d`: start the Laravel app and Redis containers.
- `docker compose exec app php artisan migrate --seed`: run migrations and seed required test data.
- `docker compose exec app php artisan test`: run the full test suite.
- `docker compose exec app php artisan openapi:generate`: regenerate OpenAPI/Swagger documentation with request and response examples.
- `docker compose ps queue-worker`: verify the permanent queue worker is running.
- `docker compose logs -f queue-worker`: follow queued email processing and failures.
- `./vendor/bin/pint --dirty`: format changed PHP files with Laravel Pint.

The `queue-worker` Docker Compose service runs `php artisan queue:work` with automatic restart through `restart: unless-stopped`. Start it together with the application using `docker compose up -d`; do not rely on a manually detached `docker compose exec` worker for persistent queue processing.

Use Docker commands when validating database-backed behavior because the project is configured against the local MySQL server through the container.

Whenever a migration is created, changed, or executed, run it together with seeders using `docker compose exec app php artisan migrate --seed`. If the schema is already up to date and only seed data changed, run `docker compose exec app php artisan db:seed`.

Whenever API behavior changes, update the OpenAPI/Swagger documentation in the same change. This includes new or changed routes, request payloads, validation rules, response payloads, status codes, authentication requirements, permissions, query parameters, and examples. Every API operation must include request payload examples when it receives a body and response examples using the real API envelope or resource shape. After updating annotations, regenerate the documentation with `docker compose exec app php artisan openapi:generate`.

Paginated collection endpoints use query parameters `page` and `per_page`, default to 20 items, and limit `per_page` to 100 unless a module has a documented reason to differ. Return the records in `data` and pagination information in `meta` with `current_page`, `per_page`, `total`, and `last_page`. Validate pagination through a module-specific FormRequest.

## Coding Style & Naming Conventions

Follow Laravel conventions and PSR-12 formatting through Pint. Use 4-space indentation for PHP. Name controllers by module and action scope, for example `CondominiumController`, `RoleController`, and `MenuController`.

Use English technical identifiers for code, tables, routes, and permission codes, while user-facing database seed names may be Spanish. Permission codes use `module.action`, for example `roles.manage` or `boards.view`. API responses should use `App\Support\Api\ApiResponse`.

For normalized location data, use `countries.code` as the country identifier and foreign keys for province/city references. Condominiums store location through `country_code`, `province_id`, and `city_id`; do not reintroduce free-text `country`, `province`, or `city` columns in `condominiums`. Validate that `province_id` belongs to `country_code` and that `city_id` belongs to `province_id`.

## Request Validation & Rules

Do not place `$request->validate()` rules inside API controllers. Create FormRequest classes under `app/Http/Requests/Api/{Module}` and inject them into controller actions. Name requests with the controller/action intent, for example `PaymentStoreRequest`, `MonthlyFeeGenerateRequest`, or `TreasuryHandoverCalculateRequest`.

Controllers should call `$request->validated()` and remain focused on orchestration only. Cross-field, catalog, condominium-scoped, or reusable validation logic should live in dedicated rule classes under `app/Rules`, for example `ValidCatalogItem`, instead of being duplicated across requests.

Every FormRequest must define explicit Spanish messages for all of its declared validation rules through `messages()`. Include wildcard messages for array elements when the payload legitimately contains arrays. Reusable custom rules must return their own clear domain-specific message. Expected client errors must produce a field-level `422 validation_failed` response and must not be allowed to reach a database constraint and become a generic server error.

For nested condominium routes, FormRequests must not validate `condominium_id` or `condominium_ids` as body fields. Read the route-bound `Condominium` with `$this->route('condominium')` and use it to scope `exists`, `unique`, and cross-field rules.

For condominium create and update payloads, the external fields are `towers` and `houses`, which map to `towers_count` and `houses_count`. `total_units` is a separate explicit field and is not currently calculated from `houses`, apartments, or rows in `units`; send it when the total must be stored. Do not assume these counters remain synchronized automatically.

## Condominium Status and Access

Condominium deletion is a soft delete. It does not delete or disable associated users and does not revoke their sessions or tokens. Inactivation currently changes only `condominiums.is_active`; it does not disable memberships, prevent login, or enforce a platform-wide operational block. Authentication continues to depend on `users.is_access_enabled`.

Some canonical nested endpoints now return `403 condominium_inactive` when the route condominium is inactive. Do not claim that inactivation fully blocks all condominium access until a centralized active-condominium check and session revocation behavior have been implemented and tested across every module. Any future implementation must consistently cover context selection, route authorization, existing sessions, refresh tokens, users with another active condominium, and unrestricted platform access for senior administrators.

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

When `docker-compose.yml` needs runtime values, source them from `.env` with interpolation instead of hardcoding them in the compose file. Keep the public URL for uploaded files separate from the internal S3 endpoint used by the Laravel container.

Administrator invitations must never contain or store temporary passwords. Store only the SHA-256 hash of the random 64-character invitation token, keep the plain token only in the queued email, and clear the stored hash when an invitation is accepted, expired, or revoked. Invitation states are `pending`, `accepted`, `expired`, and `revoked`. Login must remain blocked while `users.is_access_enabled` is false.

Configure invitation links with `FRONTEND_URL` and `INVITATION_EXPIRES_HOURS`. The Quasar frontend uses hash routing, so activation emails must target `{FRONTEND_URL}/#/activar-acceso?token=TOKEN`. Mail credentials and provider secrets belong only in `.env` and must not be committed.
