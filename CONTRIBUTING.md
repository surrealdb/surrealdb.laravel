# Contributing

Thank you for considering a contribution to the SurrealDB Laravel integration.

## Pull Request Review Policy

All code changes should be submitted through a pull request and reviewed before merge. Direct pushes to protected branches should be avoided except for emergency repository administration.

Before a pull request is merged:

- At least one approving human review is required.
- Required GitHub Actions checks must pass.
- Security-sensitive changes should receive extra scrutiny from a maintainer familiar with the affected area.
- Documentation and examples should be updated when public behavior changes.

## Local Checks

Run the same checks used by CI before opening a pull request:

```bash
composer validate --no-check-publish
composer audit --locked
composer analyse
composer test
```

## Development Setup

This package is designed to be developed beside the SDK and ORM repositories:

```text
surrealdb.php/
surqlize.php/
surrealdb.laravel/
```

The root `composer.json` includes path repositories for that local workflow.

## Security Issues

Do not report suspected vulnerabilities in public issues or pull requests. See `SECURITY.md` for private vulnerability reporting instructions.
