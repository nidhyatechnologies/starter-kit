# Contributing to Nidhya Starter Kit

Thank you for helping improve Nidhya Starter Kit.

## Before you begin

- Discuss substantial changes in an issue before investing significant implementation time.
- Use a focused branch and keep each pull request limited to one concern.
- Do not include secrets, generated build assets, or unrelated formatting changes.

## Local setup and checks

Install the project with `composer setup`. This creates a local SQLite database, runs migrations, and builds assets without adding a demo account. If you need one locally, run `php artisan db:seed --class=DemoUserSeeder`.

Before submitting a pull request, run:

```bash
php artisan test --compact
vendor/bin/pint --format agent
composer audit --no-interaction
npm audit --omit=dev --audit-level=high
npm run build
```

For changes to JavaScript or Livewire navigation, also run `npx playwright install chromium` once and `php artisan test --testsuite=Browser`.

## Pull requests

- Explain the problem and the user-visible outcome.
- Include tests for changed behaviour.
- Update the README or changelog when the public setup, feature set, or behaviour changes.
- Keep the project accessible, responsive, and consistent with the existing Bootstrap/Sass interface.

By contributing, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
