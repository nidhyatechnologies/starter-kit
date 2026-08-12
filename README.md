# Nidhya Starter Kit

A professional Laravel starter application with a custom Bootstrap-based interface, Livewire navigation, Fortify authentication, two-factor security, and role-based access control.

## Highlights

- Laravel 13, PHP 8.3+, and Livewire 4
- Custom Bootstrap/Sass design system with Manrope typography and Tabler icons
- Livewire SPA-style navigation with `wire:navigate`
- Authentication powered by Laravel Fortify
  - Registration and sign in
  - Email verification
  - Password reset and password confirmation
  - Profile and password updates
  - Two-factor authentication with recovery codes
- Role and permission management powered by Spatie Laravel Permission
- User management with role assignment
- Responsive application shell with Dashboard, Account, and User management areas
- Pest feature tests and Laravel Pint formatting

## Tech stack

| Area | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ |
| Interactive UI | Livewire 4 |
| Authentication | Laravel Fortify |
| Authorization | Spatie Laravel Permission |
| Styling | Bootstrap 5, Sass, custom components |
| Assets | Vite, Tabler Icons webfont |
| Testing | Pest |

## Requirements

- PHP 8.3 or newer
- Composer 2
- Node.js 20 or newer with npm
- SQLite, MySQL, MariaDB, or another Laravel-supported database

Laravel Herd is supported for local development. The project can also run with the standard Laravel development server.

## Create a new project with the Laravel Installer

After this repository is published as the Composer package `nidhyatechnologies/starter-kit`, create a new application directly with the Laravel Installer:

```bash
laravel new my-application --using=nidhyatechnologies/starter-kit
```

Then enter the new project, configure the database, and run the migrations and seeders:

```bash
cd my-application
php artisan migrate --seed
npm run dev
```

The `--using` option resolves a Composer package name. Publish this repository to Packagist, or register it in your private Composer repository, before using the command. Install or update the Laravel Installer first if `laravel` is not available on your machine:

```bash
composer global require laravel/installer
```

## Installation

1. Install PHP dependencies.

   ```bash
   composer install
   ```

2. Create the environment file and application key.

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   On Windows PowerShell, use:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Configure your database in `.env`.

   The default configuration uses SQLite. Create the database file before migrating:

   ```bash
   touch database/database.sqlite
   ```

   On Windows PowerShell:

   ```powershell
   New-Item -ItemType File database/database.sqlite
   ```

4. Run migrations and seed the local administrator role.

   ```bash
   php artisan migrate --seed
   ```

5. Install and build front-end assets.

   ```bash
   npm install
   npm run build
   ```

6. Start development.

   ```bash
   npm run dev
   ```

   When not using Herd, run the Laravel server in a separate terminal:

   ```bash
   php artisan serve
   ```

## Local administrator

The default database seeder creates a local administrator account:

| Field | Value |
| --- | --- |
| Email | `admin@example.com` |
| Password | `password` |
| Role | `Super Admin` |

This account is intended for local development only. Change or remove it before deployment. Email verification is enabled, so complete the verification flow after the first sign-in. With the default local mail configuration, verification messages are written to the application log.

## Application areas

### Dashboard

The Dashboard is a full-width landing area for signed-in, verified users. It intentionally has no contextual sidebar.

### Account

Every signed-in, verified user can manage:

- **Profile** — name and email address
- **Password** — current-password-protected password change
- **Security** — two-factor authentication setup, confirmation, recovery-code regeneration, disabling 2FA, and account closure

### User management

Users with the `manage access` permission can access the User management main navigation:

- **Users** — inspect registered users and assign roles
- **Roles & permissions** — create, edit, and delete roles and permissions; assign permissions to roles

The `Super Admin` role is protected from deletion and receives all authorization abilities through Laravel's gate.

## Authorization model

The project uses Spatie Laravel Permission and follows a role-first model:

```text
Users → Roles → Permissions
```

The initial seeder creates:

- `Super Admin` role
- `manage access` permission

The `Super Admin` role receives the `manage access` permission and is authorized globally through `Gate::before`.

To grant an existing user access to user administration, assign a role containing `manage access` from the User management area.

## Page structure

```text
resources/views/pages/
├── ⚡dashboard.blade.php
├── profile/
│   ├── ⚡index.blade.php
│   ├── ⚡password.blade.php
│   └── ⚡security.blade.php
├── roles/
│   └── ⚡index.blade.php
└── users/
    └── ⚡index.blade.php
```

Authentication screens are located in `resources/views/auth/`. The main and authentication layouts are in `resources/views/layouts/`.

## Front-end development

The front-end entry points are:

- `resources/sass/app.scss` — Sass entry point
- `resources/sass/_variables.scss` — Bootstrap and theme variables
- `resources/sass/_components.scss` — custom component and application-shell styling
- `resources/js/app.js` — application JavaScript
- `resources/js/bootstrap.js` — Bootstrap JavaScript setup

The primary brand color is `#056FFA`; the secondary color is `#64748B`.

## Authentication notes

Fortify has the following features enabled:

- Registration
- Password resets
- Email verification
- Profile information updates
- Password updates
- Two-factor authentication with confirmation

Google and Facebook buttons are displayed on relevant authentication screens as interface placeholders. They are disabled until OAuth credentials and a Socialite integration are added.

## Useful commands

| Command | Purpose |
| --- | --- |
| `npm run dev` | Start the Vite development server |
| `npm run build` | Build production front-end assets |
| `php artisan test` | Run the Pest test suite |
| `vendor/bin/pint --parallel` | Format changed PHP files |
| `php artisan migrate --seed` | Run migrations and create the local administrator role |
| `php artisan permission:cache-reset` | Clear Spatie permission cache |
| `php artisan route:list` | Inspect registered routes |

## Quality checks

Run these before opening a pull request or deploying:

```bash
php artisan test --compact
vendor/bin/pint --format agent
npm run build
```

## Security and deployment

- Set `APP_ENV=production` and `APP_DEBUG=false` in production.
- Use a real mail provider for verification and password reset messages.
- Replace the seeded development credentials before deployment.
- Configure a persistent cache store and queue worker for production workloads.
- Keep `APP_KEY`, database credentials, and OAuth secrets out of version control.

## License

This project is distributed under the MIT license, consistent with its Laravel starter dependencies.
