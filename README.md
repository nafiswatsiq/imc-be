## Setup

```bash
composer install
cp .env.example .env          # then edit DB_* and JWT_SECRET

vendor/bin/phinx migrate
vendor/bin/phinx seed:run
composer start                # http://localhost:8000 (API under /api/v1)
composer test                 # PHPUnit, uses in-memory SQLite
```

## Seeded accounts

| Username | Password | Role | Notes |
|---|---|---|---|
| `superadmin` | `password` | Super Admin | bypasses all checks |
| `manager1` | `password` | Manager | everything except Permissions |
| `staff1` | `password` | Staff | Dashboard (view) |
| `viewer1` | `password` | Viewer | Dashboard (view) |
| `inactive1` | `password` | Staff | inactive, cannot log in |
| `staff_allow` | `password` | Staff | **allow** override: `users.view` |
| `manager_deny` | `password` | Manager | **deny** override: `pages.view`, `roles.view` |

## API summary (`/api/v1`)

Success: `{ "data": ..., "meta": { "page", "per_page", "total" } }`
Error: `{ "error": { "code", "message", "details" } }` with codes `BAD_REQUEST 400`, `UNAUTHENTICATED 401`, `FORBIDDEN 403`,
`NOT_FOUND 404`, `CONFLICT 409`, `VALIDATION_ERROR 422`, `TOO_MANY_REQUESTS 429`, `SERVER_ERROR 500`.

| Method | Path | Permission |
|---|---|---|
| POST | `/auth/login` body `{identifier, password}` (username or email) | public |
| POST | `/auth/refresh` body `{refresh_token}` (rotates) | public |
| POST | `/auth/logout` | authenticated |
| GET | `/auth/me` (user, effective permissions, menu) | authenticated |
| GET, POST | `/users` | `users.view`, `users.create` |
| GET, PUT, DELETE | `/users/{id}` | `users.view`, `users.update`, `users.delete` |
| GET, POST | `/roles` | `roles.view`, `roles.create` |
| GET, PUT, DELETE | `/roles/{id}` | `roles.view`, `roles.update`, `roles.delete` |
| GET, POST | `/pages` | `pages.view`, `pages.create` |
| GET, PUT, DELETE | `/pages/{id}` | `pages.view`, `pages.update`, `pages.delete` |
| GET | `/permissions` (matrix: pages with permissions) | `permissions.view` |
| GET, PUT | `/roles/{id}/permissions` body `{permission_ids: []}` | `permissions.view`, `permissions.update` |
| GET, PUT | `/users/{id}/permissions` body `{overrides: [{permission_id, effect}]}` | `permissions.view`, `permissions.update` |
