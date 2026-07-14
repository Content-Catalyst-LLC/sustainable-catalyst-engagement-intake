# Release Procedure — v1.1.0

## Release identity

- Product: Sustainable Catalyst Contact and Engagement Platform
- Release: Advisory Operations and Engagement Lifecycle
- Plugin slug: `sustainable-catalyst-engagement-intake`
- Plugin version: `1.1.0`
- Database version: `1.1.0`
- Lifecycle schema: `1.0.0`
- Platform evidence schema: `1.1.0`
- Portal schema: `1.4.0`
- Engagement schema: `1.1.0`
- Lifecycle migration: `v1_1_0_advisory_operations_engagement_lifecycle`

The v1.1.0 migration is nondestructive and idempotent.

## Required repository checks

- PHP syntax across plugin and tests
- JavaScript syntax for public and admin bundles
- every executable repository test suite
- database table and inquiry-column contracts
- lifecycle-stage and allowed-transition contracts
- typed human transition and optimistic-lock contracts
- internal-note and Sender Portal isolation contracts
- task-reminder idempotency and opt-in delivery contracts
- Teams, proposal, and engagement linkage contracts
- privacy export, erasure, retention, diagnostics, readiness, cron, and uninstall contracts
- common-secret scan
- installable and repository ZIP roots
- release manifest and ZIP CRC verification
- fresh-extraction full retest

## Staging acceptance

- upgrade from v1.0.3 without record loss
- database version and lifecycle migration journal reach v1.1.0
- existing inquiries receive valid lifecycle stages
- a typed authorized transition records both lifecycle and audit events
- invalid stage jumps and stale concurrent edits are rejected
- internal notes never appear in Sender Portal output
- approved public stage, summary, and next step appear correctly
- task creation, completion, due handling, and idempotent reminders work
- lifecycle task email remains disabled unless intentionally enabled
- linked Teams offers, proposals, and engagements remain available
- advisory routes preserve source and service attribution
- privacy export and approved erasure include lifecycle records
- overdue lifecycle tasks and next actions block Production
- existing v1.0.3 live, inbox, pilot, backup, and operational controls remain effective

## Production promotion

1. Complete staging acceptance.
2. Back up the production database and protected storage.
3. Install v1.1.0 and clear all caches.
4. Complete database and lifecycle migration repairs.
5. Inspect backfilled records and assign operational owners.
6. Run Live Validation with a monitored recipient.
7. Confirm external inbox delivery and record current evidence.
8. Repeat controlled pilot checks for the current release.
9. Resolve all lifecycle and public-launch operational blockers.
10. Record current database and protected-storage backup evidence.
11. Require 100%, zero failures, and zero warnings.
12. Promote only through the typed human Production action.

Repository validation does not replace live WordPress-host validation.
