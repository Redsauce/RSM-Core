# Staff application properties

## Purpose

Describe the staff application definitions added to the v7.0.0.3.165 updater by commit 8cb5045.

## Requirements

### Requirement: Idempotent staff property definitions
The v6.9.0.3.164 to v7.0.0.3.165 SQL update SHALL insert missing definitions for `staff.login`, `staff.password`, `staff.locale` and `staff.RsmAI.token` for staff item type `3`. The password definition SHALL use type `password`; the other definitions SHALL use type `text`. Their application default values SHALL be SQL NULL.

#### Scenario: Definitions absent
- **WHEN** the update runs and one of these application property names is missing
- **THEN** it SHALL insert the missing definition

#### Scenario: Definitions already present
- **WHEN** the update runs and one of these application property names already exists
- **THEN** it SHALL leave that existing definition unchanged and SHALL NOT insert a duplicate for that name
