## Purpose

Provides a repeatable compatibility gate for RSM-Core PHP code so developers can validate PHP 8.5.1 syntax, compatibility risks, and selected runtime behavior before deploying changes.

## ADDED Requirements

### Requirement: Local compatibility suite
The system SHALL provide a command-line compatibility suite that can be run from the repository root using the project PHP runtime.

#### Scenario: Suite runs from repository root
- **WHEN** a developer runs the compatibility suite command from the repository root
- **THEN** the suite MUST execute the configured PHP compatibility checks and return exit code 0 only when all required checks pass

#### Scenario: Suite reports failures clearly
- **WHEN** any required compatibility check fails
- **THEN** the suite MUST print the failed check name and return a non-zero exit code

### Requirement: PHP source linting
The compatibility suite SHALL lint every PHP source file under the project server and script directories.

#### Scenario: PHP file has valid syntax
- **WHEN** all scanned PHP files parse successfully under PHP 8.5.1
- **THEN** the lint check MUST pass

#### Scenario: PHP file has invalid syntax
- **WHEN** any scanned PHP file fails PHP linting under PHP 8.5.1
- **THEN** the lint check MUST fail and identify the file

### Requirement: PHP 8.5 compatibility scanning
The compatibility suite SHALL scan project PHP source for language constructs and functions that are removed or newly deprecated in PHP 8.5.x when they can be detected statically.

#### Scenario: Deprecated construct is found
- **WHEN** the suite detects a configured PHP 8.5 compatibility risk in source code
- **THEN** the compatibility scan MUST fail and report the file, line, and reason

#### Scenario: No configured compatibility risks are found
- **WHEN** the suite does not detect any configured PHP 8.5 compatibility risks
- **THEN** the compatibility scan MUST pass

### Requirement: Existing regression checks
The compatibility suite SHALL run existing project regression checks that do not require production credentials or a live server.

#### Scenario: Existing regression check passes
- **WHEN** an included existing regression check exits successfully
- **THEN** the suite MUST continue to subsequent checks

#### Scenario: Existing regression check fails
- **WHEN** an included existing regression check exits with a non-zero code
- **THEN** the suite MUST fail

### Requirement: Optional HTTP smoke tests
The compatibility suite SHALL support optional HTTP smoke tests against a configured RSM command base URL without committing credentials.

#### Scenario: HTTP configuration is absent
- **WHEN** no HTTP smoke test base URL is configured
- **THEN** the suite MUST skip HTTP smoke tests and still report the skip explicitly

#### Scenario: HTTP configuration is present
- **WHEN** an HTTP smoke test base URL is configured
- **THEN** the suite MUST execute the configured non-destructive HTTP checks against that base URL

#### Scenario: HTTP smoke test fails
- **WHEN** any configured HTTP smoke test returns an unexpected status or body shape
- **THEN** the suite MUST fail and identify the endpoint check

### Requirement: Secret-free configuration
The compatibility suite SHALL read optional environment-specific values from environment variables and MUST NOT require committing credentials to the repository.

#### Scenario: Credentials are needed
- **WHEN** a test requires credentials or tokens
- **THEN** the suite MUST read those values from environment variables or skip the credentialed check when they are absent
