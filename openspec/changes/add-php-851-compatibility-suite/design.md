## Context

RSM-Core is a PHP application with many request-entry PHP files under `Server/htdocs/AppController/commands_RSM/` and a small existing test script under `scripts/`. The local environment currently provides PHP 8.5.1, so the suite can validate the target version locally. Many endpoints require database state or credentials, so the first version of the suite must separate required local checks from optional environment-backed smoke tests.

## Goals / Non-Goals

**Goals:**
- Provide one stable command developers can run after each change.
- Fail fast enough for local development while still printing actionable diagnostics.
- Keep checks useful without requiring remote credentials.
- Allow optional checks against an already mounted development endpoint through environment variables.

**Non-Goals:**
- Replace full business workflow testing.
- Seed or mutate production data.
- Store API tokens, logins, or passwords in committed files.
- Guarantee compatibility with PHP versions older than 8.5.1.

## Decisions

### Use a PHP runner script

Create a single PHP CLI runner in `scripts/` so the suite uses the same PHP binary being validated. Shell wrappers are useful for convenience, but PHP gives better cross-platform control over token scanning, process execution, and JSON/HTTP checks.

Alternative considered: a Bash-only suite. This was rejected because token-level PHP scans are easier and less error-prone with `token_get_all()`.

### Keep checks layered

The suite will run required local checks first: PHP version reporting, recursive `php -l`, static token compatibility scans, and existing project regression scripts. Optional HTTP smoke checks run only when their environment variables are present.

Alternative considered: require MariaDB and remote credentials for every run. This was rejected because developers need a cheap default gate after small code changes.

### Detect compatibility risks with PHP tokens

The static compatibility scan will use `token_get_all()` instead of text-only grep where possible, so SQL backticks inside strings are not misreported as PHP shell execution. It will flag configured removed/deprecated functions, PHP backtick shell execution, non-canonical casts deprecated by PHP 8.5, semicolon-terminated `case` statements, magic serialization methods deprecated by PHP 8.5, and simple null array offset patterns.

Alternative considered: install a third-party analyzer. This was rejected for the initial suite because the repository has no Composer tooling and network availability is not guaranteed.

### Optional HTTP checks use only safe endpoints by default

Without credentials, the suite can check public/static or invalid-request behavior. Credentialed read/write checks should be added behind explicit environment variables once stable development credentials and disposable test records are available.

Alternative considered: immediately include write tests against the remote development server. This was rejected until the exact disposable data model and credentials are provided.

## Risks / Trade-offs

- Static scans can miss runtime-only incompatibilities -> Mitigate by combining scans with linting, existing regressions, and optional HTTP smoke tests.
- HTTP checks may be flaky if the remote environment is unavailable -> Mitigate by making them opt-in and clearly marked as skipped when not configured.
- Legacy code may emit warnings only with specific request data -> Mitigate by adding focused endpoint tests incrementally as credentials and safe fixtures become available.
- PHP 8.5.2 may differ from PHP 8.5.1 -> Mitigate by printing the actual PHP version in every suite run.

## Migration Plan

1. Add the CLI runner and documentation.
2. Run the suite locally with PHP 8.5.1 and fix issues if found.
3. Add optional environment-backed HTTP checks as credentials and stable fixtures become available.
4. Wire the command into Jenkins or pre-deploy scripts after the local command is stable.
