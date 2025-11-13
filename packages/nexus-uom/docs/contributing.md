# Contributing Guidelines

Thanks for helping advance Laravel UOM Management. The notes below outline the expectations for contributions so reviews stay smooth and predictable.

## Development Workflow

1. Fork or branch from `main`.
2. Run `composer install` followed by `composer dump-autoload` after adding new classes.
3. Ensure the test database uses the in-memory sqlite connection defined in `tests/TestCase.php`.
4. Execute the full test suite with `vendor/bin/phpunit` before submitting changes.
5. When introducing new features, add feature tests and update documentation under `docs/`.

## Coding Standards

- Follow PSR-12 for PHP code style.
- Keep namespaces under `Azaharizaman\LaravelUomManagement`.
- Prefer `Brick\Math` for numeric manipulation instead of PHP floats.
- Add succinct docblocks where behaviour is non-trivial.
- Keep comments ASCII-only unless a file already ships with alternate characters.

## Tests and Coverage

- Tests live under `tests/Feature` and `tests/Unit` with shared utilities in `tests/TestCase.php`.
- Add regression coverage for every bug fix.
- Maintain coverage for critical paths (conversion, alias resolution, packaging, custom unit registration). The PHPUnit configuration tracks coverage for the `src/` directory by default.

## Documentation

- Update relevant guides in `docs/` when behaviour or public APIs change.
- Note breaking changes in `docs/upgrade-guide.md`.
- Keep the progress checklist current after merging significant features.

## Pull Requests

- Provide a concise summary and bullet list of changes.
- Reference related issues or checklists.
- Highlight any follow-up work that should be tracked separately.

Following these practices keeps the package consistent and maintainable for everyone using it.
