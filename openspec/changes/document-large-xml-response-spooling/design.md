## Context

This change documents the existing file-backed XML response mechanism and moves its two hard-coded thresholds into shared server configuration. Work is on `develop` in `RSM-Core`, following the user correction. The implementation was transferred from the separate `master` worktree while preserving unrelated local changes and the existing merge conflict.

`RSReturnQueryResults()` counts rows multiplied by fields. `RSReturnArrayQueryResults()` estimates total fields from the rounded average width of its first ten array rows. Neither calculation measures bytes or PHP memory. Disk output incrementally writes XML to an `RSR` temporary file, optionally compresses it, sends it, and cleans it up. Source result data can still reside in memory.

## Goals / Non-Goals

**Goals:**

- Document the baseline response storage decision and lifecycle.
- Configure the shared field threshold while preserving the default of 2000 and the strict `>` comparison.
- Preserve delivery, compression, pre-serialization file-failure fallback, and cleanup.

**Non-Goals:**

- Fix a separate unspecified serialization bug, change JSON output, or eliminate source result memory use.
- Change the existing sizing units: item queries count rows; XML response helpers count fields.
- Repair existing CDATA, `SplFixedArray`, legacy compression, or partial-write differences.

## Decisions

1. `$RSresponseFileFieldThreshold = 2000` belongs beside `$RStempPath` in `RSconfiguration.php`. It must be a non-negative PHP integer; zero disables file-backed query responses and keeps serialization in RAM, including the separate item-management file paths. Request parameters do not override it.
2. `RSGetResponseFileFieldThreshold()` resolves the setting for both responders. Missing or invalid values fall back to 2000, preserving installations with older configuration files. A shared resolver avoids validation drift. Byte-based or memory-based thresholds would change existing semantics and are deferred.
3. Keep the existing serializers, compression policy, comparison, and estimators unchanged. `$RStempPath` continues to configure the directory independently.
4. Repository search found the placeholder-based configuration template but no generator for it in this checkout. The new setting is a literal assignment requiring no placeholder substitution. External deployment tooling must retain it to enable customization; older generated files safely use the default.
5. CLI tests extract response functions to avoid database/security bootstrap and replace only request termination and external side effects. They exercise both responders with a fake database cursor, real temporary files and compression, injected creation/compression failures, and captured headers.

## Risks / Trade-offs

- Field count poorly approximates response bytes: one large field can remain in RAM. Preserve the sizing semantics in this change.
- Disk and memory branches differ in CDATA handling and legacy compression prefixes. These remain outside scope.
- Partial writes are unchecked and database rows may already have been consumed. The fallback contract covers failure before row consumption, not recovery from truncated files.
- Isolated CLI coverage does not replace an integration test against a live database and web server.

## Migration Plan

Deploy the updated configuration and response helper files. Configure the integer assignment in `RSconfiguration.php` as needed. Missing settings retain the default of 2000; reset to 2000 to restore the previous decision behavior.

## Verification

- `php scripts/test_response_file_threshold.php`: 138 passing cases across both responders, covering default/custom/missing/invalid/zero thresholds, boundary comparisons, XML completeness, content length, compression, file-creation fallback, and cleanup.
- PHP syntax checks and strict OpenSpec validation pass.

## Open Questions

A separate bug symptom/reproducer has not been supplied. It is deferred to a follow-up rather than being treated as fixed by configuration work.

## Abnormal termination cleanup

`RSresponseTempFiles.php` maintains a request-local registry and registers one shutdown handler before creating the first temporary file. It stores absolute paths and tracks writable streams or a release callback for XMLWriter so they can be closed before deletion, including on Windows. A 64 KiB reserve is released at shutdown to improve cleanup reliability after memory-limit errors. All three creation sites in RSdatabase, the gzip destination, and files handed to RSReturnFileResults participate.

Deletion attempts once, logs failure, and unregisters the file even on failure so shutdown does not retry it. Files not yet processed still receive shutdown cleanup. Permanent permission errors cannot be overcome by PHP. Gzip compression uses finally to close handles and remove unsuccessful destinations, and checks reads, partial/failed writes, and gzip close results.

Eleven additional isolated regression cases cover early exit before normal cleanup (modeling an output abort), fatal error, uncaught exception, memory exhaustion, a real PHP timeout, changed working directory, failed source opening, injected read/write failures, successful single-attempt deletion, and logged deletion failure without shutdown retry. These supplement the 138 response regression cases. An actual remote disconnect has not been validated against the production web server; the local development server did not reproduce an output abort, so no end-to-end disconnect guarantee is claimed from that test.

Forced process termination and permanently denied deletion remain operating limits. A server-level periodic cleanup is required for forced-kill recovery, and has not been installed by this local repository change.
## Item writer registration correction

Inspection after the server residue report found two additional file creation sites in develop: `RSstreamFilteredItemsPropertiesToXML()` and `RSfilteredItemsArrayToXML()`. Their direct tempnam calls previously bypassed the registry until delivery. Both now register files and XMLWriter release callbacks before writing, and use checked deletion if opening fails. Two subprocess tests exercise fatal errors inside the actual writers before filename return. The cleanup suite now has 13 cases; the 138 response cases and dynamic item join suite also pass. A threshold of 2000 still enables temporary-file creation by design; only zero disables file-backed query responses. Server residue causation and deployed configuration require production verification.
## Shared limit for item queries

Both item query file paths now use RSGetResponseFileFieldThreshold() instead of their former fixed 1000-row limit. The same configured number is applied with strict greater-than comparison; zero disables disk. Item queries retain row-count sizing, while RSdatabase responders retain field-count sizing. Tests cover custom limits, equality, default, missing/invalid settings, and zero on both item query paths.
