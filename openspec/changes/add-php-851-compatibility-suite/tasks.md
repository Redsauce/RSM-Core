## 1. Suite Runner

- [x] 1.1 Add a PHP CLI compatibility runner under `scripts/` and verify it can be invoked from the repository root.
- [x] 1.2 Add command-line help and environment variable documentation and verify the help output lists local and optional HTTP checks.

## 2. Local PHP Checks

- [x] 2.1 Implement recursive PHP syntax linting for `Server/` and `scripts/` and verify all current PHP files pass under PHP 8.5.1.
- [x] 2.2 Implement PHP 8.5 compatibility token scanning and verify it reports no false positives for SQL backticks inside strings.
- [x] 2.3 Run existing project regression scripts from the suite and verify `scripts/test_master_token_templates.php` passes through the runner.

## 3. Optional HTTP Smoke Checks

- [x] 3.1 Add optional HTTP smoke test support controlled by environment variables and verify it is skipped clearly when no base URL is configured.
- [x] 3.2 Add at least one non-destructive HTTP smoke check and verify it fails with actionable output on unexpected status or response shape.

## 4. Verification

- [x] 4.1 Run the full default suite with PHP 8.5.1 and verify it exits successfully.
- [x] 4.2 Validate the OpenSpec change with `openspec validate add-php-851-compatibility-suite --strict`.
