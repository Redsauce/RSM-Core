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
