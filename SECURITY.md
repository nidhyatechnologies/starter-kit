# Security Policy

## Supported versions

Security fixes are applied to the latest release on the `main` branch.

## Reporting a vulnerability

Please do not report security vulnerabilities through public GitHub issues. Email the maintainers through the contact details on the Nidhya Technologies GitHub organization, including:

- a clear description of the issue;
- reproduction steps or proof of concept;
- affected versions and potential impact.

We will acknowledge the report, investigate it privately, and coordinate a fix before disclosure where appropriate.

## Secure deployment

Before production deployment, set `APP_ENV=production` and `APP_DEBUG=false`, configure a real mail provider, use strong unique credentials, and never deploy the optional demo account.
