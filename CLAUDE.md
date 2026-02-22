# CLAUDE.md

## Project Context

Read these READMEs to understand the codebase before making changes:

- [readme.md](readme.md) — installation, configuration, and usage
- [src/README.md](src/README.md) — architecture, sync algorithm, dependency injection, project structure, developer guide
- [src/Client/README.md](src/Client/README.md) — client design and interface contracts
- [src/Client/Google/README.md](src/Client/Google/README.md) — Google OAuth lifecycle and token management
- [src/Client/PlanningCenter/README.md](src/Client/PlanningCenter/README.md) — Planning Center API integration and pagination
- [src/Command/README.md](src/Command/README.md) — command orchestration details
- [src/Contact/README.md](src/Contact/README.md) — diff algorithm

## Commands

Run tests:
```
composer run-script test
```

Check code style:
```
composer run-script cs
```

Fix code style:
```
composer run-script cs-fix
```

## Workflow Requirements

After ANY code change, always run both tests and code style checking:
```
composer run-script test && composer run-script cs
```

If `cs` reports violations, fix them with `composer run-script cs-fix`, then re-run tests to confirm nothing broke.

## Testing Conventions

- Tests live in `tests/` and mirror the `src/` directory structure (e.g. `src/Contact/ContactListAnalyzer.php` → `tests/Contact/ContactListAnalyzerTest.php`).
- Test classes extend `Mockery\Adapter\Phpunit\MockeryTestCase`, not PHPUnit's base `TestCase`.
- Use [Mockery](https://github.com/mockery/mockery) (`Mockery as m`) for mocking, not PHPUnit's built-in mock builder.
- Command tests use Symfony's `CommandTester` to execute commands and assert on output/status codes.
- Data-driven tests use PHPUnit's `#[DataProvider]` attribute with a static provider method.
- When adding a new class, add a corresponding test file. When modifying a class, update or extend its existing tests.

## Code Style

- The project enforces Symfony + PSR-12 rules via PHP-CS-Fixer (config: `.php-cs-fixer.dist.php`).
- No Yoda conditions — write `$x === true`, not `true === $x`.
- Use short array syntax (`[]`, not `array()`).
- Do not use `phpdoc_to_comment` conversion — multi-line `/** */` annotations are allowed above any statement.

## Architecture Notes

- PHP 8.5 with Symfony 7.2. Use constructor promotion and PHP 8+ features (attributes, named arguments, readonly properties, etc.) where appropriate.
- PSR-4 autoloading: `App\` → `src/`, `App\Tests\` → `tests/`.
- Symfony autowiring and autoconfigure are enabled. New services placed in `src/` are registered automatically — no manual service definitions needed unless non-standard wiring is required.
- Constructor parameters are bound to config values in `config/services.yaml`. If you add a new service that needs a config parameter, add the binding there.
- `config/parameters.yml` contains secrets (API keys, OAuth credentials). Never commit real values or echo them in output.
- `var/` contains runtime artifacts (Google OAuth token). It is not committed to version control.