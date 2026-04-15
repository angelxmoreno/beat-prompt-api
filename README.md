# CakePHP Project Template

This repository has been stripped back from the original Lopusboard domain so it can serve as a cleaner starting point for future CakePHP projects.

## What Remains

- CakePHP 5 application skeleton
- `IdentityBridge` plugin integration for bearer-token auth
- A minimal local `users` model used by the identity resolver
- A single authenticated API endpoint at `GET /api/identity/me`
- Standard CakePHP pages, errors, migrations, and test tooling

## What Was Removed

- Lopusboard project, issue, wiki, comment, attachment, status, and department MVC
- Domain-specific policies, behaviors, enums, fixtures, seeds, and tests
- Lopusboard REST resources and their routing

## Getting Started

1. Install dependencies:

```bash
composer install
```

2. Create local config:

```bash
cp config/app_local.example.php config/app_local.php
```

3. Create a local environment file:

```bash
cp sample.env .env
```

Update the values in `.env` for your project. At minimum, set `SECURITY_SALT`
and `DATABASE_URL`. Appwrite variables are only needed if you plan to keep
`IdentityBridge` with Appwrite.

4. Run the base migration:

```bash
bin/cake migrations migrate
```

5. Start the app:

```bash
bin/cake server -p 8080
```

## Next Template Steps

- Rename the app and package metadata for your new project
- Replace the `users` table and `AppUserResolver` if you are not using Appwrite
- Bake new domain models, controllers, templates, and migrations for the new app
- Remove `Crud` and `IdentityBridge` too if the next project does not need them

## Useful Commands

- `composer test`
- `composer stan`
- `composer cs-check`
- `bin/cake bake all <Table>`
