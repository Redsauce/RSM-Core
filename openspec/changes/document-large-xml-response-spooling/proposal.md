## Why

Large XML query responses already use temporary files, but this behavior has no OpenSpec contract. Documenting the implementation establishes a baseline for bug fixes. Moving the hard-coded threshold into server configuration lets installations tune it without editing response helpers.

## What Changes

- Capture existing XML response spooling in `RSReturnQueryResults()` and `RSReturnArrayQueryResults()` as a source-derived specification.
- Document the strict threshold of 2,000 result fields, array field estimation, temporary file delivery, compression, fallback, and cleanup.
- Add the shared server configuration setting `$RSresponseFileFieldThreshold`, retaining 2,000 as the default and falling back to it for missing or invalid settings.
- Use the shared validated threshold in both response helpers and add isolated regression coverage; no separate response corruption bug is claimed fixed.

## Capabilities

### New Capabilities

- `large-xml-response-spooling`: A newly documented existing capability, with a shared configurable threshold.

### Modified Capabilities

None. No main specification exists for this capability yet.

## Impact

- Baseline source: `Server/htdocs/AppController/commands_RSM/utilities/RSdatabase.php`.
- Configuration: `Server/htdocs/AppController/commands_RSM/utilities/RSconfiguration.php` and its generation/deployment flow.
- PHP configuration, response helpers, and the new `RSresponseTempFiles.php` lifecycle helper change; no database or dependency changes. Tests live in `scripts/test_response_file_threshold.php`.
- Track response temporaries for shutdown cleanup, delete failed gzip output, and check/log failed deletions without retries.

- Item query file paths also use the shared configured value instead of 1000, preserving their existing row-count sizing.
