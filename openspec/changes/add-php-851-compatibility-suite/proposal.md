## Why

The project needs a repeatable way to verify that its PHP endpoints and shared utilities remain compatible with PHP 8.5.1 before changes are promoted to the development or server environment. Today compatibility checks are mostly ad hoc, so regressions can slip through after otherwise small PHP edits.

## What Changes

- Add a project-local compatibility test suite that can be run after every change from the command line.
- Cover PHP syntax linting, PHP 8.5.1 compatibility/deprecation pattern scanning, existing utility regression tests, and optional HTTP endpoint smoke tests.
- Make the suite runnable without credentials for local static checks, while allowing optional credentials/base URL for a mounted development environment.
- Keep test configuration outside source code through environment variables so credentials are not committed.
- Produce clear pass/fail output and a non-zero exit code for CI/Jenkins/server promotion gates.

## Capabilities

### New Capabilities
- `php-compatibility-suite`: Defines the repeatable PHP 8.5.1 compatibility suite and its required coverage modes.

### Modified Capabilities

## Impact

- New test runner scripts under `scripts/`.
- Optional test configuration documented in repository docs or script help output.
- OpenSpec artifacts for the compatibility suite.
- No production API behavior changes.
