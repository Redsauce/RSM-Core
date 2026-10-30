## 1. Source-derived baseline documentation

- [x] 1.1 Inspect XML responders, field estimation, gzip delivery, and cleanup; document baseline behavior and boundaries.
- [x] 1.2 Distinguish result field counts from bytes and the independent item-management row threshold.

## 2. Configurable threshold

- [x] 2.1 Inspect configuration/deployment references and add `$RSresponseFileFieldThreshold = 2000` to the configuration template.
- [x] 2.2 Resolve and validate the shared non-negative PHP integer setting in both responders, preserving estimators and the strict comparison.
- [x] 2.3 Verify default, absent/invalid, custom, boundary, and zero settings for both responders through isolated execution tests.
- [x] 2.4 Verify XML delivery, compression selection, fallback before row consumption, and temporary-file cleanup.

## 3. Review

- [x] 3.1 Update proposal, design, and specification to describe the implemented configuration; defer unspecified serialization bugs to a separate follow-up.
- [x] 3.2 Review the runtime diff, run PHP syntax checks, and validate OpenSpec strictly.

No archive or spec sync is included in this implementation step.

## 4. Abnormal termination and cleanup failures

- [x] 4.1 Register all response temporary creation sites and gzip destinations for request-local shutdown cleanup, closing handles/writers before deletion.
- [x] 4.2 Remove incomplete gzip files immediately using finally and detect read/write/close failures.
- [x] 4.3 Check deletions, attempt each deletion once, log failures, and avoid retrying failed deletions at shutdown.
- [x] 4.4 Test early termination, fatal/exception/memory/timeout cleanup, compression failures, and single-attempt deletion/logging; rerun existing response tests.
- [x] 4.5 Document production verification limits and the requirement for independent stale-file cleanup after forced process termination.
## 5. RAM-only setting

- [x] 5.1 Define zero as disabling file-backed query responses in both responders and the item query paths, preserving the existing unrelated conflict.
- [x] 5.2 Update configuration documentation and regression coverage for large zero-threshold results and both item query paths.

## 6. Item XML generation residue

- [x] 6.1 Replace direct tempnam calls in both item XML writers with registered temporary-file creation and track XMLWriter release before serialization.
- [x] 6.2 Route XMLWriter open failures through checked cleanup.
- [x] 6.3 Inject fatal errors into both actual item writers in child processes and verify no files remain; update the isolated dynamic-join test bootstrap for the shared helpers.
## 7. Configurable item query limit

- [x] 7.1 Replace the fixed item-query limit with the shared configured value for both file paths, preserving row sizing and zero-as-RAM semantics.
- [x] 7.2 Test custom/default limits, exact boundaries, zero, and absent/invalid settings on both item query paths; update the dynamic-join fixture to set its threshold explicitly.
